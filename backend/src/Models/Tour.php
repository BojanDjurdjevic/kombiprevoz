<?php

namespace Models;

use Helpers\Logger;
use PDO;
use PDOException;
use Rules\Validator;

if (!defined('APP_ACCESS')) {
    http_response_code(403);
    die('Direct access forbidden');
}

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

    public function isDepartureDay($d, $requested): bool
    {
        $days = $d;
        
        $days = explode(",", $days);

        $depDays = [];

        foreach( $days as $day ) {
            array_push( $depDays, (int)$day);
        }

        $orderDate = date('w', strtotime($requested));

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
                    WHERE from_city = :from_city AND to_city = :to_city
            ";
            $stmt = $this->db->prepare($sql);

            $this->from_city = htmlspecialchars(strip_tags($this->from_city), ENT_QUOTES);
            $this->to_city = htmlspecialchars(strip_tags($this->to_city), ENT_QUOTES);

            $stmt->bindParam(':from_city', $this->from_city);
            $stmt->bindParam(':to_city', $this->to_city);
        } else {
            $sql = "SELECT id, seats from tours 
                WHERE id = :id 
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
                } else return false;
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
        $day = date('Y-m') . '%';
        $sql = "SELECT * from tours WHERE deleted IN (0, 1)";
        $res = $this->db->query($sql);
        $num = $res->rowCount();

        if($num > 0) {
            $tours = [];
            $from_cities = [];
            $to_cities = [];
            $fully = [];

            while($row = $res->fetch(PDO::FETCH_OBJ)) {
                array_push($tours, [
                    'name' => $row->from_city . " - " . $row->to_city,
                    'id' => $row->id,
                    'from_city' => $row->from_city,
                    'to_city' => $row->to_city,
                    'departures' => $row->departures,
                    'time' => $row->time,
                    'duration' => $row->duration,
                    'price' => $row->price,
                    'seats' => $row->seats,
                    'deleted' => $row->deleted
                ]);
            }

            foreach($tours as $id => $t) {
                $from_cities[] = $t['from_city'];
                $to_cities[] = $t['to_city']; /*
                $this->id = $t['id'];
                $full_days = $this->fullyBooked($day);
                $fully[] = [
                    $this->id => $full_days
                ]; */
            }
            $from_cities = array_values(array_unique($from_cities));
            $to_cities = array_values(array_unique($to_cities));

            echo json_encode(['tours' => $tours, 'from_cities' => $from_cities, 'to_cities' => $to_cities]);
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
                    "seats" => $res->seats,
                    "deleted" => $res->deleted
                ]);

                if(count($tour) > 0) return $tour;
                else {
                    http_response_code(404);
                    echo json_encode(['error' => 'Nije pronađena nijedna tura!']);
                } 
            }
        } catch (PDOException $e) {
            echo json_encode([
                'tour' => 'Došlo je do greške!', 
                'msg' => $e->getMessage()    
            ]);
        }
    }

    public function getByFilters() {
        if($this->id) {
            $tours = $this->getByID();
            http_response_code(200);
            echo json_encode([
                'tours' => $tours,
                'has_tours' => !empty($tours)
            ], JSON_PRETTY_PRINT);
            exit();
        }

        $params = [
            'from_city' => $this->from_city,
            'to_city' => $this->to_city
        ];

        $params = array_filter($params, fn($p) => !empty($p));

        $cleaned = Validator::cleanParams($params);

        $sql = "SELECT * from tours WHERE deleted IN (0, 1)";

        if(isset($cleaned['from_city']) && isset($cleaned['to_city'])) {
            $sql .= " AND (from_city = :from_city OR to_city = :to_city)";
        } else {
            if(isset($cleaned['from_city'])) $sql .= " AND from_city = :from_city";
            if(isset($cleaned['to_city'])) $sql .= " AND to_city = :to_city";
        }
        

        $stmt = $this->db->prepare($sql);
        foreach($cleaned as $k => $v) {
            $stmt->bindValue(':' . $k, $v);
        }

        try {
            $stmt->execute();
            $tours = $stmt->fetchAll(PDO::FETCH_OBJ);

            header('Content-Type: application/json');
            echo json_encode(['tours' => $tours, 'has_tours' => !empty($tours)], JSON_PRETTY_PRINT);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode([
                'error' => 'Došlo je do greške pri konekciji na bazu!',
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

    // -----------------------GET tour - to cities -------------------------------//

    public function getToCities($name, $from)
    {
        $sql = $from ? "SELECT DISTINCT to_city FROM tours
                WHERE from_city = :city" :
                "SELECT DISTINCT from_city FROM tours
                WHERE to_city = :city"
        ; /*
        if($from) {
            $sql = "SELECT to_city FROM tours
                WHERE from_city = :city
            ";
        } else
        $sql = "SELECT from_city FROM tours
                WHERE to_city = :city
        "; */
        
        $stmt = $this->db->prepare($sql);

        $stmt->bindParam(':city', $name);

        try {
            $stmt->execute();

            $cities = $stmt->fetchAll(PDO::FETCH_OBJ);
            http_response_code(200);
            echo json_encode([
                'success' => true,
                'toCities' => $cities,
                'has_cities' => !empty($cities)
            ]);
        } catch (PDOException $e) {
            Logger::error("Failed to fetch Tour to_city getToCities()", [
                'error' => $e->getMessage(),
                'file' => __FILE__,
                'line' => __LINE__
            ]);
            http_response_code(500);
            echo json_encode(['error' => 'Došlo je do greške prilikom učitavanja gradova!']);
        }
    }

    //------------------------- Checks available dates for the requested tour --------------//
    public function fullyBooked($format)
    {
        $tour = $this->getIdAndSeats();

        if(!$tour) return [
            'success' => true,
            'fullyBooked' => [],
            'availableD' => [],
            'allowed' => [],
            'fullyBookedIn' => [],
            'availableDIn' => [],
            'allowedIn' => []
        ];
        
        $sql = "SELECT date, SUM(places) as totall FROM order_items
                WHERE tour_id = :tour_id AND 
                date LIKE :format
                GROUP BY date
        ";
        
        $stmt = $this->db->prepare($sql);

        $this->id = htmlspecialchars(strip_tags($this->id), ENT_QUOTES);
        $format = htmlspecialchars(strip_tags($format), ENT_QUOTES);
        $stmt->bindParam(':tour_id', $this->id, PDO::PARAM_INT);
        $stmt->bindParam(':format', $format);

        $fullyBooked = [];
        $availableDates = [];
        $allowed = $this->getDays();

        $fullyBookedIn = [];
        $availableDatesIn = [];

        try {
            if($stmt->execute()) {
                
                while ($row = $stmt->fetch(PDO::FETCH_OBJ)) {
                    if($row->totall >= $this->seats) {
                        array_push($fullyBooked, $row->date);
                    } else
                        array_push($availableDates, $row->date);
                }
                
            }
        } catch (PDOException $e) {
            Logger::error('Failed to check fullyBooked() ', [
                'error' => $e->getMessage(),
                'file' => __FILE__,
                'line' => __LINE__
            ]);
            http_response_code(500);
            echo json_encode([
                'error' => 'Došlo je do greške pri konekciji na bazu!'
            ], JSON_PRETTY_PRINT);
            return ['fullyBooked' => false, 'fullyBookedIn' => false];
        }
        
        $this->id = null;
        $from = $this->from_city;
        $to = $this->to_city;
        $this->from_city = $to;
        $this->to_city = $from;

        $tourIn = $this->getIdAndSeats();

        if(!$tourIn) return [
            'success' => true,
            'fullyBookedIn' => [],
            'availableDIn' => [],
            'allowedIn' => []
        ];
        
        $sql = "SELECT date, SUM(places) as totall FROM order_items
                WHERE tour_id = :tour_id AND 
                date LIKE :format
                GROUP BY date
        ";
        $stmt = $this->db->prepare($sql);

        //$this->id = htmlspecialchars(strip_tags($this->id), ENT_QUOTES);
        $format = htmlspecialchars(strip_tags($format), ENT_QUOTES);
        $stmt->bindParam(':tour_id', $this->id);
        $stmt->bindParam(':format', $format);
        $allowedIn = $this->getDays();
        try {
            if($stmt->execute()) {
                
                while ($row2 = $stmt->fetch(PDO::FETCH_OBJ)) {
                    if($row2->totall >= $this->seats) {
                        array_push($fullyBookedIn, $row2->date);
                    } else
                        array_push($availableDatesIn, $row2->date);
                }
            }
        } catch (PDOException $e) {
            Logger::error('Failed to check fullyBooked() ', [
                'error' => $e->getMessage(),
                'file' => __FILE__,
                'line' => __LINE__
            ]);
            http_response_code(500);
            echo json_encode([
                'error' => 'Došlo je do greške pri konekciji na bazu!'
            ], JSON_PRETTY_PRINT);
            return ['fullyBooked' => false, 'fullyBookedIn' => false];
        }

        return [
            'success' => true,
            'fullyBooked' => $fullyBooked,
            'availableD' => $availableDates,
            'allowed' => $allowed,
            'fullyBookedIn' => $fullyBookedIn,
            'availableDIn' => $availableDatesIn,
            'allowedIn' => $allowedIn
        ];
    }
    //------------------- Search avaailable tours ----------------------------//
    public function getBySearch() {
        if(!empty($this->from_city) && !empty($this->to_city) && !empty($this->date) && !empty($this->inbound)) {
            $sql = "SELECT * from tours WHERE
                    (from_city = '$this->from_city' AND to_city = '$this->to_city' AND deleted = 0)
                    OR
                    (from_city = '$this->to_city' AND to_city = '$this->from_city' AND deleted = 0)
            ";
            /*
            $this->from_city = htmlspecialchars(strip_tags($this->from_city));
            $this->to_city = htmlspecialchars(strip_tags($this->to_city));
            $this->date = htmlspecialchars(strip_tags($this->date));
            $this->inbound = htmlspecialchars(strip_tags($this->inbound)); */
        } elseif(!empty($this->from_city) && !empty($this->to_city) && !empty($this->date)) {
            $sql = "SELECT * from tours WHERE
                    from_city = '$this->from_city' AND to_city = '$this->to_city' AND deleted = 0
            ";
            /*
            $this->from_city = htmlspecialchars(strip_tags($this->from_city));
            $this->to_city = htmlspecialchars(strip_tags($this->to_city));
            $this->date = htmlspecialchars(strip_tags($this->date)); */
        } else {
            http_response_code(401);
            echo json_encode([
                "error"=> "Sva polja su obavezna."
            ]);
            exit();
        }
        
        
        if(Validator::validateString($this->from_city) && Validator::validateString($this->to_city)) {
            $res = $this->db->query($sql);
            $num = $res->rowCount();
            $tours = [];
            if($num > 0) {
                while($row = $res->fetch(PDO::FETCH_OBJ)) {
                    if($row->from_city == $this->from_city)
                        $checkDeps = $this->isDepartureDay($row->departures, $this->date);
                    else
                        $checkDeps = $this->isDepartureDay($row->departures, $this->inbound);
                    if($checkDeps) {
                        if($row->from_city == $this->from_city)
                        $ordSql = "SELECT places from order_items WHERE tour_id = '$row->id' 
                        and date = '$this->date'
                        ";
                        else
                        $ordSql = "SELECT places from order_items WHERE tour_id = '$row->id' 
                        and date = '$this->inbound'
                        ";
                        
                        $ordRes = $this->db->query($ordSql);
                        if($ordRes->rowCount() > 0) {
                            //
                            $occupated = 0;
                            while($ordRow = $ordRes->fetch(PDO::FETCH_OBJ)) {
                                $occupated += $ordRow->places;
                            }
                            $available = $row->seats - $occupated;
                            if($available >= $this->requestedSeats) {
                                $dateTime = date_create($this->date . " " . $row->time);
                                $dur = "+". $row->duration . " " . "hours";
                                $arrival = date("H:i", strtotime($dur, date_timestamp_get($dateTime)));
                                if($row->from_city == $this->from_city)
                                    $myDate = date("d.m.Y", date_timestamp_get(date_create($this->date)));
                                else
                                    $myDate = date("d.m.Y", date_timestamp_get(date_create($this->inbound)));
                                $t = [
                                        'id' => $row->id,
                                        'from' => $row->from_city,
                                        'to' => $row->to_city,
                                        'date' => $myDate,
                                        'departure' => date("H:i", date_timestamp_get(date_create($row->time))),
                                        'arrival' => $arrival,
                                        'left' => $available,
                                        'duration' => $row->duration,
                                        'price' => $row->price,
                                        'seats' => (int)$this->requestedSeats,
                                        'priceTotal' => $row->price * (int)$this->requestedSeats
                                ];
                                array_push($tours, $t);
                            } else {
                                echo json_encode(["msg"=> 'Nema dovoljno mesta za traženi datum. Molimo promenite broj mesta ili datum.']);
                                exit();
                            }
                        } else {
                            $dateTime = date_create($this->date . " " . $row->time);
                            $dur = "+". $row->duration . " " . "hours";
                            $arrival = date("H:i", strtotime($dur, date_timestamp_get($dateTime)));
                            if($row->from_city == $this->from_city)
                                $myDate = date("d.m.Y", date_timestamp_get(date_create($this->date)));
                            else
                                $myDate = date("d.m.Y", date_timestamp_get(date_create($this->inbound)));
                            $t = [
                                    'id' => $row->id,
                                    'from' => $row->from_city,
                                    'to' => $row->to_city,
                                    'date' => $myDate,
                                    'departure' => date("H:i", date_timestamp_get(date_create($row->time))),
                                    'arrival' => $arrival,
                                    'left' => $row->seats,
                                    'duration' => $row->duration,
                                    'price' => $row->price,
                                    'seats' => (int)$this->requestedSeats,
                                    'priceTotal' => $row->price * (int)$this->requestedSeats
                            ];
                            array_push($tours, $t);
                            
                        }
                    
                    } else { echo json_encode([
                        
                        "msg"=> "Nema dostupnih vožnji prema zadatim parametrima. Dostupni dani su: ."]);
                        exit(); 
                    }
                }
                echo json_encode(["tour"=> $tours]);
                exit();
            }
        } else {
            echo json_encode([
                http_response_code(401),
                "msg"=> "Potrebno je uneti validne podatke (Ime postojećeg grada)."
            ]);
            exit();
        }
    }

    // -----------------------   POST   ----------------------------//

    public function create() 
    {
        $tour = $this->getIdAndSeats();

        if($tour) {
            http_response_code(403);
            echo json_encode([
                'error' => 'Tura sa ovim gradovima već postoji!'
            ]);
            exit;
        }
        $sql = "INSERT INTO tours SET
        from_city = :from_city, to_city = :to_city, departures = :departures,
        time = :time, duration = :duration, price = :price, seats = :seats
        ";
        $stmt = $this->db->prepare($sql);

        $this->from_city = htmlspecialchars(strip_tags($this->from_city));
        $this->to_city = htmlspecialchars(strip_tags($this->to_city));
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

        try {
            if($stmt->execute()) {
                $citiSql = "UPDATE cities 
                            SET deleted = 0 
                            WHERE name = :name
                            "
                ;
                $stmt = $this->db->prepare($citiSql);
                $stmt->bindParam(":name", $this->to_city, PDO::PARAM_STR);

                $stmt->execute();

                http_response_code(200);
                echo json_encode(['msg' => "Vožnja od $this->from_city do $this->to_city je uspešno dodata."], JSON_PRETTY_PRINT);
            } 
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode([
                'error' => "Vožnja od $this->from_city do $this->to_city ne može da se doda zbog neuspešne konekcije na bazu podataka.",
                'msg' => $e->getMessage()
            ], JSON_PRETTY_PRINT);
        }
            
    }

    // ------------------------------ PUT ---------------------------------------/

    public function update()
    {
        $sql = "UPDATE tours SET
        departures = :departures, time = :time, duration = :duration, 
        price = :price, seats = :seats
        WHERE id = :id
        ";
        $stmt = $this->db->prepare($sql);

        $this->id = htmlspecialchars(strip_tags($this->id));
        $this->departures = htmlspecialchars(strip_tags($this->departures));
        $this->time = htmlspecialchars(strip_tags($this->time));
        $this->duration = htmlspecialchars(strip_tags($this->duration));
        $this->price = htmlspecialchars(strip_tags($this->price));
        $this->seats = htmlspecialchars(strip_tags($this->seats));

        $stmt->bindParam(':id', $this->id);
        $stmt->bindParam(':departures', $this->departures);
        $stmt->bindParam(':time', $this->time);
        $stmt->bindParam(':duration', $this->duration);
        $stmt->bindParam(':price', $this->price);
        $stmt->bindParam(':seats', $this->seats);

        try {
            if($stmt->execute()) {
                http_response_code(200);
                echo json_encode([
                    'success' => true,
                    'msg' => "Tura je uspešno izmenjena."
                ], JSON_PRETTY_PRINT);
            }
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode([
                'error' => 'Došlo je do greške prilikom konekcije na bazu podataka!',
                'msg' => $e->getMessage()
            ], JSON_PRETTY_PRINT);
        }
    }

    public function delete() 
    {
        $sql = "UPDATE tours SET
        deleted = 1 WHERE id = :id";
        $stmt = $this->db->prepare($sql);

        $stmt->bindParam(':id', $this->id, PDO::PARAM_INT);

        try {
            if($stmt->execute()) {
                $citiSql = "UPDATE cities 
                            SET deleted = 1 
                            WHERE name = (
                                SELECT to_city FROM tours WHERE id = :tour_id
                            )
                            AND NOT EXISTS (
                                SELECT 1 
                                FROM tours 
                                WHERE to_city = (SELECT to_city FROM tours WHERE id = :tour_id)
                                AND deleted = 0
                                AND id != :tour_id
                            );"
                ;
                $stmt = $this->db->prepare($citiSql);
                $stmt->bindParam(":tour_id", $this->id, PDO::PARAM_INT);

                $stmt->execute();

                http_response_code(200);
                echo json_encode(['msg' => "Tura je uspešno deaktivirana."], JSON_PRETTY_PRINT);
            }
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode([
                'error' => "Trenutno nije moguće obrisati ovu turu.",
                'msg' => $e->getMessage()
            ], JSON_PRETTY_PRINT);  
        }           
    }

    public function restore() 
    {
        $sql = "UPDATE tours SET
        deleted = 0 WHERE id = :id";
        $stmt = $this->db->prepare($sql);

        $stmt->bindParam(':id', $this->id);

        try {
            if($stmt->execute()) {
                $citiSql = "UPDATE cities 
                            SET deleted = 0 
                            WHERE name = (
                                SELECT to_city FROM tours WHERE id = :tour_id
                            )"
                ;
                $stmt = $this->db->prepare($citiSql);
                $stmt->bindParam(":tour_id", $this->id, PDO::PARAM_INT);

                $stmt->execute();

                http_response_code(200);
                echo json_encode([
                    'success' => true,
                    'msg' => "Tura je uspešno aktivirana."
                ], JSON_PRETTY_PRINT);
            }
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode([
                'error' => "Trenutno nije moguće aktivirati ovu turu.",
                'msg' => $e->getMessage()
            ], JSON_PRETTY_PRINT);  
        }  
    }
    public function restoreAll() 
    {
        $sql = "UPDATE tours SET
        deleted = 0 WHERE deleted = 1";
        $stmt = $this->db->prepare($sql);

        try {
            if($stmt->execute()) {
                echo json_encode(['msg' => "Sve ture su uspešno aktivirane."], JSON_PRETTY_PRINT);
            }
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode([
                'error' => "Trenutno nije moguće aktivirati sve ture.",
                'msg' => $e->getMessage()
            ], JSON_PRETTY_PRINT);  
        }
    }

    public function permanentDelete() 
    {
        $sql = "UPDATE tours SET deleted = 2 WHERE id = :id";
        $stmt = $this->db->prepare($sql);

        $this->id = htmlspecialchars(strip_tags($this->id), ENT_QUOTES);

        $stmt->bindParam(':id', $this->id);

        try {
            $stmt->execute();
            echo json_encode([
                'success' => true,
                'msg' => 'Tura je zauvek obrisana'
            ], JSON_PRETTY_PRINT);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode([
                'error' => "Trenutno nije moguće zauvek obrisati ovu turu.",
                'msg' => $e->getMessage()
            ], JSON_PRETTY_PRINT);  
        }
    }
}

?>