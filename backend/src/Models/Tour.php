<?php

namespace Models;

use PDO;
use PDOException;

class Tour {
    public $id;
    public $from_city;
    public $to_city;
    public $departures;
    public $time;
    public $duration;
    public $price;
    public $seats;
    public $date;
    public $inbound;
    public $requestedSeats;
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    //------------------------------ BEFOR ACTION --------------------------//

    public function isDepartureDay($d): bool
    {
        $days = $d;
        
        $days = explode(",", $days);

        $depDays = [];

        foreach( $days as $day ) {
            array_push( $depDays, (int)$day);
        }

        $orderDate = date('w', strtotime($this->date));

        if(in_array($orderDate, $depDays)) {
            return true;
        } else {
            return false;
        }
    }

    public function getIdAndSeats() 
    {
        if(empty($this->id)) {
            $sql = "SELECT id, seats from tours 
                    WHERE from_city = :from_city AND to_city = :to_city and deleted = 0
            ";
            $stmt = $this->db->prepare($sql);

            $this->from_city = htmlspecialchars(strip_tags($this->from_city), ENT_QUOTES);
            $this->to_city = htmlspecialchars(strip_tags($this->to_city), ENT_QUOTES);

            $stmt->bindParam(':from_city', $this->from_city);
            $stmt->bindParam(':to_city', $this->to_city);
        } else {
            $sql = "SELECT id, seats from tours 
                WHERE id = :id AND deleted = 0
            ";
            $stmt = $this->db->prepare($sql);

            $this->id = htmlspecialchars(strip_tags($this->id), ENT_QUOTES);

            $stmt->bindParam(':id', $this->id);
        }
        try {
            if($stmt->execute()) {
                $tour = $stmt->fetch(PDO::FETCH_OBJ);

                if($tour) {
                    $this->id = $tour->id;
                    $this->seats = $tour->seats;
                    return [
                        'id' => $tour->id,
                        'seats' => $tour->seats 
                    ];
                }
            }
        } catch (PDOException $e) {
            echo json_encode([
                    'allowed' => 'Došlo je do greške pri konekciji na bazu!',
                    'msg' => $e->getMessage()
            ], JSON_PRETTY_PRINT);
        }
    }

    //------------------------------ GET -----------------------------//

    public function getAll() 
    {
        $sql = "SELECT * from tours WHERE deleted = 0";
        $res = $this->db->query($sql);
        $num = $res->rowCount();

        if($num > 0) {
            $tours = [];

            while($row = $res->fetch(PDO::FETCH_OBJ)) {
                array_push($tours, $row);
            }

            echo json_encode(['tours' => $tours]);
        } else
        echo json_encode(['msg' => "Nema dostupnih vožnji."]);
    } 

    public function getByID() 
    {
        $sql = "SELECT * FROM tours WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $this->id = htmlspecialchars(strip_tags($this->id));
        $stmt->bindParam(':id', $this->id);

        try {
            if($stmt->execute()) {
                $tour = [];
                $res = $stmt->fetch(PDO::FETCH_OBJ);
                array_push($tour, [
                    "id" => $res->id,
                    "from_city" => $res->from_city,
                    "to_city" => $res->to_city,
                    "departures" => $res->departures,
                    "time" => $res->time,
                    "duration" => $res->duration,
                    "price" => $res->price,
                    "seats" => $res->seats
                ]);

                if($tour) return $tour;
                else echo json_encode(['tour' => 'Nije pronađena nijedna vožnja!']);
            }
        } catch (PDOException $e) {
            echo json_encode([
                'tour' => 'Došlo je do greške!', 
                'msg' => $e->getMessage()    
            ]);
        }
    }

    public function getDays() {
        if(!empty($this->from_city) && !empty($this->to_city))
        $sql = "SELECT tours.departures from tours where from_city = '$this->from_city' and to_city = '$this->to_city' and deleted = 0";
        elseif(!empty($this->id))
        $sql = "SELECT tours.departures from tours where {$this->id} and deleted = 0";
        $res = $this->db->query($sql);
        $num = $res->rowCount();

        $tourDep = [];

        if($num > 0) {
            $row = $res->fetch(PDO::FETCH_OBJ);
            $arr = explode(',', $row->departures);

            foreach($arr as $day) {
                array_push($tourDep, (int)$day); 
            }

            //echo json_encode(["allowed"=> $tourDep], JSON_PRETTY_PRINT);
            return $tourDep;
        } else {
            echo json_encode(["msg"=> "Nema dostupnih vožnji prema zadatim parametrima."]);
            exit();
        }
    }

    public function fullyBooked($format)
    {
        $tour = $this->getIdAndSeats();
        
        $sql = "SELECT date, SUM(places) as totall FROM orders
                WHERE tour_id = :tour_id AND 
                date LIKE :format
                GROUP BY date
        ";
        $stmt = $this->db->prepare($sql);

        $this->id = htmlspecialchars(strip_tags($this->id), ENT_QUOTES);
        $format = htmlspecialchars(strip_tags($format), ENT_QUOTES);
        $stmt->bindParam(':tour_id', $this->id);
        $stmt->bindParam(':format', $format);

        try {
            if($stmt->execute()) {
                $fullyBooked = [];
                $availableDates = [];
                while ($row = $stmt->fetch(PDO::FETCH_OBJ)) {
                    if($row->totall >= $this->seats) {
                        array_push($fullyBooked, $row->date);
                    } else
                        array_push($availableDates, $row->date);
                }

                echo json_encode([
                    'fullyBooked' => $fullyBooked,
                    'availableD' => $availableDates,
                    'allowed' => $this->getDays()
                ], JSON_PRETTY_PRINT);
            }
        } catch (PDOException $e) {
            echo json_encode([
                'fullyBooked' => 'Došlo je do greške pri konekciji na bazu!',
                'msg' => $e->getMessage()
            ], JSON_PRETTY_PRINT);
        }
    }

    public function getBySearch() {
        if(!empty($this->from_city) && !empty($this->to_city) && !empty($this->date)) {
            $sql = "SELECT * from tours where from_city = '$this->from_city' 
            and to_city = '$this->to_city' and deleted = 0";

            $res = $this->db->query($sql);
            $num = $res->rowCount();

            if($num > 0) {
                $row = $res->fetch(PDO::FETCH_OBJ);
                if($this->isDepartureDay($row->departures)) {
                    $ordSql = "SELECT places from orders WHERE orders.tour_id = '$row->id' 
                    and orders.date = '$this->date'
                    ";
                    $ordRes = $this->db->query($ordSql);
                    if($ordRes->rowCount() > 0) {
                        $occupated = 0;
                        while($ordRow = $ordRes->fetch(PDO::FETCH_OBJ)) {
                            $occupated += $ordRow->places;
                        }
                        $available = $row->seats - $occupated;
                        if($available >= $this->requestedSeats) {
                            $dateTime = date_create($this->date . " " . $row->time);
                            $dur = "+". $row->duration . " " . "hours";
                            $arrival = date("H:i", strtotime($dur, date_timestamp_get($dateTime)));
                            $tour = [
                                'id' => $row->id,
                                'from' => $row->from_city,
                                'to' => $row->to_city,
                                'date' => date("d.m.Y", date_timestamp_get(date_create($this->date))),
                                'departure' => date("H:i", date_timestamp_get(date_create($row->time))),
                                'arrival' => $arrival,
                                'left' => $available,
                                'duration' => $row->duration,
                                'price' => $row->price,
                                'seats' => 1,
                                'priceTotal' => $row->price
                            ];
                            echo json_encode(["tour"=> $tour]);
                            exit();
                        } else {
                            echo json_encode(["msg"=> 'Nema dovoljno mesta za traženi datum. Molimo promenite broj mesta ili datum.']);
                            exit();
                        }
                    } else {
                        $dateTime = date_create($this->date . " " . $row->time);
                        $dur = "+". $row->duration . " " . "hours";
                        $arrival = date("H:i", strtotime($dur, date_timestamp_get($dateTime)));
                        $tour = [
                            'id' => $row->id,
                            'from' => $row->from_city,
                            'to' => $row->to_city,
                            'date' => date("d.m.Y", date_timestamp_get(date_create($this->date))),
                            'departure' => date("H:i", date_timestamp_get(date_create($row->time))),
                            'arrival' => $arrival,
                            'left' => $row->seats,
                            'duration' => $row->duration,
                            'price' => $row->price,
                            'seats' => 1,
                            'priceTotal' => $row->price
                        ];
                        echo json_encode(["tour"=> $tour]);
                        if(!empty($this->inbound)) {
                            $this->date = $this->inbound;
                            $this->inbound = null;
                            $from = $this->to_city;
                            $to = $this->from_city;
                            $this->from_city = $from;
                            $this->to_city = $to;
                            $this->getBySearch();
                        }
                        exit();
                    }
                } else echo json_encode(["msg"=> "Nema dostupnih vožnji prema zadatim parametrima
                                    Dostupni dani su: ."]);
                    exit();
            } else {
                echo json_encode(["msg"=> "Nema dostupnih vožnji prema zadatim parametrima."]);
                exit();
            }
        } else {
            echo json_encode(["msg"=> "Sva polja su obavezna."]);
            exit();
        }
    }

    public function create() 
    {
        $sql = "INSERT INTO tours SET
        from_city = :from_city, to_city = :to_city, departures = :departures,
        time = :time, duration = :duration, price = :price, seats = :seats
        ";
        $stmt = $this->db->prepare($sql);

        $this->from_city = htmlspecialchars(strip_tags($this->from_city));
        $this->to_city = htmlspecialchars(strip_tags($this->to_city));
        $this->departures = htmlspecialchars(strip_tags($this->departures));
        $this->time = htmlspecialchars(strip_tags($this->time));
        $this->duration = htmlspecialchars(strip_tags($this->duration));
        $this->price = htmlspecialchars(strip_tags($this->price));
        $this->seats = htmlspecialchars(strip_tags($this->seats));

        $stmt->bindParam(':from_city', $this->from_city);
        $stmt->bindParam(':to_city', $this->to_city);
        $stmt->bindParam(':departures', $this->departures);
        $stmt->bindParam(':time', $this->time);
        $stmt->bindParam(':duration', $this->duration);
        $stmt->bindParam(':price', $this->price);
        $stmt->bindParam(':seats', $this->seats);

        if($stmt->execute()) {
            echo json_encode(['msg' => "Vožnja od $this->from_city do $this->to_city je uspešno dodata."], JSON_PRETTY_PRINT);
        } else
            echo json_encode(['msg' => "Vožnja od $this->from_city do $this->to_city je uspešno dodata."], JSON_PRETTY_PRINT);
    }

    public function update()
    {
        $sql = "UPDATE tours SET
        from_city = :from_city, to_city = :to_city, departures = :departures,
        time = :time, duration = :duration, price = :price, seats = :seats
        WHERE id = :id
        ";
        $stmt = $this->db->prepare($sql);

        $this->id = htmlspecialchars(strip_tags($this->id));
        $this->from_city = htmlspecialchars(strip_tags($this->from_city));
        $this->to_city = htmlspecialchars(strip_tags($this->to_city));
        $this->departures = htmlspecialchars(strip_tags($this->departures));
        $this->time = htmlspecialchars(strip_tags($this->time));
        $this->duration = htmlspecialchars(strip_tags($this->duration));
        $this->price = htmlspecialchars(strip_tags($this->price));
        $this->seats = htmlspecialchars(strip_tags($this->seats));

        $stmt->bindParam(':id', $this->id);
        $stmt->bindParam(':from_city', $this->from_city);
        $stmt->bindParam(':to_city', $this->to_city);
        $stmt->bindParam(':departures', $this->departures);
        $stmt->bindParam(':time', $this->time);
        $stmt->bindParam(':duration', $this->duration);
        $stmt->bindParam(':price', $this->price);
        $stmt->bindParam(':seats', $this->seats);

        if($stmt->execute()) {
            echo json_encode(['msg' => "Vožnja je uspešno izmenjena."], JSON_PRETTY_PRINT);
        } else
            echo json_encode(['msg' => "Trenutno nije moguće izmeniti ovu vožnju."], JSON_PRETTY_PRINT);
    }

    public function delete() 
    {
        $sql = "UPDATE tours SET
        deleted = 1 WHERE id = :id";
        $stmt = $this->db->prepare($sql);

        $this->from_city = htmlspecialchars(strip_tags($this->id));
        $stmt->bindParam(':id', $this->id);

        if($stmt->execute()) {
            echo json_encode(['msg' => "Vožnja je uspešno obrisana."], JSON_PRETTY_PRINT);
        } else
            echo json_encode(['msg' => "Trenutno nije moguće obrisati ovu vožnju."], JSON_PRETTY_PRINT);  
    }

    public function restore() 
    {
        $sql = "UPDATE tours SET
        deleted = 0 WHERE id = :id";
        $stmt = $this->db->prepare($sql);

        $this->from_city = htmlspecialchars(strip_tags($this->id));
        $stmt->bindParam(':id', $this->id);

        if($stmt->execute()) {
            echo json_encode(['msg' => "Vožnja je uspešno aktivirana."], JSON_PRETTY_PRINT);
        } else
            echo json_encode(['msg' => "Trenutno nije moguće aktivirati ovu vožnju."], JSON_PRETTY_PRINT);  
    }
    public function restoreAll() 
    {
        $sql = "UPDATE tours SET
        deleted = 0 WHERE deleted = 1";
        $stmt = $this->db->prepare($sql);

        if($stmt->execute()) {
            echo json_encode(['msg' => "Sve neaktivne vožnje su uspešno aktivirane."], JSON_PRETTY_PRINT);
        } else
            echo json_encode(['msg' => "Trenutno nije moguće aktivirati sve neaktivne vožnje."], JSON_PRETTY_PRINT);  
    }
}

?>