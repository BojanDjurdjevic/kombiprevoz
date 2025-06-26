<?php

namespace Models;

use PDO;
use PDOException;
use Rules\Validator;
use Dompdf\Dompdf;
use Dompdf\Options;

class Order {
    public $id;
    public $tour_id;
    public $user_id;
    public $places;
    public $add_from;
    public $add_to;
    public $date;
    public $price;
    public $code;
    public $deleted;
    public $newDate;
    public $newPlaces;
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    // Checking if the USER is OWNER of the order
    public function findUserId() 
    {
        $select = "SELECT user_id from orders WHERE id = '$this->id' and user_id = '$this->user_id'";
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
            exit();
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

    // GET data:

    public function getAll() 
    {
        $sql = "SELECT orders.id, orders.tour_id, orders.places, tours.from_city, 
                orders.add_from as pickup, tours.to_city, orders.add_to as dropoff,
                orders.date, tours.time as pickuptime, tours.duration,
                orders.total as price, orders.code, users.name as user, users.email, users.phone
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
        $sql = "SELECT orders.id, orders.tour_id, orders.places, tours.from_city, 
                orders.add_from as pickup, tours.to_city, orders.add_to as dropoff,
                orders.date, tours.time as pickuptime, tours.duration,
                orders.total as price, orders.code, users.name as user, users.email, users.phone
                from orders 
                INNER JOIN tours on orders.tour_id = tours.id
                INNER JOIN users on orders.user_id = users.id
                WHERE orders.date = '$this->date' AND orders.deleted = 0"
        ;
        $res = $this->db->query($sql);
        $num = $res->rowCount();

        if($num > 0) {
            $orders = [];
            while($row = $res->fetch(PDO::FETCH_OBJ)) {
                array_push($orders, $row);
            }
            echo json_encode(['orders' => $orders]);
        } else
        echo json_encode(['msg' => 'Nema rezervisanih vožnji za traženi datum.']);
    }

    public function getAllByDateRange(? string $from, ? string $to)
    {
        $now = date("Y-m-d");
        $sql = "";
        if(isset($from) && isset($to)) {
            $sql = "SELECT orders.id, orders.tour_id, orders.places, tours.from_city, 
                orders.add_from as pickup, tours.to_city, orders.add_to as dropoff,
                orders.date, tours.time as pickuptime, tours.duration,
                orders.total as price, orders.code, users.name as user, users.email, users.phone
                from orders 
                INNER JOIN tours on orders.tour_id = tours.id
                INNER JOIN users on orders.user_id = users.id
                WHERE orders.date >= '$from' AND orders.date <= '$to' AND orders.deleted = 0
                ORDER BY orders.date"
            ;
        } elseif(isset($from) && !isset($to)) {
            $sql = "SELECT orders.id, orders.tour_id, orders.places, tours.from_city, 
                orders.add_from as pickup, tours.to_city, orders.add_to as dropoff,
                orders.date, tours.time as pickuptime, tours.duration,
                orders.total as price, orders.code, users.name as user, users.email, users.phone
                from orders 
                INNER JOIN tours on orders.tour_id = tours.id
                INNER JOIN users on orders.user_id = users.id
                WHERE orders.date >= '$from' AND orders.deleted = 0
                ORDER BY orders.date"
            ;
        } elseif(!isset($from) && isset($to)) {
            $sql = "SELECT orders.id, orders.tour_id, orders.places, tours.from_city, 
                orders.add_from as pickup, tours.to_city, orders.add_to as dropoff,
                orders.date, tours.time as pickuptime, tours.duration,
                orders.total as price, orders.code, users.name as user, users.email, users.phone
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
        $sql = "SELECT orders.id, orders.tour_id, orders.places, tours.from_city, 
                orders.add_from as pickup, tours.to_city, orders.add_to as dropoff,
                orders.date, tours.time as pickuptime, tours.duration,
                orders.total as price, orders.code, users.name as user, users.email, users.phone
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
        $sql = "SELECT orders.id, orders.tour_id, orders.places, tours.from_city, 
                orders.add_from as pickup, tours.to_city, orders.add_to as dropoff,
                orders.date, tours.time as pickuptime, tours.duration,
                orders.total as price, orders.code, orders.deleted,
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
                        koji sadrži 7 brojeva i 2 velika slova: xxxxxxxKP'], JSON_PRETTY_PRINT)
        ;

        /*
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
        echo json_encode(['msg' => 'Nema rezervisanih vožnji za odabrane destinacije.'], JSON_PRETTY_PRINT); */
    }

    public function getByTour() {
        $sql = "SELECT orders.id, orders.tour_id, orders.places, tours.from_city, 
                orders.add_from as pickup, tours.to_city, orders.add_to as dropoff,
                orders.date, tours.time as pickuptime, tours.duration,
                orders.total as price, orders.code, users.name as user, users.email, users.phone
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
        $sql = "SELECT orders.id, orders.tour_id, orders.places, tours.from_city, 
                orders.add_from as pickup, tours.to_city, orders.add_to as dropoff,
                orders.date, tours.time as pickuptime, tours.duration,
                orders.total as price, orders.code, users.name as user, users.email, users.phone
                from orders 
                INNER JOIN tours on orders.tour_id = tours.id
                INNER JOIN users on orders.user_id = users.id
                WHERE orders.tour_id = '$this->tour_id' AND orders.date = '$this->date' AND orders.deleted = 0"
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
        echo json_encode(['msg' => 'Nema rezervisanih vožnji za odabrane datume.'], JSON_PRETTY_PRINT);
    }

    // POST - create

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

                $user = new User($this->db);
                $user->id = $this->user_id;
                $tour = new Tour($this->db);
                $tour->id = $this->tour_id;

                $owner = $user->getByID();
                    
                $tourObj = $tour->getByID();
                    
                $options = new Options();
                $options->setChroot("src/assets/img");
                $pdf = new Dompdf($options);
                $pdf->setPaper("A4", "Portrait");

                $html = file_get_contents("src/template.html");
                $html = str_replace("{{ order }}", $new_code, $html);

                $html = str_replace("{{ name }}", $owner[0]['name'], $html);
                $html = str_replace("{{ places }}", $this->places, $html);
                $html = str_replace("{{ address }}", $this->add_from, $html);
                $html = str_replace("{{ city }}", $tourObj[0]['from_city'], $html);
                $html = str_replace("{{ address_to }}", $this->add_to, $html);
                $html = str_replace("{{ city_to }}", $tourObj[0]['to_city'], $html);
                $html = str_replace("{{ date }}", $this->date, $html);
                $html = str_replace("{{ time }}", $tourObj[0]['time'], $html);
                $html = str_replace("{{ price }}", $this->price, $html);
                $html = str_replace("{{ year }}", date("Y"), $html);

                $pdf->loadHtml($html);

                $pdf->render(); // Obavezno!!!
                $pdf->addInfo("Title", "Kombiprevoz - rezervacija: ". $new_code);
                //$pdf->stream("Rezervacija.pdf");
                $file_path = "src/assets/pdfs/". $new_code . ".pdf";
                    
                $output = $pdf->output();
                file_put_contents($file_path, $output);

                $stmt->bindParam(':pdf', $file_path);

                if($stmt->execute()) {
                    echo json_encode(['msg' => "Uspešno ste rezervisali vožnju. Vaš broj rezervacije je: {$new_code}"], JSON_PRETTY_PRINT);
                }
                else echo json_encode(['msg' => 'Trenutno nije moguće rezervisati ovu vožnju.'], JSON_PRETTY_PRINT);
            } else echo json_encode(['msg' => 'Trenutno nije moguće rezervisati ovu vožnju. 
                                    Nolimo Vas da se obratite našem centru za podršku!'], JSON_PRETTY_PRINT);
        } else
        echo json_encode(['msg' => 'Žao nam je, ali nema više slobodnih mesta za ovu vožnju.']);
    }

    // PUT - UPDATES 

    public function updateAddress()
    {
        $sql = "UPDATE orders SET add_from = :add_from, add_to = :add_to
                WHERE id = :id"
        ;
        $stmt = $this->db->prepare($sql);
        $this->id = htmlspecialchars(strip_tags($this->id));
        $this->add_from = htmlspecialchars(strip_tags($this->add_from));
        $this->add_to = htmlspecialchars(strip_tags($this->add_to));
        //$this->places = htmlspecialchars(strip_tags($this->places));
        $stmt->bindParam(":id", $this->id);
        $stmt->bindParam('add_from', $this->add_from);
        $stmt->bindParam('add_to', $this->add_to);
        //$stmt->bindParam('places', $this->places);
        if(!empty($this->add_from) && !empty($this->add_to)) {
            if($stmt->execute()) {
                echo json_encode(["address" => 'Uspešno ste izmenili adresu/adrese rezervacije!'], JSON_PRETTY_PRINT);
            } else
            echo json_encode(["address" => 'Trenutno nije moguće izmeniti ovu rezervaciju!']);
        } else
            echo json_encode(["address" => 'Molimo Vas da unesete validne adrese!']);
    }

    // Update ONLY number of places:
    public function updatePlaces() 
    {
        if($this->newPlaces - $this->places <= $this->availability($this->date)) {
            $sql = "UPDATE orders SET places = :places, total = :total WHERE id = :id";
            $stmt = $this->db->prepare($sql);

            $this->id = htmlspecialchars(strip_tags($this->id));
            $this->places = htmlspecialchars(strip_tags($this->places));
            $this->price = htmlspecialchars(strip_tags($this->price));
            $this->newPlaces = htmlspecialchars(strip_tags($this->newPlaces));
            $new_total = ($this->price / $this->places) * $this->newPlaces;

            $stmt->bindParam(':places', $this->newPlaces);
            $stmt->bindParam(':total', $new_total);
            $stmt->bindParam(':id', $this->id);

            if($stmt->execute()) {
                echo json_encode([
                    "places" => "Uspešno ste izmenili broj mesta u rezervaciji.",
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
        if(isset($this->newDate) && !empty($this->newDate) && isset($this->newPlaces) && !empty($this->newPlaces)) {
            if($this->isDeparture($this->newDate)) {
                if($this->newPlaces <= $this->availability($this->newDate)) {
                    $sql = "UPDATE orders SET places = :places, total = :total, date = :date WHERE id = :id";
                    $stmt = $this->db->prepare($sql);
                    
                    $this->id = htmlspecialchars(strip_tags($this->id));
                    $this->places = htmlspecialchars(strip_tags($this->places));
                    $this->price = htmlspecialchars(strip_tags($this->price));
                    $this->newPlaces = htmlspecialchars(strip_tags($this->newPlaces));
                    $new_total = ($this->price / $this->places) * $this->newPlaces;
                    $this->newDate = htmlspecialchars(strip_tags($this->newDate));

                    $stmt->bindParam(':id', $this->id);
                    $stmt->bindParam(':places', $this->newPlaces);
                    $stmt->bindParam(':total', $new_total);
                    $stmt->bindParam('date', $this->newDate);

                    $formated = date_create($this->newDate);
                    $d = date("d.m.Y", date_timestamp_get($formated));

                    if($stmt->execute()) {
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

    // DELETE

    public function delete()
    {
        $sql = "UPDATE orders SET deleted = 1 WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $this->id = htmlspecialchars(strip_tags($this->id));
        $stmt->bindParam(":id", $this->id);
        if($stmt->execute()) {
            echo json_encode(["msg" => 'Uspešno ste obrisali rezervaciju!'], JSON_PRETTY_PRINT);
        } else
        echo json_encode(["msg" => 'Trenutno nije moguće obrisati ovu rezervaciju!']);
    }

    // RESTORE

    public function restore() 
    {   
        $find = "SELECT date, places from orders WHERE id = '$this->id'";
        $found = $this->db->query($find);
        $num = $found->rowCount();

        if($num > 0) {
            $row = $found->fetch(PDO::FETCH_OBJ);

            $this->date = $row->date;
            $this->places = $row->places;
            if($this->places <= $this->availability($this->date) && $this->isUnlocked($this->date)) {
                $sql = "UPDATE orders SET deleted = 0 WHERE id = :id";
                $stmt = $this->db->prepare($sql);
                $this->id = htmlspecialchars(strip_tags($this->id));
                $stmt->bindParam(":id", $this->id);
                if($stmt->execute()) {
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

?>