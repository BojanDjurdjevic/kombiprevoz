<?php

namespace Models;

use PDO;
use PDOException;
use Rules\Validator;
use Dompdf\Dompdf;
use Dompdf\Options;
use Error;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;
use stdClass;
use tidy;

class Order {
    public $id;
    public $order_id;
    public $tour_id;
    public $user_id;
    public $driver_id;
    public $places;
    public $add_from;
    public $add_to;
    public $date;
    public $price;
    public $total;
    public $code;
    public $voucher;
    public $deleted;
    public $new_add_from;
    public $new_add_to;
    public $newDate;
    public $newDateIn;
    public $newPlaces;
    public $driver;
    public $dep_id;
    public $selected;
    public $items;

    private $user;
    private $tour;
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
        $this->user = new User($this->db);
        $this->tour = new Tour($this->db);
    }

    //------------------------------- FUNCTIONS BEFORE THE ACTION ------------------------------//

    // Checking if the USER is OWNER of the order
    public function findUserId() 
    {
        $select = "SELECT user_id from orders 
                    INNER JOIN order_items on orders.id = order_items.order_id 
                    WHERE order_items.id = '$this->id'"
        ;
        $res = $this->db->query($select);
        $num = $res->rowCount();

        if($num > 0) {
            $row = $res->fetch(PDO::FETCH_OBJ);

            if($_SESSION['user']['id'] == $row->user_id) {
                return true;
            } else {
                return false;
            }           
        } else
        return false;
    }

    // How many places we have available for the requested date:
    public function availability($date) {
        $sql = "SELECT order_items.places, tours.seats from order_items
                INNER JOIN tours on tours.id = order_items.tour_id
                INNER JOIN orders on order_items.order_id = orders.id
                WHERE order_items.date = '$date'
                and order_items.tour_id = '$this->tour_id' 
        ";
        $res = $this->db->query($sql);
        $num = $res->rowCount();
        $occupated = 0;
        $seats = 0;
        //and orders.deleted = 0
        if($num > 0) {
            
            while($row = $res->fetch(PDO::FETCH_OBJ)) {
                $occupated += $row->places;
                $seats = $row->seats;
            }
            return $seats - $occupated;
        } else {
            $tSql = "SELECT seats from tours WHERE id = '$this->tour_id' and deleted = 0";
            $tRes = $this->db->query($tSql);
            $tNum = $tRes->rowCount();
            if($tNum > 0) {
                $row = $tRes->fetch(PDO::FETCH_OBJ);
                $seats = $row->seats;
                return $seats;
            } else 
            return 0;
        }
    }

    // CHECK if the DEADLINE (48H) for changes is NOT passed:
    public function checkDeadline() 
    {
        $current = "SELECT places, date, order_items.price, tour_id, time FROM order_items 
        INNER JOIN orders on order_items.order_id = orders.id
        INNER JOIN tours on order_items.tour_id = tours.id
        WHERE order_items.id = '$this->id'";
        $res = $this->db->query($current);
        $num = $res->rowCount();

        if($num > 0) {
            $row = $res->fetch(PDO::FETCH_OBJ);
            $test = date_create();
            $today = date("Y-m-d H:i:s", date_timestamp_get($test));
            $departure = date_create($row->date . " " . $row->time);
            //$deadline = date_sub($departure, date_interval_create_from_date_string("48 hours"));
            $deadline = date("Y-m-d H:i:s", strtotime("-48 hours", date_timestamp_get($departure)));

            $this->date = date("Y-m-d", date_timestamp_get($departure));
            $this->places = $row->places;
            $this->price = $row->price;
            $this->tour_id = $row->tour_id;

            if($deadline > $today) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    // CHECK if the new date isn't within 24H

    public function isUnlocked($d) {
        if($d == null) return false;
        $valid = explode('-', $d);
        if(!checkdate($valid[1], $valid[2], $valid[0])) {
            return false;
            //
            //exit();
        }
        $new_date = date_create($d);
        $requested = date("Y-m-d H:i:s", date_timestamp_get($new_date));
        $test = date_create();
        $now = date("Y-m-d H:i:s", date_timestamp_get($test));
        $unlock = date("Y-m-d H:i:s", strtotime("+25 hours", date_timestamp_get($test)));

        if($requested > $unlock) {
            return true;
        } else
            return false;
    }

    // CHECK if the requested DATE is departure day:
    public function isDeparture($d)
    {
        if(!isset($this->tour_id) || empty($this->tour_id)) {
            $sqlID = "SELECT tour_id from order_items WHERE order_id = '$this->id'";
            $res = $this->db->query($sqlID);
            $row = $res->fetch(PDO::FETCH_OBJ);
            $this->tour_id = $row->tour_id;
        }
        $sql = "SELECT departures from tours WHERE id = '$this->tour_id'";
        $res2 = $this->db->query($sql);
        $row2 = $res2->fetch(PDO::FETCH_OBJ);
        
        $days = $row2->departures;
        
        $days = explode(",", $days);

        $depDays = [];

        foreach( $days as $day ) {
            array_push( $depDays, (int)$day);
        }

        $orderDate = date('w', strtotime($d));

        if(in_array($orderDate, $depDays)) {
            return true;
        } else {
            return false;
        }
    }

    // Check the real price of the order:
    public static function totalPrice($db, $tourID, $places)
    {
        $sql = "SELECT price FROM tours WHERE id = :id";
        $stmt = $db->prepare($sql);

        if(filter_var($tourID, FILTER_VALIDATE_INT) && filter_var($places, FILTER_VALIDATE_INT)) {
            $stmt->bindParam(':id', $tourID);

            try {
                if($stmt->execute()) {
                    $row = $stmt->fetch(PDO::FETCH_OBJ);
                    
                    if($row) {
                        return (int)$row->price * (int)$places;
                    } else return null;
                }
            } catch (PDOException $e) {
                json_encode([
                    'order' => 'Došlo je do greške pri konekciji na bazu podataka.',
                    'msg' => $e->getMessage()
                ]);
                exit();
            }
        } else return null;
    }

    //------------------------------- FUNCTIONS AFTER THE ACTION ------------------------------//

    public function updateTotalPrice() 
    {
        $find = "SELECT SUM(price) as total FROM order_items WHERE order_id = :order_id";
        $this->order_id = htmlspecialchars($this->order_id);
        $stmtF = $this->db->prepare($find);
        $stmtF->bindParam(':order_id', $this->order_id);
        $total = 0;
        try {
            $stmtF->execute();
            $t = $stmtF->fetch(PDO::FETCH_OBJ);
            if($t) {
                $total = $t->total;
            }
        } catch(PDOException $e) {
            throw new Error($e->getMessage());
        }
        $sql = "UPDATE orders SET total = :total WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        //
        $params = [
            ':total' => $total,
            ':id' => $this->order_id
        ];

        try {
            $stmt->execute(Validator::cleanParams($params));
            return $total;
        } catch (PDOException $e) {
            throw new Error($e->getMessage());
        }
    }

    public function generateVoucher( ? int $total): array 
    {
        $this->user->id = $this->user_id;
        $this->tour->id = $this->items->create[0]->tour_id;

        $owner = $this->user->getByID();
                    
        $tourObj = $this->tour->getByID();

        $sql = "SELECT orders.id, orders.user_id, orders.code, orders.file_path, orders.total, 
                order_items.* FROM orders
                INNER JOIN order_items on orders.id = order_items.order_id
                WHERE orders.id = :id     
        ";

        $drive = [];

        $stmt = $this->db->prepare($sql);
        $this->id = htmlspecialchars($this->id);
        $stmt->bindParam(':id', $this->id);

        try {
            if($stmt->execute()) {
                while($row = $stmt->fetch(PDO::FETCH_OBJ)) {
                    array_push($drive, $row);
                }
            }
        } catch (PDOException $e) {
            throw new Exception($e->getMessage());
        }
                    
        $options = new Options();
        $options->setChroot("src/assets/img");
        $pdf = new Dompdf($options);
        $pdf->setPaper("A4", "Portrait");

        $d = Validator::formatDateForFront($drive[0]->date);

        $html = file_get_contents("src/template.html");
        $html = str_replace("{{ order }}", $drive[0]->code, $html);

        $html = str_replace("{{ name }}", $owner[0]['name'], $html);

        $html = str_replace("{{ places }}", $drive[0]->places, $html);
        $html = str_replace("{{ address }}", $drive[0]->add_from, $html);
        $html = str_replace("{{ city }}", $tourObj[0]['from_city'], $html);
        $html = str_replace("{{ address_to }}", $drive[0]->add_to, $html);
        $html = str_replace("{{ city_to }}", $tourObj[0]['to_city'], $html);
        $html = str_replace("{{ date }}", $d, $html);
        $html = str_replace("{{ time }}", $tourObj[0]['time'], $html);
        $html = str_replace("{{ price }}", $drive[0]->price, $html);

        if(count($drive) > 1) {
            $dIn = Validator::formatDateForFront($drive[1]->date);

            $html = str_replace("{{ view }}", "visible", $html);
            $html = str_replace("{{ places2 }}", $drive[1]->places, $html);
            $html = str_replace("{{ address2 }}", $drive[1]->add_from, $html);
            $html = str_replace("{{ city2 }}", $tourObj[0]['to_city'], $html);
            $html = str_replace("{{ address_to2 }}", $drive[1]->add_to, $html);
            $html = str_replace("{{ city_to2 }}", $tourObj[0]['from_city'], $html);
            $html = str_replace("{{ date2 }}", $dIn, $html);
            $html = str_replace("{{ time2 }}", $tourObj[0]['time'], $html);
            $html = str_replace("{{ price2 }}", $drive[1]->price, $html);
            if($total) $html = str_replace("{{ total }}", $total, $html);
            else $html = str_replace("{{ total }}", 'N/A', $html);
        } else {
            $html = str_replace("{{ view }}", "invisible", $html);
            $html = str_replace("{{ places2 }}", "N/A", $html);
            $html = str_replace("{{ address2 }}", "N/A", $html);
            $html = str_replace("{{ city2 }}", "N/A", $html);
            $html = str_replace("{{ address_to2 }}", "N/A", $html);
            $html = str_replace("{{ city_to2 }}", "N/A", $html);
            $html = str_replace("{{ date2 }}", "N/A", $html);
            $html = str_replace("{{ time2 }}", "N/A", $html);
            $html = str_replace("{{ price2 }}", "N/A", $html);
            $html = str_replace("{{ total }}", 'N/A', $html);
        }

        $html = str_replace("{{ year }}", date("Y"), $html);

        $pdf->loadHtml($html);

        $pdf->render(); // Obavezno!!!
        $pdf->addInfo("Title", "Kombiprevoz - rezervacija: ". $drive[0]->code);
        //$pdf->stream("Rezervacija.pdf");
        $file_path = "src/assets/pdfs/". $drive[0]->code . ".pdf";
                    
        $output = $pdf->output();
        file_put_contents($file_path, $output);
        return [
            'email' => $owner[0]['email'],
            'name' => $owner[0]['name'],
            'path' => $file_path,
            'code' => $drive[0]->code
        ];
    }

    public function reGenerateVoucher() 
    {
        $this->user->id = $this->user_id;
        $this->tour->id = $this->tour_id;
        
        $owner = $this->user->getByID();          
        $tourObj = $this->tour->getByID();
        $myOrder = $this->getDriverOfTour();
                    
        $options = new Options();
        $options->setChroot("src/assets/img");
        $pdf = new Dompdf($options);
        $pdf->setPaper("A4", "Portrait");
        // Drivers for each item
        if($myOrder != NULL && $myOrder['items'][0]['driver'] != NULL) {
            $arr = explode(" ", $myOrder['items'][0]['driver']['name']);
            $myDriver = $arr[0];
        } else $myDriver = null;
        if($myOrder != NULL && $myOrder['items'][1]['driver'] != NULL) {
            $arr = explode(" ", $myOrder['items'][1]['driver']['name']);
            $myDriver2 = $arr[0];
        } else $myDriver2 = null;
        // Dates for each item
        if($myOrder != NULL) {
           $formated = date_create($myOrder['items'][0]['order']['date']); 
        } else 
        $formated = date_create($this->date); 
        $d = date("d.m.Y", date_timestamp_get($formated));
        if($myOrder != NULL) {
           $formated2 = date_create($myOrder['items'][0]['order']['date']); 
        } else 
        $formated2 = date_create($this->date); 
        $d2 = date("d.m.Y", date_timestamp_get($formated2));
        // In the title
        if($myOrder != NULL) {
            $html = file_get_contents("src/updated.html");
        } else $html = file_get_contents("src/template.html");
        if($myOrder != NULL) {
            $html = str_replace("{{ order }}", $myOrder['items'][0]['order']['code'], $html);
        } else
        $html = str_replace("{{ order }}", $this->code, $html);

        $html = str_replace("{{ name }}", $owner[0]['name'], $html);
        // First Item
        if($myOrder != NULL) {
            $html = str_replace("{{ places }}", $myOrder['items'][0]['order']['places'], $html);
        } else
        $html = str_replace("{{ places }}", $this->places, $html);
        if($myOrder != NULL) {
            $html = str_replace("{{ address }}", $myOrder['items'][0]['order']['pickup'], $html);
        } else
        $html = str_replace("{{ address }}", $this->add_from, $html);
        $html = str_replace("{{ city }}", $tourObj[0]['from_city'], $html);
        if($myOrder != NULL) {
            $html = str_replace("{{ address_to }}", $myOrder['items'][0]['order']['dropoff'], $html);
        } else
        $html = str_replace("{{ address_to }}", $this->add_to, $html);
        $html = str_replace("{{ city_to }}", $tourObj[0]['to_city'], $html);
        $html = str_replace("{{ date }}", $d, $html);
        $html = str_replace("{{ time }}", $tourObj[0]['time'], $html);
        if($myOrder != NULL) {
            $html = str_replace("{{ price }}", $myOrder['items'][0]['order']['price'], $html);
        } else
        $html = str_replace("{{ price }}", $this->price, $html);

        if($myOrder != NULL && $myDriver) {
            $html = str_replace("{{ driver }}", $myDriver, $html);
            $html = str_replace("{{ drphone }}", $myOrder['items'][0]['driver']['dr_phone'], $html);
            $html = str_replace("{{ drmail }}", $myOrder['items'][0]['driver']['dr_email'], $html);
            $html = str_replace("{{ driver_view }}", "visible", $html);
        } else {
            $html = str_replace("{{ driver_view }}", "invisible", $html);
        }

        // 2nd Item - Inbound
        if($myOrder != NULL && $myOrder['items'][1]) {
            $html = str_replace("{{ view }}", "visible", $html);
            $html = str_replace("{{ places2 }}", $myOrder['items'][1]['order']['places'], $html);
        
            $html = str_replace("{{ address2 }}", $myOrder['items'][1]['order']['pickup'], $html);

            $html = str_replace("{{ address2 }}", $this->add_from, $html);
            $html = str_replace("{{ city2 }}", $tourObj[0]['to_city'], $html);

            $html = str_replace("{{ address_to2 }}", $myOrder['items'][1]['order']['dropoff'], $html);

            $html = str_replace("{{ address_to2 }}", $this->add_to, $html);
            $html = str_replace("{{ city_to2 }}", $tourObj[0]['from_city'], $html);
            $html = str_replace("{{ date2 }}", $d, $html);
            $html = str_replace("{{ time2 }}", $tourObj[0]['time'], $html);
            
            $html = str_replace("{{ price2 }}", $myOrder['items'][1]['order']['price'], $html);
            $html = str_replace("{{ price3 }}", $myOrder['items'][0]['order']['total'], $html);
            
            if($myOrder != NULL && $myDriver2) {
                $html = str_replace("{{ driver2 }}", $myDriver2, $html);
                $html = str_replace("{{ drphone2 }}", $myOrder['items'][1]['driver']['dr_phone'], $html);
                $html = str_replace("{{ drmail2 }}", $myOrder['items'][1]['driver']['dr_email'], $html);
                $html = str_replace("{{ driver_view2 }}", "visible", $html);
            } else {
                $html = str_replace("{{ driver_view2 }}", "invisible", $html);
            }
        } else $html = str_replace("{{ view }}", "invisible", $html);
        //In footer
        $html = str_replace("{{ year }}", date("Y"), $html);
        // Render voucher and return data
        $pdf->loadHtml($html);

        $pdf->render();
        if($myOrder != NULL) {
            $pdf->addInfo("Title", "Kombiprevoz - rezervacija: ". $myOrder['items'][0]['order']['code']);
        } else
        $pdf->addInfo("Title", "Kombiprevoz - rezervacija: ". $this->code);
        if($myOrder != NULL) {
            $file_path = $myOrder['items'][0]['order']['voucher'];
        } else
        $file_path = $this->voucher;
                    
        $output = $pdf->output();
        file_put_contents($file_path, $output);
        if($myOrder != NULL && $myDriver or $myDriver2) {
            return [
                'email' => $owner[0]['email'],
                'name' => $owner[0]['name'],
                'path' => $file_path,
                'code' => $myOrder['items'][0]['order']['code'],
                'driver' => $myDriver,
                'driver_phone' => $myOrder['items'][0]['driver']['dr_phone'],
                'driver_email' => $myOrder['items'][0]['driver']['dr_phone'],
                'driver2' => $myDriver2,
                'driver_phone2' => $myOrder['items'][1]['driver']['dr_phone'],
                'driver_email2' => $myOrder['items'][1]['driver']['dr_phone']
            ];
        } else
        return [
            'email' => $owner[0]['email'],
            'name' => $owner[0]['name'],
            'path' => $file_path,
            'code' => $this->code
        ];
    }

    public function sendVoucher($email, $name, $path, $new_code, $goal)
    {
        $template = '';
        if($goal === 'create') {
            $template = "<p> Poštovani/a {$name}, </p>
            <br>
            <p> Uspešno ste rezervisali vašu vožnju! </p>
            <br>
            <p> Broj vaše rezervacije je: <b> $new_code </b> </p>
            <br>
            <p> U prilogu Vam šaljemo potvrdu rezervacije. </p>
            <br><br>
            <p> Srdačan pozdrav od KombiPrevoz tima! </p>";
        } elseif($goal === 'update') {
            $template = "<p> Poštovani/a {$name}, </p>
            <br>
            <p> Uspešno ste izmenili vašu vožnju! </p>
            <br>
            <p> Broj vaše rezervacije je: <b> $new_code </b> </p>
            <br>
            <p> U prilogu Vam šaljemo ažuriranu potvrdu rezervacije. </p>
            <br><br>
            <p> Srdačan pozdrav od KombiPrevoz tima! </p>";
        } else {
            $template = "<p> Poštovani/a {$name}, </p>
            <br>
            <p> Vaša rezervacija broj <b> $new_code </b> je ažurirana </p>
            <br>
            <p> U prilogu Vam šaljemo ažuriranu potvrdu rezervacije. </p>
            <br><br>
            <p> Srdačan pozdrav od KombiPrevoz tima! </p>";
        }

        $mail = new PHPMailer(true);
        //$mail->SMTPDebug = SMTP::DEBUG_SERVER;
        $mail->isSMTP();
        $mail->SMTPAuth = true;

        $mail->Host = "smtp.gmail.com";
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = 465;
        $mail->Username = $_ENV["SMTP_USER"];
        $mail->Password = $_ENV["SMTP_PASS"];

        $mail->setFrom("noreply-kombiprevoz@gmail.com", "Bojan");
        $mail->addAddress($email, $name);
        $mail->isHTML(true);
        $mail->addAttachment($path, "Kombiprevoz - rezervacija: ". $new_code);
        $mail->Subject = "Potvrda Rezervacije";
        $mail->Body = <<<END

            $template
                        
        END;

        try {
            $mail->send();
            //echo json_encode(['email' => 'Potvrda je upravo poslata na Vašu email adresu. Molimo proverite Vaš email!']);
        } catch (Exception $e) {
            echo json_encode([
                'email' => 'Došlo je do greške!',
                'msg' => $mail->ErrorInfo
            ]);
        }
    }

    // ------ PDF of all passangers and reservations to DRIVER
    public function generateDeparture($users, $new_code, $dateTime)
    {        
        $options = new Options();
        $options->setChroot("src/assets/img");
        $pdf = new Dompdf($options);
        $pdf->setPaper("A4", "Portrait");

        $formated = date_create($dateTime);
        $d = date_format($formated, "d.m.Y H:i"); 

        $template = '';
        foreach ($users as $pax) {
            $template .= Validator::mailerDriverTemplate($pax->code, $pax->user->name, $pax->places, $pax->pickup, $pax->from_city, $pax->dropoff, $pax->to_city, $pax->date, $pax->pickuptime, $pax->price, $pax->user->phone);
        }

        $html = file_get_contents("src/driver.html");
        $html = str_replace("{{ code }}", $new_code, $html);
        $html = str_replace("{{ dateTime }}", $d, $html);
        $html = str_replace("{{ main }}", $template, $html);
        $html = str_replace("{{ year }}", date("Y"), $html);

        $pdf->loadHtml($html);

        $pdf->render();
        $pdf->addInfo("Title", "Kombiprevoz - vožnja: ". $new_code);
        $file_path = "src/assets/pdfs/". $new_code . ".pdf";
                    
        $output = $pdf->output();
        file_put_contents($file_path, $output);
        return [
            'path' => $file_path
        ];
    }

    // ------ Email about reservations to DRIVER
    public function sendOrdersToDriver($name, $new_code, $path, $email)
    {        
        $template = "<p> Poštovani/a {$name}, </p>
        <br>
        <p> Sistem Vam je dodelio vožnju broj: <b> $new_code </b> </p>
        <br>
        <p> U prilogu Vam šaljemo spisak svih porudžbina, sa imenima i podacima putnika. </p>
        <p> Savetujemo Vam da napravite svoju rutu i poredak kupljenja i ostavljanja putnika sa/na adrese. </p>
        <p> Takođe Vas molimo da dan pre polaska, a nakon pravljenja redosleda preuzimanja putnika, 
        svim putnicima blagovremeno javite okvirno vreme kada ćete po njih doći. </p>
        <p>Hvala!</p>
        <br><br>
        <p> Srdačan pozdrav od KombiPrevoz tima! </p>";

        $mail = new PHPMailer(true);
        //$mail->SMTPDebug = SMTP::DEBUG_SERVER;
        $mail->isSMTP();
        $mail->SMTPAuth = true;

        $mail->Host = "smtp.gmail.com";
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = 465;
        $mail->Username = $_ENV["SMTP_USER"];
        $mail->Password = $_ENV["SMTP_PASS"];

        $mail->setFrom("noreply-kombiprevoz@gmail.com", "Bojan");
        $mail->addAddress($email, $name);
        $mail->isHTML(true);
        $mail->addAttachment($path, "Kombiprevoz - rezervacija: ". $new_code);
        $mail->Subject = "Potvrda Rezervacije";
        $mail->Body = <<<END

            $template
                        
        END;

        try {
            $mail->send();
        } catch (Exception $e) {
            echo json_encode([
                'email' => 'Došlo je do greške!',
                'msg' => $mail->ErrorInfo
            ]);
        }
    }

    //------------------------------- AFTER ACTION HELPERS RELATED WITH ANOTHER CLASSES --------------------------------//

    public function availableDrivers($date, $tour_id) {
        $sql = "SELECT users.id, users.name, users.status, users.email, users.phone, users.city FROM users
                WHERE users.status = 'driver' AND
                NOT EXISTS (SELECT 1 FROM departures WHERE driver_id = users.id AND departures.date = :date)
                AND users.city IN 
                (
                    (SELECT from_city FROM tours WHERE id = :tour_id), 
                    (SELECT to_city FROM tours WHERE id = :tour_id)
                ) 
                "
        ;
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':date', $date);
        $stmt->bindParam(':tour_id', $tour_id);
        
        try {
            if($stmt->execute()) {
                $drivers = $stmt->fetchAll(PDO::FETCH_OBJ);
                return $drivers;
            }
        } catch(PDOException $e) {
            return json_encode(['message' => $e->getMessage()]);
        }
    }

    //------------------------------- FUNCTIONS OF GET METHOD --------------------------------//

    public function getAll($in24, $in48) 
    {
        if($in24 && empty($in48)) {
            $tomorrow = date("Y-m-d", strtotime("+1 day"));
        } elseif($in48 && empty($in24)) {
            $tomorrow = date("Y-m-d", strtotime("+2 days"));
        } else {
            echo json_encode(['error' => 'Odaberite 24h ili 48h']);
            exit();
        } 
        $sql = "SELECT order_items.id as order_item_id, tours.id as tour_id, orders.user_id as user_id, order_items.places, tours.from_city, 
                order_items.add_from as pickup, tours.to_city, order_items.add_to as dropoff,
                order_items.date, tours.time as pickuptime, tours.duration,
                orders.total as price, orders.code, orders.file_path as voucher, users.name as user_name, users.email as email, users.phone as phone
                from orders 
                INNER JOIN order_items on order_items.order_id = orders.id
                INNER JOIN tours on order_items.tour_id = tours.id
                INNER JOIN users on orders.user_id = users.id
                WHERE orders.deleted = 0 and order_items.deleted = 0
                and order_items.date = :tomorrow
                ORDER BY tours.id, order_items.date, pickuptime"
        ;
        $stmt = $this->db->prepare($sql);
        
        /*
        $test = date_create();
        $now = date("Y-m-d H:i:s", date_timestamp_get($test));
        $tomorrow = date("Y-m-d", strtotime("+24 hours", date_timestamp_get($test))); */

        $stmt->bindParam(':tomorrow', $tomorrow);

        try {
            if($stmt->execute()) {
                $rows = $stmt->fetchAll(PDO::FETCH_OBJ);
                $tourID = 0;
                $orders = [];
                foreach($rows as $row) {
                    $tId = $row->tour_id;
                    $tourID = $tId;
                    if(!isset($orders[$tId])) {
                        $orders[$tId] = [
                            'tour_id' => $row->tour_id,
                            'from_city' => $row->from_city,
                            'to_city' => $row->to_city,
                            'pickuptime' => $row->pickuptime,
                            'duration' => $row->duration,
                            'date' => $tomorrow,
                            'rides' => []
                        ];
                    }

                    $orders[$tId]['rides'][] = [
                        'order_item_id' => $row->order_item_id,
                        'places'        => $row->places,
                        'pickup'        => $row->pickup,
                        'dropoff'       => $row->dropoff,
                        'date'          => $row->date,
                        'price'         => $row->price,
                        'voucher'       => $row->voucher,
                        'code'          => $row->code,   
                        'from_city'     => $row->from_city,
                        'to_city'       => $row->to_city, 
                        'pickuptime'    => $row->pickuptime,
                        'user'          => [
                            'id'    => $row->user_id,
                            'name'  => $row->user_name,
                            'email' => $row->email,
                            'phone' => $row->phone,
                        ]
                    ];
                }
                $drivers = $this->availableDrivers($tomorrow, $tourID);
                echo json_encode(['orders' => $orders, 'has_orders' => !empty($orders), 'drivers' => $drivers]);
            }
        } catch(PDOException $e) {
            http_response_code(500);
            echo json_encode([
                'error' => 'Došlo je do greške pri učitavanju iz baze',
                'msg' => $e->getMessage() 
            ]);
        }
        
    }

    public function getAllByDate() 
    {
        $sql = "SELECT orders.id, orders.tour_id, orders.user_id, orders.places, tours.from_city, 
                orders.add_from as pickup, tours.to_city, orders.add_to as dropoff,
                orders.date, tours.time as pickuptime, tours.duration,
                orders.total as price, orders.code, orders.file_path as voucher, users.name as user, users.email, users.phone
                from orders 
                INNER JOIN tours on orders.tour_id = tours.id
                INNER JOIN users on orders.user_id = users.id
                WHERE orders.date = '$this->date' AND orders.deleted = 0"
        ;
        $res = $this->db->query($sql);
        $num = $res->rowCount();

        if($num > 0) {
            $drivers = $this->user->getAvailableDrivers($this->date);
            $orders = [];
            while($row = $res->fetch(PDO::FETCH_OBJ)) {
                array_push($orders, $row);
            }
            echo json_encode(['orders' => $orders, 'drivers' => $drivers], JSON_PRETTY_PRINT);
        } else
        echo json_encode(['msg' => 'Nema rezervisanih vožnji za traženi datum.']);
    }

    public function getAllByDateRange(? string $from, ? string $to)
    {
        $now = date("Y-m-d");
        $sql = "";
        if(isset($from) && isset($to)) {
            $sql = "SELECT orders.id, orders.tour_id, orders.user_id, orders.places, tours.from_city, 
                orders.add_from as pickup, tours.to_city, orders.add_to as dropoff,
                orders.date, tours.time as pickuptime, tours.duration,
                orders.total as price, orders.code, orders.file_path as voucher, users.name as user, users.email, users.phone
                from orders 
                INNER JOIN tours on orders.tour_id = tours.id
                INNER JOIN users on orders.user_id = users.id
                WHERE orders.date >= '$from' AND orders.date <= '$to' AND orders.deleted = 0
                ORDER BY orders.date"
            ;
        } elseif(isset($from) && !isset($to)) {
            $sql = "SELECT orders.id, orders.tour_id, orders.user_id, orders.places, tours.from_city, 
                orders.add_from as pickup, tours.to_city, orders.add_to as dropoff,
                orders.date, tours.time as pickuptime, tours.duration,
                orders.total as price, orders.code, orders.file_path as voucher, users.name as user, users.email, users.phone
                from orders 
                INNER JOIN tours on orders.tour_id = tours.id
                INNER JOIN users on orders.user_id = users.id
                WHERE orders.date >= '$from' AND orders.deleted = 0
                ORDER BY orders.date"
            ;
        } elseif(!isset($from) && isset($to)) {
            $sql = "SELECT orders.id, orders.tour_id, orders.user_id, orders.places, tours.from_city, 
                orders.add_from as pickup, tours.to_city, orders.add_to as dropoff,
                orders.date, tours.time as pickuptime, tours.duration,
                orders.total as price, orders.code, orders.file_path as voucher, users.name as user, users.email, users.phone
                from orders 
                INNER JOIN tours on orders.tour_id = tours.id
                INNER JOIN users on orders.user_id = users.id
                WHERE orders.date >= '$now' AND orders.date <= '$to' AND orders.deleted = 0
                ORDER BY orders.date"
            ;
        }
        
        $res = $this->db->query($sql);
        $num = $res->rowCount();

        if($num > 0) {
            $orders = [];
            while($row = $res->fetch(PDO::FETCH_OBJ)) {
                array_push($orders, $row);
            }
            echo json_encode([
                'orders'=> $orders
            ], JSON_PRETTY_PRINT);
        } else
        echo json_encode(['msg' => 'Nema rezervisanih vožnji za odabrane datume.'], JSON_PRETTY_PRINT);
    }

    public function getByUser() 
    {
        $this->user_id = htmlspecialchars(strip_tags($this->user_id));
        $sql = "SELECT orders.id, order_items.id as item_id, 
                order_items.tour_id, orders.user_id, order_items.places, tours.from_city, 
                order_items.add_from as pickup, tours.to_city, order_items.add_to as dropoff,
                order_items.date, order_items.price, order_items.deleted,
                tours.time as pickuptime, tours.duration,
                orders.total, orders.code, orders.file_path as voucher, 
                users.name as user, users.email, users.phone
                from orders 
                JOIN order_items on orders.id = order_items.order_id
                JOIN tours on order_items.tour_id = tours.id
                JOIN users on orders.user_id = users.id
                
                WHERE orders.user_id = '$this->user_id' AND orders.deleted = 0

                Order BY order_items.date ASC"
        ;
        try {
            $res = $this->db->query($sql);
            $num = $res->rowCount();

            if($num > 0) {
                $rows = $res->fetchAll(PDO::FETCH_OBJ);
                $orders = [];
                foreach($rows as $row) {
                    //var_dump($row->id);
                    $orderId = $row->id;

                    if(!isset($orders[$orderId])) {
                        $order = new stdClass();
                        $order->id = $row->id;
                        $order->code = $row->code;
                        $order->duration = $row->duration;
                        $order->voucher = $row->voucher;
                        $order->total = $row->total;
                        $order->items = [];
                        $orders[$orderId] = $order;
                    }

                    


                    $item = new stdClass();
                    $item->id = $row->item_id;
                    $item->tour_id = $row->tour_id;
                    $item->date = $row->date;
                    $item->time = $row->pickuptime;
                    $item->pickup = $row->pickup;
                    $item->dropoff = $row->dropoff;
                    $item->places = $row->places;
                    $item->price = $row->price;
                    $item->from = $row->from_city;
                    $item->to = $row->to_city;
                    $item->deleted = $row->deleted;

                    $orders[$orderId]->items[] = $item;
                }
                echo json_encode([
                    'success' => true,
                    'orders' => array_values($orders)
                ], JSON_PRETTY_PRINT);
            } else {
                echo json_encode([
                    'success' => false,
                    'msg' => 'Nema rezervacija od ovog korisnika.'
                ], JSON_PRETTY_PRINT);
            } 
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ], JSON_PRETTY_PRINT);
        }
    }

    public function getByCode() {
        $sql = "SELECT orders.id, orders.tour_id, orders.user_id, orders.places, tours.from_city, 
                orders.add_from as pickup, tours.to_city, orders.add_to as dropoff,
                orders.date, tours.time as pickuptime, tours.duration,
                orders.total as price, orders.code, orders.file_path as voucher, orders.deleted,
                users.name as user, users.email, users.phone
                from orders 
                INNER JOIN tours on orders.tour_id = tours.id
                INNER JOIN users on orders.user_id = users.id
                WHERE orders.code = :code"
        ;
        $stmt = $this->db->prepare($sql);

        if(Validator::validateString($this->code)) {
            $this->code = htmlspecialchars(strip_tags($this->code));
            $stmt->bindParam(':code', $this->code);

            try {
                if($stmt->execute()) {
                    $order = $stmt->fetch(PDO::FETCH_OBJ);
                    //$num = $order->rowCount();

                    if($order) {
                        echo json_encode(['order' => $order], JSON_PRETTY_PRINT);
                    } else echo json_encode(['order' => 'Rezervacija nije pronađena.'], JSON_PRETTY_PRINT);
                }
            } catch (PDOException $e) {
                echo json_encode([
                    'order' => 'Došlo je do greške pri konekciji na bazu podataka.',
                    'msg' => $e->getMessage()
                ], JSON_PRETTY_PRINT);
            }
        } else
        echo json_encode(['order' => 'Pogrešno unet broj rezervacije. Molimo Vas da unesete validan kod
                        koji sadrži 7 brojeva i 2 velika slova: xxxxxxxKP'], JSON_PRETTY_PRINT);
    }

    public function getItems($order_id) 
    {
        $sql = "SELECT order_items.*, orders.user_id, orders.code, 
                orders.file_path as voucher, orders.total
                FROM order_items 
                INNER JOIN orders on order_items.order_id = orders.id
                WHERE orders.id = :id"
        ; 
        $stmt = $this->db->prepare($sql);
        $order_id = htmlspecialchars(strip_tags($order_id), ENT_QUOTES);
        $stmt->bindParam(':id', $order_id);

        try {
            if($stmt->execute()) {
                $order = new stdClass();
                while($row = $stmt->fetch(PDO::FETCH_OBJ)) {
                    $order->items[] = $row;
                }
                $this->items = $order;
            }
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
            return null;
        }
    }

    public function getFromDB($id) 
    {
        $sql = "SELECT order_items.*, orders.* FROM order_items 
        INNER JOIN orders on order_items.order_id = orders.id
        WHERE order_items.id = :id";
        $stmt = $this->db->prepare($sql);

        $id = htmlspecialchars(strip_tags($id), ENT_QUOTES);
        $stmt->bindParam(':id', $id);

        try {
            if($stmt->execute()) {
                $order = $stmt->fetch(PDO::FETCH_OBJ);
                if($order) {
                    $this->tour_id = $order->tour_id;
                    $this->user_id = $order->user_id;
                    $this->places = $order->places;
                    $this->add_from = $order->add_from;
                    $this->add_to = $order->add_to;
                    $this->date = $order->date;
                    $this->total = $order->total;
                    $this->price = $order->price;
                    $this->code = $order->code;
                    $this->voucher = $order->file_path;
                    $this->driver_id = $order->driver_id;
                    $this->dep_id = $order->dep_id;
                    $this->order_id = $order->order_id;

                    $this->getItems($this->order_id);
                    
                    return $order; 
                } 
                else return null;
            }
        } catch (PDOException $e) {
            echo json_encode(['error' => $e->getMessage()]);
            return null;
        }
    }

    public function getByTour() {
        $sql = "SELECT orders.id, orders.tour_id, orders.user_id, orders.places, tours.from_city, 
                orders.add_from as pickup, tours.to_city, orders.add_to as dropoff,
                orders.date, tours.time as pickuptime, tours.duration,
                orders.total as price, orders.code, orders.file_path as voucher, users.name as user, users.email, users.phone
                from orders 
                INNER JOIN tours on orders.tour_id = tours.id
                INNER JOIN users on orders.user_id = users.id
                WHERE orders.tour_id = '$this->tour_id' AND orders.deleted = 0"
        ;
        $res = $this->db->query($sql);
        $num = $res->rowCount();

        if($num > 0) {
            $orders = [];
            while($row = $res->fetch(PDO::FETCH_OBJ)) {
                array_push($orders, $row);
            }
            echo json_encode([
                'orders'=> $orders
            ], JSON_PRETTY_PRINT);
        } else
        echo json_encode(['msg' => 'Nema rezervisanih vožnji za odabrane destinacije.'], JSON_PRETTY_PRINT);
    }

    public function getByTourAndDate() {
        $sql = "SELECT orders.id, orders.tour_id, orders.user_id, orders.places, tours.from_city, 
                orders.add_from as pickup, tours.to_city, orders.add_to as dropoff,
                orders.date, tours.time as pickuptime, tours.duration,
                orders.total as price, orders.code, orders.file_path as voucher, users.name as user, users.email, users.phone
                from orders 
                INNER JOIN tours on orders.tour_id = tours.id
                INNER JOIN users on orders.user_id = users.id
                WHERE orders.tour_id = '$this->tour_id' AND orders.date = '$this->date' AND orders.deleted = 0"
        ;
        $res = $this->db->query($sql);
        $num = $res->rowCount();

        if($num > 0) {
            $drivers = $this->user->getAvailableDrivers($this->date);
            $orders = [];
            while($row = $res->fetch(PDO::FETCH_OBJ)) {
                array_push($orders, $row);
            }
            echo json_encode([
                'orders'=> $orders,
                'drivers' => $drivers
            ], JSON_PRETTY_PRINT);
        } else
        echo json_encode(['msg' => 'Nema rezervisanih vožnji za odabrane datume.'], JSON_PRETTY_PRINT);
    }

    public function getDriverOfTour()
    {
        $sql = "SELECT orders.id, order_items.id as item_id, order_items.tour_id, 
                orders.driver_id, order_items.places, tours.from_city, 
                order_items.add_from as pickup, tours.to_city, order_items.add_to as dropoff,
                order_items.date, tours.time as pickuptime, tours.duration,
                order_items.price, orders.total, orders.code, orders.file_path as voucher
                
                from order_items 
                INNER JOIN orders on order_items.order_id = orders.id
                INNER JOIN tours on order_items.tour_id = tours.id
                
                WHERE orders.id = '$this->order_id' AND order_items.deleted = 0"
        ;
        /**
        users.name as driver, users.email as dr_email, users.phone as dr_phone
        INNER JOIN users on orders.driver_id = users.id
         */
        $res = $this->db->query($sql);
        $orders = $res->fetchAll(PDO::FETCH_ASSOC);
        
        $items = [];
        //if($order) {
            foreach($orders as $order) {
                if($order['driver_id']) {
                    $sdr = "SELECT users.name as driver, users.email as dr_email, 
                            users.phone as dr_phone FROM users
                            WHERE id = {$order['driver_id']}"
                    ;
                    $sdRes = $this->db->query($sdr);
                    $driver = $sdRes->fetch(PDO::FETCH_ASSOC);
                    if($driver) 
                    array_push($items, [
                        'order' => $order,
                        'driver' => $driver
                    ]);
                    else array_push($items, ['order' => $order, 'driver' => null]);
                }
                else array_push($items, ['order' => $order, 'driver' => null]);
            }
            
            return [
                'items' => $items
            ];
        //} else {
            //$this->getFromDB($this->id);
            //echo json_encode(['msg_drIsNull' => $order]);
          //  return null;
        //} 
    }

    //------------------------------- FUNCTIONS OF POST METHOD --------------------------------//

    public function create() 
    {
        $this->db->beginTransaction();

        try {
            $sql = "INSERT INTO orders SET
                    user_id = :user_id, total = :total, code = :code, file_path = :pdf
            ";
            $stmt = $this->db->prepare($sql); 
            
            $this->user_id = $this->items->create[0]->user_id;
            $now = time() + $this->user_id;
            $generated = (string)$now . "KP";
            $new_code = substr($generated, -9);
            $file_path = "src/assets/pdfs/". $new_code . ".pdf";
            $total = 0; 
            foreach($this->items->create as $item) {
                $total = $total + $item->price;
            }

            $params = [
                ':user_id' => $this->user_id,
                ':total' => $total,
                ':code' => $new_code,
                ':pdf' => $file_path
            ];

            try {
                try {
                    $stmt->execute(Validator::cleanParams($params));
                } catch (PDOException $e) {
                    throw new Error($e->getMessage() . " / " . Validator::cleanParams($params));
                }
                $this->id = $this->db->lastInsertId();
                
                foreach($this->items->create as $item) {
                    $this->add_from = $item->add_from;
                    $this->add_to = $item->add_to;
                    $this->tour_id = $item->tour_id;
                    $this->date = $item->date;
                    $this->places = $item->places;

                    if($this->places <= $this->availability($this->date)) {
                        if($this->isDeparture($this->date)) {
                            if($this->isUnlocked($this->date)) {
                                
                                $sql = "INSERT INTO order_items SET
                                        order_id = :order_id, tour_id = :tour_id, places = :places,
                                        add_from = :add_from, add_to = :add_to, date = :date, price = :price
                                ";
                                $stmt = $this->db->prepare($sql);

                                $this->tour_id = htmlspecialchars(strip_tags($this->tour_id));
                                $this->id = htmlspecialchars(strip_tags($this->id));
                                $this->places = htmlspecialchars(strip_tags($this->places));
                                $this->add_from = htmlspecialchars(strip_tags($this->add_from));
                                $this->add_to = htmlspecialchars(strip_tags($this->add_to));
                                $this->date = htmlspecialchars(strip_tags($this->date));
                                $this->price = $this->totalPrice($this->db, $this->tour_id, $this->places);
                                $this->price = htmlspecialchars(strip_tags($this->price));

                                if($this->price != null) {
                                    $stmt->bindParam(':order_id', $this->id);
                                    $stmt->bindParam(':tour_id', $this->tour_id);
                                    //$stmt->bindParam(':user_id', $this->user_id);
                                    $stmt->bindParam(':places', $this->places);
                                    $stmt->bindParam(':add_from', $this->add_from);
                                    $stmt->bindParam(':add_to', $this->add_to);
                                    $stmt->bindParam(':date', $this->date);
                                    $stmt->bindParam(':price', $this->price);
                                    // voucher
                                    //$mydata = $this->generateVoucher($new_code, $this->places, $this->add_from, $this->add_to, $this->date, $this->price);
                                    //
                                    //$stmt->bindParam(':pdf', $mydata['path']);

                                    try {
                                        /*
                                        //$this->sendVoucher($mydata['email'], $mydata['name'], $mydata['path'], $new_code, 'create');
                                        http_response_code(200);
                                        echo json_encode([
                                            'success' => true,
                                            'msg' => "Uspešno ste rezervisali vožnju. Vaš broj rezervacije je: {$new_code}"
                                        ], JSON_PRETTY_PRINT); */
                                        $stmt->execute();
                                        
                                    }
                                    catch (PDOException $e) { /*
                                        http_response_code(422);
                                        echo json_encode([
                                            'error' => 'Trenutno nije moguće rezervisati ovu vožnju.'
                                        ], JSON_PRETTY_PRINT); */
                                        throw new Exception($e->getMessage());
                                    } 
                                } else { /*
                                    http_response_code(422);
                                    echo json_encode(['error' => 'Trenutno nije moguće rezervisati ovu vožnju. 
                                                        Nolimo Vas da se obratite našem centru za podršku!'], JSON_PRETTY_PRINT); */
                                    throw new Exception('Trenutno nije moguće rezervisati ovu vožnju. 
                                                        Nolimo Vas da se obratite našem centru za podršku!');
                                } 
                            } else { /*
                                http_response_code(422);
                                echo json_encode(['error' => 'Žao nam je, do vožnje mora biti rezervisna najmanje 25 sati pre polaska.']); */
                                throw new Exception('Žao nam je, do vožnje mora biti rezervisna najmanje 25 sati pre polaska.');
                            }
                        } else { /*
                            http_response_code(422);
                            echo json_encode(['error' => 'Žao nam je, ali nemamo polaske na birani datum.']); */
                            throw new Exception('Žao nam je, ali nemamo polaske na birani datum.');
                        }
                    } else { /*
                        http_response_code(422);
                        echo json_encode(['error' => 'Žao nam je, ali nema više slobodnih mesta za ovu vožnju.']); */
                        throw new Exception('Žao nam je, ali nema više slobodnih mesta za ovu vožnju.');
                    }
                }

            } catch(PDOException $e) { /*
                http_response_code(500);
                return json_encode([
                    'error' => 'Došlo je do greške pri konekciji na bazu podataka',
                    'errMsg' => $e->getMessage() 
                ], JSON_PRETTY_PRINT); */
                throw new Exception($e->getMessage());
            }      
            
            $this->db->commit();
            // Update total price
            $total = $this->updateTotalPrice();
            //generate Voucher
            $mydata = $this->generateVoucher($total);
            // Email
            $this->sendVoucher($mydata['email'], $mydata['name'], $mydata['path'], $mydata['code'], 'create');

            return json_encode([
                'success' => true,
                'msg' => "Uspešno ste rezervisali vožnju. Vaš broj rezervacije je: {$new_code}"
            ], JSON_PRETTY_PRINT); 

        } catch(Exception $e) {
            $this->db->rollBack();
            http_response_code(422);
            return json_encode([
                'success' => false,
                'error' => 'Nije prošlo sve. '. "/ " . $e->getMessage()
            ], JSON_PRETTY_PRINT);
        }
        
    }

    //------------------------------- FUNCTIONS OF PUT METHOD --------------------------------// 

    public function updateAddress()
    {
        //$this->getFromDB($this->id);
        $sql = "UPDATE order_items SET add_from = :add_from, add_to = :add_to
                WHERE id = :id"
        ;
        $stmt = $this->db->prepare($sql);
        $this->id = htmlspecialchars(strip_tags($this->id));
        $this->add_from = htmlspecialchars(strip_tags($this->add_from));
        $this->add_to = htmlspecialchars(strip_tags($this->add_to));
        //$this->places = htmlspecialchars(strip_tags($this->places));
        $stmt->bindParam(":id", $this->id);
        $stmt->bindParam('add_from', $this->new_add_from);
        $stmt->bindParam('add_to', $this->new_add_to);
        //$stmt->bindParam('places', $this->places);
        if(!empty($this->new_add_from) && !empty($this->new_add_to)) {
            try {
                if($stmt->execute()) {
                    if(empty($this->newDate) && empty($this->newPlaces)) {
                        //$mydata = $this->generateVoucher($this->code);
                        //$this->sendVoucher($mydata['email'], $mydata['name'], $mydata['path'], $this->code, 'update');
                    }
                    echo json_encode([
                        "success" => true,
                        "msg" => 'Uspešno ste izmenili adresu/adrese rezervacije!',
                    ], JSON_PRETTY_PRINT);
                } 
            } catch (PDOException $e) {
                http_response_code(500);
                echo json_encode([
                    'error' => 'Došlo je do greške pri konekciji na bazu podataka.',
                    'db' => $e->getMessage()
                ], JSON_PRETTY_PRINT);
            }
            
        } else {
            http_response_code(422);
            echo json_encode(["error" => 'Molimo Vas da unesete validne adrese!']);
        }           
    }

    // Update ONLY number of places:
    public function updatePlaces() 
    {
        $this->getFromDB($this->id);
        if($this->newPlaces - $this->places <= $this->availability($this->date)) {
            $sql = "UPDATE order_items SET places = :places, price = :total WHERE id = :id";
            $stmt = $this->db->prepare($sql);

            $this->id = htmlspecialchars(strip_tags($this->id));
            $this->places = htmlspecialchars(strip_tags($this->places));
            $this->price = htmlspecialchars(strip_tags($this->price));
            $this->newPlaces = htmlspecialchars(strip_tags($this->newPlaces));
            
            $new_total = $this->totalPrice($this->db, $this->tour_id, $this->newPlaces);
            //($this->price / $this->places) * $this->newPlaces;

            $stmt->bindParam(':places', $this->newPlaces);
            $stmt->bindParam(':total', $new_total);
            $stmt->bindParam(':id', $this->id);

            if($stmt->execute()) {
                $this->updateTotalPrice();
                $mydata = $this->reGenerateVoucher();
                $this->sendVoucher($mydata['email'], $mydata['name'], $mydata['path'], $this->code, 'update');
                echo json_encode([
                    "success" => true,
                    "msg" => "Uspešno ste izmenili broj mesta u rezervaciji na {$this->newPlaces}.",
                    "mesta" => $this->places,
                    "NovaMesta" => $this->newPlaces, 
                    "Dostupno" => $this->availability($this->date)
                ], JSON_PRETTY_PRINT);
            } else {
                http_response_code(422);
                echo json_encode(["places" => 'Trenutno nije moguće izmeniti ovu rezervaciju!']);
            }    
        } else {
            http_response_code(422);
            echo json_encode([
                "error" => "Nema dovoljno slobodnih mesta da biste izvršili izmenu!",
                "available" => $this->availability($this->date) + $this->places
            ], JSON_PRETTY_PRINT);
        }
    }

    // RESCHEDULE outbound

    public function outbound($voucher) 
    {
        if($this->isDeparture($this->newDate)) {
            if($this->isUnlocked($this->newDate)) {
                if($this->items->items[0]->places <= $this->availability($this->newDate)) {
                    $sql = "UPDATE order_items SET date = :date WHERE id = :id";
                    $stmt = $this->db->prepare($sql);
                    
                    $this->id = htmlspecialchars(strip_tags($this->items->items[0]->id));
                    $this->newDate = htmlspecialchars(strip_tags($this->newDate));

                    $stmt->bindParam(':id', $this->items->items[0]->id);
                    $stmt->bindParam('date', $this->newDate);

                    if($stmt->execute()) {
                        $this->updateTotalPrice();
                        if($voucher) {
                            $mydata = $this->reGenerateVoucher();
                            $this->sendVoucher($mydata['email'], $mydata['name'], $mydata['path'], $this->code, 'update');
                        }   
                    } else {
                        http_response_code(422);
                        echo json_encode(['error' => "Nije moguće promeniti datum vaše vožnje na: $this->newDate. Molimo kontaktirajte našu podršku!"]);
                        exit();
                    }    
                } else {
                    http_response_code(422);
                    echo json_encode([
                        'error' => 'Nema dovoljno slobodnih mesta za izabrani datum.'
                    ], JSON_PRETTY_PRINT);
                    exit();
                }  
            } else {
                http_response_code(422);
                echo json_encode(["error" => "Odabrani datum je ili nepostojeći, ili je već prošao. Novi odabrani polazak mora biti najmanje 24 časa od ovog momenta!"]);
                exit(); 
            }
        } else {
            echo json_encode([
                'reschedule' => 'Nemamo polaske za odabrani datum.'
            ], JSON_PRETTY_PRINT);
            exit();
        } 
    }

    // RESCHEDULE inbound

    public function inbound($voucher)
    {
        if($this->isDeparture($this->newDateIn)) {
            if($this->isUnlocked($this->newDateIn)) {
                if($this->items->items[1]->places <= $this->availability($this->newDateIn)) {
                    $sql = "UPDATE order_items SET date = :date WHERE id = :id";
                    $stmt = $this->db->prepare($sql);
                    
                    $this->id = htmlspecialchars(strip_tags($this->items->items[1]->id));
                    $this->newDate = htmlspecialchars(strip_tags($this->newDateIn));

                    $stmt->bindParam(':id', $this->items->items[1]->id);
                    $stmt->bindParam('date', $this->newDateIn);

                    if($stmt->execute()) {
                        $this->updateTotalPrice();
                        if($voucher) {
                            $mydata = $this->reGenerateVoucher();
                            $this->sendVoucher($mydata['email'], $mydata['name'], $mydata['path'], $this->code, 'update');
                        }   
                    } else {
                        http_response_code(422);
                        echo json_encode(['error' => "Nije moguće promeniti datum vaše vožnje na: $this->newDateIn. Molimo kontaktirajte našu podršku!"]);
                        exit();
                    }    
                } else {
                    http_response_code(422);
                    echo json_encode([
                        'error' => 'Nema dovoljno slobodnih mesta za izabrani datum.'
                    ], JSON_PRETTY_PRINT);
                    exit();
                }  
            } else {
                http_response_code(422);
                echo json_encode(["error" => "Odabrani datum je ili nepostojeći, ili je već prošao. Novi odabrani polazak mora biti najmanje 24 časa od ovog momenta!"]); 
                exit();
            }
        } else {
            http_response_code(422);
            echo json_encode([
                'error' => 'Nemamo polaske za odabrani datum.'
            ], JSON_PRETTY_PRINT);
            exit();
        } 
    }

    // RESCHEDULE date all bounds
    public function reschedule() 
    {
        //$this->getFromDB($this->id);
        try {
            if(isset($this->newDate) && !empty($this->newDate) && isset($this->newDateIn) && !empty($this->newDateIn)) {
                $this->db->beginTransaction();
                $this->outbound(false);
                $this->inbound(false);
                $this->db->commit();
                echo json_encode([
                    'success' => true,
                    'msg' => 'Uspešno ste izmenili datume vaših vožnji'
                ], JSON_PRETTY_PRINT);
                exit();
            } elseif(isset($this->newDate) && !empty($this->newDate) && empty($this->newDateIn)) {
                $this->outbound(false);
                echo json_encode([
                    'success' => true,
                    'msg' => "Uspešno ste promenili datum vaše vožnje na: $this->newDate"
                ], JSON_PRETTY_PRINT);
                exit();
            } elseif(isset($this->newDateIn) && !empty($this->newDateIn) && empty($this->newDate)) {
                $this->inbound(false);
                echo json_encode([
                    'success' => true,
                    'msg' => "Uspešno ste promenili datum vaše vožnje na: $this->newDateIn"
                ], JSON_PRETTY_PRINT);
                exit();
            } else {
                http_response_code(422);
                echo json_encode(['error' => 'Unesite bar jedan datum'], JSON_PRETTY_PRINT);
            }
        } catch (Exception $e) {
            if($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            http_response_code(422);
            json_encode([
                'error' => 'Nije moguće izvršiti promenu datuma',
                'msg' => $e
            ], JSON_PRETTY_PRINT);
        }
        
        
    }

    // UPDATE PLACES and DATE
    public function rescheduleAndPlaces() 
    {
        $this->getFromDB($this->id);
        if(isset($this->newDate) && !empty($this->newDate) && isset($this->newPlaces) && !empty($this->newPlaces)) {
            if($this->isDeparture($this->newDate)) {
                if($this->newPlaces <= $this->availability($this->newDate)) {
                    $sql = "UPDATE orders SET places = :places, total = :total, date = :date WHERE id = :id";
                    $stmt = $this->db->prepare($sql);
                    
                    $this->id = htmlspecialchars(strip_tags($this->id));
                    $this->places = htmlspecialchars(strip_tags($this->places));
                    $this->price = htmlspecialchars(strip_tags($this->price));
                    $this->newPlaces = htmlspecialchars(strip_tags($this->newPlaces));
                    $new_total = $this->totalPrice($this->db, $this->tour_id, $this->newPlaces);
                    //($this->price / $this->places) * $this->newPlaces;
                    $this->newDate = htmlspecialchars(strip_tags($this->newDate));

                    $stmt->bindParam(':id', $this->id);
                    $stmt->bindParam(':places', $this->newPlaces);
                    $stmt->bindParam(':total', $new_total);
                    $stmt->bindParam('date', $this->newDate);

                    $formated = date_create($this->newDate);
                    $d = date("d.m.Y", date_timestamp_get($formated));

                    if($stmt->execute()) {
                        $mydata = $this->reGenerateVoucher();
                        $this->sendVoucher($mydata['email'], $mydata['name'], $mydata['path'], $this->code, 'update');
                        echo json_encode(['reschedule' => "Uspešno ste promenili datum vaše vožnje na: $d, a broj mesta na: $this->newPlaces"]);
                    } else
                        echo json_encode(['reschedule' => "Nije moguće promeniti datum vaše vožnje na: $d. Molimo kontaktirajte našu podršku!"]);
                } else {
                    echo json_encode([
                        'reschedule' => 'Nema dovoljno slobodnih mesta za izabrani datum.',
                        'mesta' => $this->newPlaces,
                        'dostupno' => $this->availability($this->newDate)
                    ], JSON_PRETTY_PRINT);
                }  
            } else {
                echo json_encode([
                    'reschedule' => 'Odabrani datum nije dostupan.'
                ], JSON_PRETTY_PRINT);
            }   
        }
        
    }
    /* Old ASSIGN:
    public function assignDriver()
    {
        $ordersArr = []; 
        foreach($this->selected as $ord) {
            $sql = "UPDATE orders SET driver_id = :driver WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $this->driver->id = htmlspecialchars(strip_tags($this->driver->id), ENT_QUOTES);
            $this->id = htmlspecialchars(strip_tags($ord->id), ENT_QUOTES);
            $this->user_id = htmlspecialchars(strip_tags($ord->user_id), ENT_QUOTES);
            $this->tour_id = htmlspecialchars(strip_tags($ord->tour_id), ENT_QUOTES);
            $stmt->bindParam(':driver', $this->driver->id);
            $stmt->bindParam(':id', $this->id);
            array_push($ordersArr, (string)$ord->id);
            
            try {
                if($stmt->execute()) {
                    $updated = $this->reGenerateVoucher();
                    $this->sendVoucher($ord->email, $ord->user, $updated['path'], $updated['code'], 'resend');
                    //echo json_encode(["driver_assign' => 'Uspešno ste dodelili vožnje vozaču {$this->driver->name}"], JSON_PRETTY_PRINT);
                }
            } catch (PDOException $e) {
                echo json_encode([
                    "driver_assign" => 'Došlo je do greške pri konekciji na bazu!',
                    "msg" => $e->getMessage()
                ], JSON_PRETTY_PRINT);
                
            } 
        }
        
        $ord_set = implode(",", $ordersArr);
        $now = time() + $this->driver->id;
        $generated = (string)$now . "KP";
        $new_code = substr($generated, -9);
        $dep_date = $this->selected[0]->date . " " . $this->selected[0]->pickuptime;
        
        $pathD = $this->generateDeparture($this->selected, $new_code, $dep_date);

        $this->departureCreate($this->driver->id, $ord_set, $new_code, $pathD['path'], $dep_date);
        $this->sendOrdersToDriver($this->driver->name, $new_code, $pathD['path'], $this->driver->email);
        
    } */

    // REFACTOR ASSIGN DRIVER

    public function assignDriverTo()
    {
        $now = time() + $this->driver->id;
        $generated = (string)$now . "KP";
        $new_code = substr($generated, -9);
        $dep_date = $this->selected[0]->date;
        //$this->tour_id = $this->selected[0]->tour_id;
        
        $pathD = $this->generateDeparture($this->selected, $new_code, $dep_date);

        $dep_id = $this->departureCreate($this->driver->id, $this->tour_id, $new_code, $pathD['path'], $dep_date);
        $this->sendOrdersToDriver($this->driver->name, $new_code, $pathD['path'], $this->driver->email);

        foreach($this->selected as $ord) {
            $sql = "UPDATE order_items SET driver_id = :driver, dep_id = :dep_id WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $this->driver->id = htmlspecialchars(strip_tags($this->driver->id), ENT_QUOTES);
            $this->id = htmlspecialchars(strip_tags($ord->order_item_id), ENT_QUOTES);
            $this->user_id = htmlspecialchars(strip_tags($ord->user->id), ENT_QUOTES);
            //$this->tour_id = htmlspecialchars(strip_tags($ord->tour_id), ENT_QUOTES);
            $stmt->bindParam(':driver', $this->driver->id);
            $stmt->bindParam(':dep_id', $dep_id);
            $stmt->bindParam(':id', $this->id);
            
            try {
                if($stmt->execute()) {
                    //$updated = $this->reGenerateVoucher();
                    //$this->sendVoucher($ord->email, $ord->user, $updated['path'], $updated['code'], 'resend');
                    echo json_encode([
                        "success" => true,
                        "msg" => "Uspešno ste dodelili vožnje vozaču {$this->driver->name}"
                    ], JSON_PRETTY_PRINT);
                }
            } catch (PDOException $e) {
                http_response_code(500);
                echo json_encode([
                    "error" => 'Došlo je do greške pri konekciji na bazu!',
                    "msg" => $e->getMessage()
                ], JSON_PRETTY_PRINT);
                
            } 
        }
        
    }

    // END
    public function departureCreate($driver_id, $tour_id, $code, $path, $date) 
    {
        $sql = "INSERT INTO departures SET driver_id = :driver_id, tour_id = :tour_id, code = :code, file_path = :path, date = :date";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':driver_id', $driver_id);
        $stmt->bindParam(':tour_id', $tour_id);
        //$stmt->bindParam(':orders', $orders);
        $stmt->bindParam(':code', $code);
        $stmt->bindParam(':path', $path);
        $stmt->bindParam(':date', $date);

        try {
            if($stmt->execute()) {
                echo json_encode(['departure' => 'Uspešno ste kreirali polazak!'], JSON_PRETTY_PRINT);
                return $this->db->lastInsertId();
            } 
        }catch (PDOException $e) {
            echo json_encode(['departure' => 'Došlo je do greške pri konekciji na bazu!', 'msg' => $e->getMessage()], JSON_PRETTY_PRINT);
        }
    }

    //------------------------------- FUNCTIONS OF DELETE METHOD --------------------------------//

    // DELETE order
    public function delete()
    {
        $this->getFromDB($this->id);
        $sql = "UPDATE order_items SET deleted = 1 WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $this->id = htmlspecialchars(strip_tags($this->id));
        $stmt->bindParam(":id", $this->id);
        //$id_sql = "SELECT dep_id FROM order_items WHERE id = {$this->id}";
        if($stmt->execute()) { /*
            $res = $this->db->query($id_sql);
            $row = $res->fetch(PDO::FETCH_OBJ);
            $d_id = $row->dep_id;
            if($d_id != NULL) {
                $sum = 0;
                $dep_sql = "SELECT deleted FROM order_items WHERE dep_id = {$d_id}";
                $dels = $this->db->query($dep_sql);
                if($dels->rowCount() > 0) {
                    while($row = $dels->fetch(PDO::FETCH_OBJ)) {
                        if($row->deleted == 0) {
                            $sum++;
                        }
                    }
                    if($sum < 1) {
                        $del_sql = "UPDATE departures SET deleted = 1 WHERE id = :id";
                        $stmt = $this->db->prepare($del_sql);
                        $stmt->bindParam(':id', $d_id);
                        $stmt->execute();
                    }
                }
            } */
            // Check if the departure has at leas one active item:

            $depSql = "UPDATE departures SET deleted = 1 WHERE id = :dep_id
                        AND NOT EXISTS ( SELECT 1 FROM order_items 
                        WHERE dep_id = departures.id AND deleted = 0)
            ";
            $stmt = $this->db->prepare($depSql);
            $stmt->bindParam(':dep_id', $this->dep_id);
            $stmt->execute();

            // Check if the order has at leas one active item:
            
            $ordSql = "UPDATE orders SET deleted = 1 WHERE id = :order_id
                        AND NOT EXISTS ( SELECT 1 FROM order_items 
                        WHERE order_id = orders.id AND deleted = 0)
            ";
            $stmt = $this->db->prepare($ordSql);
            $stmt->bindParam(':order_id', $this->order_id);
            $stmt->execute();

            echo json_encode([
                "success" => true,
                "msg" => 'Uspešno ste obrisali vožnju!'
            ], JSON_PRETTY_PRINT);
        } else {
            http_response_code(422);
            echo json_encode(["msg" => 'Trenutno nije moguće obrisati ovu rezervaciju!']);
        }
    }

    // RESTORE order - only Admin

    public function restore() 
    {   
        $order = $this->getFromDB($this->id);

        if($order) {
            if($this->places <= $this->availability($this->date) && $this->isUnlocked($this->date)) {
                $sql = "UPDATE orders SET deleted = 0, WHERE id = :id";
                $stmt = $this->db->prepare($sql);
                $this->id = htmlspecialchars(strip_tags($this->id));

                //$stmt->bindParam(":path", $mydata['path']);
                $stmt->bindParam(":id", $this->id);
                
                if($stmt->execute()) {
                    $ordSql = "UPDATE orders SET deleted = 0 WHERE id = :order_id
                        AND EXISTS ( SELECT 1 FROM order_items 
                        WHERE order_id = orders.id AND deleted = 0)
                    ";
                    $stmt = $this->db->prepare($ordSql);
                    $stmt->bindParam(':order_id', $this->order_id);
                    $stmt->execute();

                    $mydata = $this->reGenerateVoucher();
                    $this->sendVoucher($mydata['email'], $mydata['name'], $mydata['path'], $this->code, 'update');

                    echo json_encode([
                        'success' => true,
                        "msg" => 'Uspešno ste aktivirali rezervaciju!'
                    ], JSON_PRETTY_PRINT);
                } else {
                    http_response_code(422);
                    echo json_encode(["error" => 'Trenutno nije moguće aktivirati ovu rezervaciju!']);
                } 
            } else {
                http_response_code(422);
                echo json_encode([
                    'success' => false,
                    "error" => 'Nema više slobodnih mesta za datum ove rezervacije, te je ne možemo aktivirati.'
                ]);
            }
        } else
            echo json_encode(["msg" => 'Ova rezervacija je izbrisana iz naše baze, pokušajte da kreirate novu.']);
    }
}


/**
 
    PUT
    
    "user": {
        "id": 10,
        "email": "pininfarina164@gmail.com"
    },
    "orders": {
        "update": {
            "order_id": 83,
            "tour_id": 1,
            "user_id": 10,
            "places": 2,
            "add_from": "Gavrila Principa 9",
            "add_to": "Primorska 18",
            "date": "2025-07-14",
            "price": null
        },
        "address": {
            "add_from": "Jevrejska 9",
            "add_to": "Mornarska 18"
        },
        "new_places": 3,
        "reschedule": null
    }

    DELETE

    "orders": {
        "delete": {
            "order_id": 83
        }
    }

    "orders": {
        "restore": {
            "order_id": 83
        }
    }

 */
?>