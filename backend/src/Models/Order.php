<?php

namespace Models;

use PDO;
use PDOException;
use Rules\Validator;
use Dompdf\Dompdf;
use Dompdf\Options;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;
use tidy;

class Order {
    public $id;
    public $tour_id;
    public $user_id;
    public $driver_id;
    public $places;
    public $add_from;
    public $add_to;
    public $date;
    public $price;
    public $code;
    public $voucher;
    public $deleted;
    public $new_add_from;
    public $new_add_to;
    public $newDate;
    public $newPlaces;
    public $driver;
    public $selected;

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
        $select = "SELECT user_id from orders WHERE id = '$this->id'";
        $res = $this->db->query($select);
        $num = $res->rowCount();

        if($num > 0) {
            $row = $res->fetch(PDO::FETCH_OBJ);

            if($_SESSION['user_id'] == $row->user_id) {
                return true;
            } else {
                return false;
            }           
        } else
        return false;
    }

    // How many places we have available for the requested date:
    public function availability($date) {
        $sql = "SELECT orders.places, tours.seats from orders 
                INNER JOIN tours on tours.id = orders.tour_id
                WHERE orders.date = '$date'
                and orders.tour_id = '$this->tour_id' and orders.deleted = 0
        ";
        $res = $this->db->query($sql);
        $num = $res->rowCount();
        $occupated = 0;
        $seats = 0;

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
        $current = "SELECT places, date, total, tour_id, time FROM orders 
        INNER JOIN tours on orders.tour_id = tours.id
        WHERE orders.id = '$this->id'";
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
            $this->price = $row->total;
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
            $sqlID = "SELECT tour_id from orders WHERE id = '$this->id'";
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

    public function generateVoucher($new_code, $places, $add_from, $add_to, $date, $price): array 
    {
        $this->user->id = $this->user_id;
        $this->tour->id = $this->tour_id;

        $owner = $this->user->getByID();
                    
        $tourObj = $this->tour->getByID();
                    
        $options = new Options();
        $options->setChroot("src/assets/img");
        $pdf = new Dompdf($options);
        $pdf->setPaper("A4", "Portrait");

        $formated = date_create($date);
        $d = date("d.m.Y", date_timestamp_get($formated));

        $html = file_get_contents("src/template.html");
        $html = str_replace("{{ order }}", $new_code, $html);

        $html = str_replace("{{ name }}", $owner[0]['name'], $html);
        $html = str_replace("{{ places }}", $places, $html);
        $html = str_replace("{{ address }}", $add_from, $html);
        $html = str_replace("{{ city }}", $tourObj[0]['from_city'], $html);
        $html = str_replace("{{ address_to }}", $add_to, $html);
        $html = str_replace("{{ city_to }}", $tourObj[0]['to_city'], $html);
        $html = str_replace("{{ date }}", $d, $html);
        $html = str_replace("{{ time }}", $tourObj[0]['time'], $html);
        $html = str_replace("{{ price }}", $price, $html);
        $html = str_replace("{{ year }}", date("Y"), $html);

        $pdf->loadHtml($html);

        $pdf->render(); // Obavezno!!!
        $pdf->addInfo("Title", "Kombiprevoz - rezervacija: ". $new_code);
        //$pdf->stream("Rezervacija.pdf");
        $file_path = "src/assets/pdfs/". $new_code . ".pdf";
                    
        $output = $pdf->output();
        file_put_contents($file_path, $output);
        return [
            'email' => $owner[0]['email'],
            'name' => $owner[0]['name'],
            'path' => $file_path
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

        if($myOrder != NULL) {
            $arr = explode(" ", $myOrder->driver);
            $myDriver = $arr[0];
        }

        if($myOrder != NULL) {
           $formated = date_create($myOrder->date); 
        } else 
        $formated = date_create($this->date); 
        $d = date("d.m.Y", date_timestamp_get($formated));
        if($myOrder != NULL) {
            $html = file_get_contents("src/updated.html");
        } else $html = file_get_contents("src/template.html");
        if($myOrder != NULL) {
            $html = str_replace("{{ order }}", $myOrder->code, $html);
        } else
        $html = str_replace("{{ order }}", $this->code, $html);

        $html = str_replace("{{ name }}", $owner[0]['name'], $html);
        if($myOrder != NULL) {
            $html = str_replace("{{ places }}", $myOrder->places, $html);
        } else
        $html = str_replace("{{ places }}", $this->places, $html);
        if($myOrder != NULL) {
            $html = str_replace("{{ address }}", $myOrder->pickup, $html);
        } else
        $html = str_replace("{{ address }}", $this->add_from, $html);
        $html = str_replace("{{ city }}", $tourObj[0]['from_city'], $html);
        if($myOrder != NULL) {
            $html = str_replace("{{ address_to }}", $myOrder->dropoff, $html);
        } else
        $html = str_replace("{{ address_to }}", $this->add_to, $html);
        $html = str_replace("{{ city_to }}", $tourObj[0]['to_city'], $html);
        $html = str_replace("{{ date }}", $d, $html);
        $html = str_replace("{{ time }}", $tourObj[0]['time'], $html);
        if($myOrder != NULL) {
            $html = str_replace("{{ price }}", $myOrder->price, $html);
        } else
        $html = str_replace("{{ price }}", $this->price, $html);
        $html = str_replace("{{ year }}", date("Y"), $html);
        if($myOrder != NULL) {
            $html = str_replace("{{ driver }}", $myDriver, $html);
            $html = str_replace("{{ drphone }}", $myOrder->dr_phone, $html);
            $html = str_replace("{{ drmail }}", $myOrder->dr_email, $html);
        }

        $pdf->loadHtml($html);

        $pdf->render();
        if($myOrder != NULL) {
            $pdf->addInfo("Title", "Kombiprevoz - rezervacija: ". $myOrder->code);
        } else
        $pdf->addInfo("Title", "Kombiprevoz - rezervacija: ". $this->code);
        if($myOrder != NULL) {
            $file_path = $myOrder->voucher;
        } else
        $file_path = $this->voucher;
                    
        $output = $pdf->output();
        file_put_contents($file_path, $output);
        if($myOrder != NULL) {
            return [
                'email' => $owner[0]['email'],
                'name' => $owner[0]['name'],
                'path' => $file_path,
                'code' => $myOrder->code,
                'driver' => $myDriver,
                'driver_phone' => $myOrder->dr_phone,
                'driver_email' => $myOrder->dr_email
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
            $template .= Validator::mailerDriverTemplate($pax->code, $pax->user, $pax->places, $pax->pickup, $pax->from_city, $pax->dropoff, $pax->to_city, $pax->date, $pax->pickuptime, $pax->price, $pax->phone);
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

    //------------------------------- FUNCTIONS OF GET METHOD --------------------------------//

    public function getAll() 
    {
        $sql = "SELECT orders.id, orders.tour_id, orders.user_id, orders.places, tours.from_city, 
                orders.add_from as pickup, tours.to_city, orders.add_to as dropoff,
                orders.date, tours.time as pickuptime, tours.duration,
                orders.total as price, orders.code, orders.file_path as voucher, users.name as user, users.email, users.phone
                from orders 
                INNER JOIN tours on orders.tour_id = tours.id
                INNER JOIN users on orders.user_id = users.id
                WHERE orders.deleted = 0 order by orders.date"
        ;
        $res = $this->db->query($sql);
        $num = $res->rowCount();

        if($num > 0) {
            $orders = [];
            while($row = $res->fetch(PDO::FETCH_OBJ)) {
                array_push($orders, $row);
            }
            echo json_encode(['orders' => $orders]);
        } else {
            echo json_encode(['msg' => 'Nema rezervisanih vožnji']);
            exit();
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
        $sql = "SELECT orders.id, orders.tour_id, orders.user_id, orders.places, tours.from_city, 
                orders.add_from as pickup, tours.to_city, orders.add_to as dropoff,
                orders.date, tours.time as pickuptime, tours.duration,
                orders.total as price, orders.code, orders.file_path as voucher, users.name as user, users.email, users.phone
                from orders 
                INNER JOIN tours on orders.tour_id = tours.id
                INNER JOIN users on orders.user_id = users.id
                WHERE orders.user_id = '$this->user_id' AND orders.deleted = 0"
        ;
        $res = $this->db->query($sql);
        $num = $res->rowCount();

        if($num > 0) {
            $orders = [];
            while($row = $res->fetch(PDO::FETCH_OBJ)) {
                array_push($orders, $row);
            }
            echo json_encode(['orders' => $orders]);
        } else echo json_encode(['order' => 'Nema rezervacija od ovog korisnika.'], JSON_PRETTY_PRINT);
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

    public function getFromDB($id) 
    {
        $sql = "SELECT * FROM orders WHERE id = :id";
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
                    $this->price = $order->total;
                    $this->code = $order->code;
                    $this->voucher = $order->file_path;
                    $this->driver_id = $order->driver_id;
                    
                    return $order; 
                } 
                else return null;
            }
        } catch (PDOException $e) {
            echo json_encode(['msg' => $e->getMessage()]);
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
        $sql = "SELECT orders.id, orders.tour_id, orders.driver_id, orders.places, tours.from_city, 
                orders.add_from as pickup, tours.to_city, orders.add_to as dropoff,
                orders.date, tours.time as pickuptime, tours.duration,
                orders.total as price, orders.code, orders.file_path as voucher, 
                users.name as driver, users.email as dr_email, users.phone as dr_phone
                from orders 
                INNER JOIN tours on orders.tour_id = tours.id
                INNER JOIN users on orders.driver_id = users.id
                WHERE orders.id = '$this->id' AND orders.deleted = 0"
        ;
        $res = $this->db->query($sql);
        $order = $res->fetch(PDO::FETCH_OBJ);
        if($order) {
            echo json_encode(['msg_drOfT' => $order]);
            return $order;
        } else {
            $this->getFromDB($this->id);
            echo json_encode(['msg_drIsNull' => $order]);
            return null;
        } 
    }

    //------------------------------- FUNCTIONS OF POST METHOD --------------------------------//

    public function create() 
    {
        if($this->places <= $this->availability($this->date) && $this->isDeparture($this->date) && $this->isUnlocked($this->date)) {
            $sql = "INSERT INTO orders SET
                    tour_id = :tour_id, user_id = :user_id, places = :places,
                    add_from = :add_from, add_to = :add_to, date = :date, total = :price, 
                    code = :code, file_path = :pdf
            ";
            $stmt = $this->db->prepare($sql);

            $now = time() + $this->user_id;
            $generated = (string)$now . "KP";
            $new_code = substr($generated, -9);

            $this->tour_id = htmlspecialchars(strip_tags($this->tour_id));
            $this->user_id = htmlspecialchars(strip_tags($this->user_id));
            $this->places = htmlspecialchars(strip_tags($this->places));
            $this->add_from = htmlspecialchars(strip_tags($this->add_from));
            $this->add_to = htmlspecialchars(strip_tags($this->add_to));
            $this->date = htmlspecialchars(strip_tags($this->date));
            $this->price = $this->totalPrice($this->db, $this->tour_id, $this->places);
            $this->price = htmlspecialchars(strip_tags($this->price));

            if($this->price != null) {
                $stmt->bindParam(':tour_id', $this->tour_id);
                $stmt->bindParam(':user_id', $this->user_id);
                $stmt->bindParam(':places', $this->places);
                $stmt->bindParam(':add_from', $this->add_from);
                $stmt->bindParam(':add_to', $this->add_to);
                $stmt->bindParam(':date', $this->date);
                $stmt->bindParam(':price', $this->price);
                $stmt->bindParam(':code', $new_code);
                // voucher
                $mydata = $this->generateVoucher($new_code, $this->places, $this->add_from, $this->add_to, $this->date, $this->price);
                //
                $stmt->bindParam(':pdf', $mydata['path']);

                if($stmt->execute()) {
                    // Mail
                    $this->sendVoucher($mydata['email'], $mydata['name'], $mydata['path'], $new_code, 'create');

                    echo json_encode(['msg' => "Uspešno ste rezervisali vožnju. Vaš broj rezervacije je: {$new_code}"], JSON_PRETTY_PRINT);
                }
                else echo json_encode(['msg' => 'Trenutno nije moguće rezervisati ovu vožnju.'], JSON_PRETTY_PRINT);
            } else echo json_encode(['msg' => 'Trenutno nije moguće rezervisati ovu vožnju. 
                                    Nolimo Vas da se obratite našem centru za podršku!'], JSON_PRETTY_PRINT);
        } else
        echo json_encode(['msg' => 'Žao nam je, ali nema više slobodnih mesta za ovu vožnju.']);
    }

    //------------------------------- FUNCTIONS OF PUT METHOD --------------------------------// 

    public function updateAddress()
    {
        $this->getFromDB($this->id);
        $sql = "UPDATE orders SET add_from = :add_from, add_to = :add_to
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
            if($stmt->execute()) {
                if(empty($this->newDate) && empty($this->newPlaces)) {
                    $mydata = $this->generateVoucher($this->code, $this->places, $this->new_add_from, $this->new_add_to, $this->date, $this->price);
                    $this->sendVoucher($mydata['email'], $mydata['name'], $mydata['path'], $this->code, 'update');
                }
                echo json_encode(["address" => 'Uspešno ste izmenili adresu/adrese rezervacije!'], JSON_PRETTY_PRINT);
            } else
            echo json_encode(["address" => 'Trenutno nije moguće izmeniti ovu rezervaciju!']);
        } else
            echo json_encode(["address" => 'Molimo Vas da unesete validne adrese!']);
    }

    // Update ONLY number of places:
    public function updatePlaces() 
    {
        $this->getFromDB($this->id);
        if($this->newPlaces - $this->places <= $this->availability($this->date)) {
            $sql = "UPDATE orders SET places = :places, total = :total WHERE id = :id";
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
                $mydata = $this->generateVoucher($this->code, $this->newPlaces, $this->add_from, $this->add_to, $this->date, $new_total);
                $this->sendVoucher($mydata['email'], $mydata['name'], $mydata['path'], $this->code, 'update');
                echo json_encode([
                    "places" => "Uspešno ste izmenili broj mesta u rezervaciji na {$this->newPlaces}.",
                    "mesta" => $this->places,
                    "NovaMesta" => $this->newPlaces, 
                    "Dostupno" => $this->availability($this->date)
                ], JSON_PRETTY_PRINT);
            } else
                echo json_encode(["places" => 'Trenutno nije moguće izmeniti ovu rezervaciju!']);
        } else {
            echo json_encode([
                "places" => "Nema dovoljno slobodnih mesta da biste izvršili izmenu!",
                "available" => $this->availability($this->date) + $this->places
            ], JSON_PRETTY_PRINT);
        }
    }

    // RESCHEDULE date
    public function reschedule() 
    {
        $this->getFromDB($this->id);
        if(isset($this->newDate) && !empty($this->newDate)) {
            if($this->isDeparture($this->newDate)) {
                if($this->places <= $this->availability($this->newDate)) {
                    $sql = "UPDATE orders SET date = :date WHERE id = :id";
                    $stmt = $this->db->prepare($sql);
                    
                    $this->id = htmlspecialchars(strip_tags($this->id));
                    $this->newDate = htmlspecialchars(strip_tags($this->newDate));

                    $stmt->bindParam(':id', $this->id);
                    $stmt->bindParam('date', $this->newDate);

                    if($stmt->execute()) {
                        $mydata = $this->generateVoucher($this->code, $this->places, $this->add_from, $this->add_to, $this->newDate, $this->price);
                        $this->sendVoucher($mydata['email'], $mydata['name'], $mydata['path'], $this->code, 'update');
                        echo json_encode(['reschedule' => "Uspešno ste promenili datum vaše vožnje na: $this->newDate"]);
                    } else
                        echo json_encode(['reschedule' => "Nije moguće promeniti datum vaše vožnje na: $this->newDate. Molimo kontaktirajte našu podršku!"]);
                } else {
                    echo json_encode([
                        'reschedule' => 'Nema dovoljno slobodnih mesta za izabrani datum.',
                        'mesta' => $this->places,
                        'dostupno' => $this->availability($this->newDate)
                    ], JSON_PRETTY_PRINT);
                }  
            } else {
                echo json_encode([
                    'reschedule' => 'Nemamo polaske za odabrani datum.'
                ], JSON_PRETTY_PRINT);
            }   
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
                        $mydata = $this->generateVoucher($this->code, $this->newPlaces, $this->add_from, $this->add_to, $this->newDate, $new_total);
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
        $this->tour_id = $this->selected[0]->tour_id;
        
        $pathD = $this->generateDeparture($this->selected, $new_code, $dep_date);

        $dep_id = $this->departureCreate($this->driver->id, $this->tour_id, $new_code, $pathD['path'], $dep_date);
        $this->sendOrdersToDriver($this->driver->name, $new_code, $pathD['path'], $this->driver->email);

        foreach($this->selected as $ord) {
            $sql = "UPDATE orders SET driver_id = :driver, dep_id = :dep_id WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $this->driver->id = htmlspecialchars(strip_tags($this->driver->id), ENT_QUOTES);
            $this->id = htmlspecialchars(strip_tags($ord->id), ENT_QUOTES);
            $this->user_id = htmlspecialchars(strip_tags($ord->user_id), ENT_QUOTES);
            $this->tour_id = htmlspecialchars(strip_tags($ord->tour_id), ENT_QUOTES);
            $stmt->bindParam(':driver', $this->driver->id);
            $stmt->bindParam(':dep_id', $dep_id);
            $stmt->bindParam(':id', $this->id);
            
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
        $sql = "UPDATE orders SET deleted = 1, file_path = NULL WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $this->id = htmlspecialchars(strip_tags($this->id));
        $stmt->bindParam(":id", $this->id);
        $id_sql = "SELECT dep_id FROM orders WHERE id = {$this->id}";
        if($stmt->execute()) {
            $res = $this->db->query($id_sql);
            $row = $res->fetch(PDO::FETCH_OBJ);
            $d_id = $row->dep_id;
            if($d_id != NULL) {
                $sum = 0;
                $dep_sql = "SELECT deleted FROM orders WHERE dep_id = {$d_id}";
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
            } 
            

            echo json_encode(["msg" => 'Uspešno ste obrisali rezervaciju!'], JSON_PRETTY_PRINT);
        } else
        echo json_encode(["msg" => 'Trenutno nije moguće obrisati ovu rezervaciju!']);
    }

    // RESTORE order - only Admin

    public function restore() 
    {   
        $order = $this->getFromDB($this->id);

        if($order) {
            if($this->places <= $this->availability($this->date) && $this->isUnlocked($this->date)) {
                $mydata = $this->generateVoucher($this->code, $this->places, $this->add_from, $this->add_to, $this->date, $this->price);
                $sql = "UPDATE orders SET deleted = 0, file_path = :path WHERE id = :id";
                $stmt = $this->db->prepare($sql);
                $this->id = htmlspecialchars(strip_tags($this->id));

                $stmt->bindParam(":path", $mydata['path']);
                $stmt->bindParam(":id", $this->id);
                
                if($stmt->execute()) {
                    $this->sendVoucher($mydata['email'], $mydata['name'], $mydata['path'], $this->code, 'update');
                    echo json_encode(["msg" => 'Uspešno ste aktivirali rezervaciju!'], JSON_PRETTY_PRINT);
                } else
                echo json_encode(["msg" => 'Trenutno nije moguće aktivirati ovu rezervaciju!']);
            } else
                echo json_encode(["msg" => 'Nema više slobodnih mesta za datum ove rezervacije, te je ne možemo aktivirati.',
                    'mesta' => $this->places,
                    'dostupno' => $this->availability($this->date),
                    'datum' => $this->date,
                    'otključan' => $this->isUnlocked($this->date)
                ]);
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