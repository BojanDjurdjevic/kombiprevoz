<?php
declare(strict_types=1);

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
    public ?int $id = null;
    public ?string $from_city = null;
    public ?string $to_city = null;
    public ?string $departures = null;
    public ?string $time = null;
    public ?int $duration = null;
    public ?int $price = null;
    public ?int $seats = null;
    public ?string $date = null;
    public ?string $inbound = null;
    public ?int $requestedSeats = null;
    
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    // ======================== HELPER METHODS ========================

    // Check if the requested date is departure date
    public function isDepartureDay(string $departureDays, string $requestedDate): bool
    {
        $days = explode(",", $departureDays);
        $depDays = array_map('intval', $days);
        
        $orderDate = (int) date('w', strtotime($requestedDate));

        return in_array($orderDate, $depDays, true);
    }

    // Fetch Tour ID and num of seats
    public function getIdAndSeats(): array|false
    {
        if (empty($this->id)) {
            $sql = "SELECT id, seats 
                    FROM tours 
                    WHERE from_city = :from_city 
                    AND to_city = :to_city
                    AND deleted = 0
                    LIMIT 1";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':from_city', $this->from_city, PDO::PARAM_STR);
            $stmt->bindParam(':to_city', $this->to_city, PDO::PARAM_STR);
        } else {
            $sql = "SELECT id, seats 
                    FROM tours 
                    WHERE id = :id 
                    AND deleted = 0
                    LIMIT 1";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':id', $this->id, PDO::PARAM_INT);
        }

        try {
            $stmt->execute();
            $tour = $stmt->fetch(PDO::FETCH_OBJ);

            if ($tour) {
                $this->id = (int) $tour->id;
                $this->seats = (int) $tour->seats;
                
                return [
                    'id' => (int) $tour->id,
                    'seats' => (int) $tour->seats 
                ];
            }
            
            return false;
            
        } catch (PDOException $e) {
            Logger::error('Failed to fetch tour in getIdAndSeats()', [
                'from_city' => $this->from_city ?? null,
                'to_city' => $this->to_city ?? null,
                'id' => $this->id ?? null,
                'error' => $e->getMessage(),
                'file' => __FILE__,
                'line' => __LINE__
            ]);
            
            http_response_code(500);
            echo json_encode([
                'error' => 'Došlo je do greške pri konekciji na bazu!',
            ], JSON_UNESCAPED_UNICODE);
            
            return false;
        }
    }

    // ======================== GET METHODS ========================

    //Fetch all existing tours (not deleted)
     
    public function getAll(): void
    {
        $sql = "SELECT * FROM tours WHERE deleted IN (0, 1) ORDER BY from_city, to_city";
        
        try {
            $res = $this->db->query($sql);
            $num = $res->rowCount();

            if ($num > 0) {
                $tours = [];
                $from_cities = [];
                $to_cities = [];

                while ($row = $res->fetch(PDO::FETCH_OBJ)) {
                    $tour = [
                        'name' => $row->from_city . " - " . $row->to_city,
                        'id' => (int) $row->id,
                        'from_city' => $row->from_city,
                        'to_city' => $row->to_city,
                        'departures' => $row->departures,
                        'time' => $row->time,
                        'duration' => (int) $row->duration,
                        'price' => (int) $row->price,
                        'seats' => (int) $row->seats,
                        'deleted' => (int) $row->deleted
                    ];
                    
                    $tours[] = $tour;
                    $from_cities[] = $row->from_city;
                    $to_cities[] = $row->to_city;
                }

                $from_cities = array_values(array_unique($from_cities));
                $to_cities = array_values(array_unique($to_cities));

                http_response_code(200);
                echo json_encode([
                    'tours' => $tours, 
                    'from_cities' => $from_cities, 
                    'to_cities' => $to_cities
                ], JSON_UNESCAPED_UNICODE);
            } else {
                http_response_code(200);
                echo json_encode([
                    'msg' => "Nema dostupnih vožnji."
                ], JSON_UNESCAPED_UNICODE);
            }
        } catch (PDOException $e) {
            Logger::error('Failed to fetch all tours in getAll()', [
                'error' => $e->getMessage(),
                'file' => __FILE__,
                'line' => __LINE__
            ]);
            
            http_response_code(500);
            echo json_encode([
                'error' => 'Došlo je do greške pri učitavanju tura!'
            ], JSON_UNESCAPED_UNICODE);
        }
    }

    // Fetch 1 tour by ID
    public function getByID(): array|false
    {
        $sql = "SELECT * FROM tours WHERE id = :id AND deleted IN (0, 1) LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $this->id, PDO::PARAM_INT);

        try {
            $stmt->execute();
            $res = $stmt->fetch(PDO::FETCH_OBJ);
            
            if ($res) {
                return [[
                    "id" => (int) $res->id,
                    "from_city" => $res->from_city,
                    "to_city" => $res->to_city,
                    "departures" => $res->departures,
                    "time" => $res->time,
                    "duration" => (int) $res->duration,
                    "price" => (int) $res->price,
                    "seats" => (int) $res->seats,
                    "deleted" => (int) $res->deleted
                ]];
            }
            
            http_response_code(404);
            echo json_encode([
                'error' => 'Nije pronađena nijedna tura!'
            ], JSON_UNESCAPED_UNICODE);
            
            return false;
            
        } catch (PDOException $e) {
            Logger::error('Failed to fetch tour by ID in getByID()', [
                'id' => $this->id,
                'error' => $e->getMessage(),
                'file' => __FILE__,
                'line' => __LINE__
            ]);
            
            http_response_code(500);
            echo json_encode([
                'error' => 'Došlo je do greške!',
            ], JSON_UNESCAPED_UNICODE);
            
            return false;
        }
    }

    public function getByFilters(): void
    {
        if ($this->id) {
            $tours = $this->getByID();
            http_response_code(200);
            echo json_encode([
                'tours' => $tours,
                'has_tours' => !empty($tours)
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        $params = [
            'from_city' => $this->from_city,
            'to_city' => $this->to_city
        ];

        $params = array_filter($params, fn($p) => !empty($p));
        $cleaned = Validator::cleanParams($params);

        $sql = "SELECT * FROM tours WHERE deleted IN (0, 1)";

        if (isset($cleaned['from_city']) && isset($cleaned['to_city'])) {
            $sql .= " AND (from_city = :from_city OR to_city = :to_city)";
        } else {
            if (isset($cleaned['from_city'])) {
                $sql .= " AND from_city = :from_city";
            }
            if (isset($cleaned['to_city'])) {
                $sql .= " AND to_city = :to_city";
            }
        }

        $sql .= " ORDER BY from_city, to_city";

        try {
            $stmt = $this->db->prepare($sql);
            
            foreach ($cleaned as $k => $v) {
                $stmt->bindValue(':' . $k, $v, PDO::PARAM_STR);
            }

            $stmt->execute();
            $tours = $stmt->fetchAll(PDO::FETCH_OBJ);

            http_response_code(200);
            echo json_encode([
                'tours' => $tours, 
                'has_tours' => !empty($tours)
            ], JSON_UNESCAPED_UNICODE);
            
        } catch (PDOException $e) {
            Logger::error('Failed to fetch tours by filters in getByFilters()', [
                'filters' => $cleaned,
                'error' => $e->getMessage(),
                'file' => __FILE__,
                'line' => __LINE__
            ]);
            
            http_response_code(500);
            echo json_encode([
                'error' => 'Došlo je do greške pri konekciji na bazu!'
            ], JSON_UNESCAPED_UNICODE);
        }
    }

    // all departure days (of week) of specific tour
    public function getDays(): array
    {
        if (!empty($this->from_city) && !empty($this->to_city)) {
            $sql = "SELECT departures 
                    FROM tours 
                    WHERE from_city = :from_city 
                    AND to_city = :to_city 
                    AND deleted = 0 
                    LIMIT 1";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':from_city', $this->from_city, PDO::PARAM_STR);
            $stmt->bindParam(':to_city', $this->to_city, PDO::PARAM_STR);
            
        } elseif (!empty($this->id)) {
            $sql = "SELECT departures 
                    FROM tours 
                    WHERE id = :id 
                    AND deleted = 0 
                    LIMIT 1";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':id', $this->id, PDO::PARAM_INT);
        } else {
            http_response_code(400);
            echo json_encode([
                "error" => "Nedostaju parametri za pretragu."
            ], JSON_UNESCAPED_UNICODE);
            exit();
        }

        try {
            $stmt->execute();
            $num = $stmt->rowCount();

            if ($num > 0) {
                $row = $stmt->fetch(PDO::FETCH_OBJ);
                $arr = explode(',', $row->departures);
                
                $tourDep = array_map('intval', $arr);
                
                return $tourDep;
            }
            
            http_response_code(404);
            echo json_encode([
                "msg" => "Nema dostupnih vožnji prema zadatim parametrima."
            ], JSON_UNESCAPED_UNICODE);
            exit();
            
        } catch (PDOException $e) {
            Logger::error('Failed to fetch departure days in getDays()', [
                'from_city' => $this->from_city ?? null,
                'to_city' => $this->to_city ?? null,
                'id' => $this->id ?? null,
                'error' => $e->getMessage(),
                'file' => __FILE__,
                'line' => __LINE__
            ]);
            
            http_response_code(500);
            echo json_encode([
                'error' => 'Došlo je do greške pri konekciji na bazu!'
            ], JSON_UNESCAPED_UNICODE);
            exit();
        }
    }

    // Fetch destination cities
    public function getToCities(string $name, bool $from): void
    {
        $sql = $from 
            ? "SELECT DISTINCT to_city FROM tours WHERE from_city = :city AND deleted = 0"
            : "SELECT DISTINCT from_city FROM tours WHERE to_city = :city AND deleted = 0";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':city', $name, PDO::PARAM_STR);
            $stmt->execute();

            $cities = $stmt->fetchAll(PDO::FETCH_OBJ);
            
            http_response_code(200);
            echo json_encode([
                'success' => true,
                'toCities' => $cities,
                'has_cities' => !empty($cities)
            ], JSON_UNESCAPED_UNICODE);
            
        } catch (PDOException $e) {
            Logger::error("Failed to fetch Tour to_city in getToCities()", [
                'city' => $name,
                'from' => $from,
                'error' => $e->getMessage(),
                'file' => __FILE__,
                'line' => __LINE__
            ]);
            
            http_response_code(500);
            echo json_encode([
                'error' => 'Došlo je do greške prilikom učitavanja gradova!'
            ], JSON_UNESCAPED_UNICODE);
        }
    }

    // Check teh fully booked days of specific tour
    public function fullyBooked(string $format): array
    {
        $tour = $this->getIdAndSeats();

        if (!$tour) {
            return [
                'success' => true,
                'fullyBooked' => [],
                'availableD' => [],
                'allowed' => [],
                'fullyBookedIn' => [],
                'availableDIn' => [],
                'allowedIn' => []
            ];
        }
        
        $sql = "SELECT date, SUM(places) as totall 
                FROM order_items
                WHERE tour_id = :tour_id 
                AND date LIKE :format
                AND deleted = 0
                GROUP BY date";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':tour_id', $this->id, PDO::PARAM_INT);
        $stmt->bindParam(':format', $format, PDO::PARAM_STR);

        $fullyBooked = [];
        $availableDates = [];
        $allowed = $this->getDays();

        try {
            $stmt->execute();
            
            while ($row = $stmt->fetch(PDO::FETCH_OBJ)) {
                if ((int) $row->totall >= $this->seats) {
                    $fullyBooked[] = $row->date;
                } else {
                    $availableDates[] = $row->date;
                }
            }
            
        } catch (PDOException $e) {
            Logger::error('Failed to check fullyBooked()', [
                'tour_id' => $this->id,
                'format' => $format,
                'error' => $e->getMessage(),
                'file' => __FILE__,
                'line' => __LINE__
            ]);
            
            http_response_code(500);
            echo json_encode([
                'error' => 'Došlo je do greške pri konekciji na bazu!'
            ], JSON_UNESCAPED_UNICODE);
            
            return [
                'fullyBooked' => false, 
                'fullyBookedIn' => false
            ];
        }
        
        // Inbound tour (povratna tura)
        $this->id = null;
        $from = $this->from_city;
        $to = $this->to_city;
        $this->from_city = $to;
        $this->to_city = $from;

        $tourIn = $this->getIdAndSeats();

        if (!$tourIn) {
            return [
                'success' => true,
                'fullyBooked' => $fullyBooked,
                'availableD' => $availableDates,
                'allowed' => $allowed,
                'fullyBookedIn' => [],
                'availableDIn' => [],
                'allowedIn' => []
            ];
        }
        
        $fullyBookedIn = [];
        $availableDatesIn = [];
        
        $sql = "SELECT date, SUM(places) as totall 
                FROM order_items
                WHERE tour_id = :tour_id 
                AND date LIKE :format
                AND deleted = 0
                GROUP BY date";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':tour_id', $this->id, PDO::PARAM_INT);
        $stmt->bindParam(':format', $format, PDO::PARAM_STR);
        
        $allowedIn = $this->getDays();
        
        try {
            $stmt->execute();
            
            while ($row2 = $stmt->fetch(PDO::FETCH_OBJ)) {
                if ((int) $row2->totall >= $this->seats) {
                    $fullyBookedIn[] = $row2->date;
                } else {
                    $availableDatesIn[] = $row2->date;
                }
            }
            
        } catch (PDOException $e) {
            Logger::error('Failed to check fullyBookedIn()', [
                'tour_id' => $this->id,
                'format' => $format,
                'error' => $e->getMessage(),
                'file' => __FILE__,
                'line' => __LINE__
            ]);
            
            http_response_code(500);
            echo json_encode([
                'error' => 'Došlo je do greške pri konekciji na bazu!'
            ], JSON_UNESCAPED_UNICODE);
            
            return [
                'fullyBooked' => $fullyBooked,
                'availableD' => $availableDates,
                'allowed' => $allowed,
                'fullyBookedIn' => false
            ];
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

    // Search available tours
    public function getBySearch(): void
    {
        // Validacija obaveznih polja
        if (empty($this->from_city) || empty($this->to_city) || empty($this->date)) {
            http_response_code(400);
            echo json_encode([
                "error" => "Sva polja su obavezna."
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        // Validacija gradova
        if (!Validator::validateString($this->from_city) || !Validator::validateString($this->to_city)) {
            http_response_code(400);
            echo json_encode([
                "error" => "Potrebno je uneti validne podatke (Ime postojećeg grada)."
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        // SQL upit
        if (!empty($this->inbound)) {
            $sql = "SELECT * FROM tours 
                    WHERE ((from_city = :from_city AND to_city = :to_city) 
                    OR (from_city = :to_city AND to_city = :from_city))
                    AND deleted = 0";
        } else {
            $sql = "SELECT * FROM tours 
                    WHERE from_city = :from_city 
                    AND to_city = :to_city 
                    AND deleted = 0";
        }

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':from_city', $this->from_city, PDO::PARAM_STR);
            $stmt->bindParam(':to_city', $this->to_city, PDO::PARAM_STR);
            $stmt->execute();
            
            $num = $stmt->rowCount();
            $tours = [];

            if ($num > 0) {
                while ($row = $stmt->fetch(PDO::FETCH_OBJ)) {
                    // Provera dana polaska
                    $checkDate = ($row->from_city === $this->from_city) 
                        ? $this->date 
                        : $this->inbound;
                    
                    $checkDeps = $this->isDepartureDay($row->departures, $checkDate);
                    
                    if (!$checkDeps) {
                        continue;
                    }

                    // Provera zauzetosti
                    $ordSql = "SELECT SUM(places) as total_places 
                               FROM order_items 
                               WHERE tour_id = :tour_id 
                               AND date = :date
                               AND deleted = 0";
                    
                    $ordStmt = $this->db->prepare($ordSql);
                    $ordStmt->bindValue(':tour_id', $row->id, PDO::PARAM_INT);
                    $ordStmt->bindValue(':date', $checkDate, PDO::PARAM_STR);
                    $ordStmt->execute();
                    
                    $ordRow = $ordStmt->fetch(PDO::FETCH_OBJ);
                    $occupated = $ordRow->total_places ? (int) $ordRow->total_places : 0;
                    $available = (int) $row->seats - $occupated;

                    if ($available < $this->requestedSeats) {
                        continue;
                    }

                    // Računanje vremena dolaska
                    $dateTime = strtotime($checkDate . " " . $row->time);
                    $arrival = date("H:i", strtotime("+{$row->duration} hours", $dateTime));
                    
                    $myDate = date("d.m.Y", strtotime($checkDate));

                    $tours[] = [
                        'id' => (int) $row->id,
                        'from' => $row->from_city,
                        'to' => $row->to_city,
                        'date' => $myDate,
                        'departure' => substr($row->time, 0, 5),
                        'arrival' => $arrival,
                        'left' => $available,
                        'duration' => (int) $row->duration,
                        'price' => (int) $row->price,
                        'seats' => $this->requestedSeats,
                        'priceTotal' => (int) $row->price * $this->requestedSeats
                    ];
                }

                if (empty($tours)) {
                    http_response_code(404);
                    echo json_encode([
                        "msg" => 'Nema dovoljno mesta za traženi datum. Molimo promenite broj mesta ili datum.'
                    ], JSON_UNESCAPED_UNICODE);
                    return;
                }

                http_response_code(200);
                echo json_encode([
                    "tour" => $tours
                ], JSON_UNESCAPED_UNICODE);
                
            } else {
                http_response_code(404);
                echo json_encode([
                    "msg" => "Nema dostupnih vožnji prema zadatim parametrima."
                ], JSON_UNESCAPED_UNICODE);
            }
            
        } catch (PDOException $e) {
            Logger::error('Failed to search tours in getBySearch()', [
                'from_city' => $this->from_city,
                'to_city' => $this->to_city,
                'date' => $this->date,
                'inbound' => $this->inbound ?? null,
                'error' => $e->getMessage(),
                'file' => __FILE__,
                'line' => __LINE__
            ]);
            
            http_response_code(500);
            echo json_encode([
                'error' => 'Došlo je do greške pri pretrazi tura!'
            ], JSON_UNESCAPED_UNICODE);
        }
    }

    // ======================== CREATE METHOD ========================
    public function create(): void
    {
        $tour = $this->getIdAndSeats();

        if ($tour) {
            http_response_code(409);
            echo json_encode([
                'error' => 'Tura sa ovim gradovima već postoji!'
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        $sql = "INSERT INTO tours SET
                from_city = :from_city, 
                to_city = :to_city, 
                departures = :departures,
                time = :time, 
                duration = :duration, 
                price = :price, 
                seats = :seats";
        
        try {
            $stmt = $this->db->prepare($sql);

            $stmt->bindParam(':from_city', $this->from_city, PDO::PARAM_STR);
            $stmt->bindParam(':to_city', $this->to_city, PDO::PARAM_STR);
            $stmt->bindParam(':departures', $this->departures, PDO::PARAM_STR);
            $stmt->bindParam(':time', $this->time, PDO::PARAM_STR);
            $stmt->bindParam(':duration', $this->duration, PDO::PARAM_INT);
            $stmt->bindParam(':price', $this->price, PDO::PARAM_INT);
            $stmt->bindParam(':seats', $this->seats, PDO::PARAM_INT);

            $stmt->execute();

            // Aktivacija grada
            $cityStmt = $this->db->prepare("UPDATE cities SET deleted = 0 WHERE name = :name");
            $cityStmt->bindParam(":name", $this->to_city, PDO::PARAM_STR);
            $cityStmt->execute();

            http_response_code(201);
            echo json_encode([
                'success' => true,
                'msg' => "Vožnja od $this->from_city do $this->to_city je uspešno dodata."
            ], JSON_UNESCAPED_UNICODE);
            
        } catch (PDOException $e) {
            Logger::error('Failed to create tour in create()', [
                'from_city' => $this->from_city,
                'to_city' => $this->to_city,
                'error' => $e->getMessage(),
                'file' => __FILE__,
                'line' => __LINE__
            ]);
            
            http_response_code(500);
            echo json_encode([
                'error' => "Vožnja od $this->from_city do $this->to_city ne može da se doda zbog neuspešne konekcije na bazu podataka."
            ], JSON_UNESCAPED_UNICODE);
        }
    }

    // ======================== UPDATE METHOD ========================
    public function update(): void
    {
        $sql = "UPDATE tours SET
                departures = :departures, 
                time = :time, 
                duration = :duration, 
                price = :price, 
                seats = :seats
                WHERE id = :id";
        
        try {
            $stmt = $this->db->prepare($sql);

            $stmt->bindParam(':id', $this->id, PDO::PARAM_INT);
            $stmt->bindParam(':departures', $this->departures, PDO::PARAM_STR);
            $stmt->bindParam(':time', $this->time, PDO::PARAM_STR);
            $stmt->bindParam(':duration', $this->duration, PDO::PARAM_INT);
            $stmt->bindParam(':price', $this->price, PDO::PARAM_INT);
            $stmt->bindParam(':seats', $this->seats, PDO::PARAM_INT);

            $stmt->execute();

            http_response_code(200);
            echo json_encode([
                'success' => true,
                'msg' => "Tura je uspešno izmenjena."
            ], JSON_UNESCAPED_UNICODE);
            
        } catch (PDOException $e) {
            Logger::error('Failed to update tour in update()', [
                'id' => $this->id,
                'error' => $e->getMessage(),
                'file' => __FILE__,
                'line' => __LINE__
            ]);
            
            http_response_code(500);
            echo json_encode([
                'error' => 'Došlo je do greške prilikom konekcije na bazu podataka!'
            ], JSON_UNESCAPED_UNICODE);
        }
    }

    // ======================== DELETE METHODS ========================

    /**
     * Soft delete 
     */
    public function delete(): void
    {
        $sql = "UPDATE tours SET deleted = 1 WHERE id = :id";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':id', $this->id, PDO::PARAM_INT);
            $stmt->execute();

            // Deaktivacija grada ako nema drugih aktivnih tura
            $cityStmt = $this->db->prepare("
                UPDATE cities 
                SET deleted = 1 
                WHERE name = (SELECT to_city FROM tours WHERE id = :tour_id)
                AND NOT EXISTS (
                    SELECT 1 
                    FROM tours 
                    WHERE to_city = (SELECT to_city FROM tours WHERE id = :tour_id2)
                    AND deleted = 0
                    AND id != :tour_id3
                )
            ");
            $cityStmt->bindParam(":tour_id", $this->id, PDO::PARAM_INT);
            $cityStmt->bindParam(":tour_id2", $this->id, PDO::PARAM_INT);
            $cityStmt->bindParam(":tour_id3", $this->id, PDO::PARAM_INT);
            $cityStmt->execute();

            http_response_code(200);
            echo json_encode([
                'success' => true,
                'msg' => "Tura je uspešno deaktivirana."
            ], JSON_UNESCAPED_UNICODE);
            
        } catch (PDOException $e) {
            Logger::error('Failed to delete tour in delete()', [
                'id' => $this->id,
                'error' => $e->getMessage(),
                'file' => __FILE__,
                'line' => __LINE__
            ]);
            
            http_response_code(500);
            echo json_encode([
                'error' => "Trenutno nije moguće obrisati ovu turu."
            ], JSON_UNESCAPED_UNICODE);
        }
    }

    /**
     * Restore 
     */
    public function restore(): void
    {
        $sql = "UPDATE tours SET deleted = 0 WHERE id = :id";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':id', $this->id, PDO::PARAM_INT);
            $stmt->execute();

            // Aktivacija grada
            $cityStmt = $this->db->prepare("
                UPDATE cities 
                SET deleted = 0 
                WHERE name = (SELECT to_city FROM tours WHERE id = :tour_id)
            ");
            $cityStmt->bindParam(":tour_id", $this->id, PDO::PARAM_INT);
            $cityStmt->execute();

            http_response_code(200);
            echo json_encode([
                'success' => true,
                'msg' => "Tura je uspešno aktivirana."
            ], JSON_UNESCAPED_UNICODE);
            
        } catch (PDOException $e) {
            Logger::error('Failed to restore tour in restore()', [
                'id' => $this->id,
                'error' => $e->getMessage(),
                'file' => __FILE__,
                'line' => __LINE__
            ]);
            
            http_response_code(500);
            echo json_encode([
                'error' => "Trenutno nije moguće aktivirati ovu turu."
            ], JSON_UNESCAPED_UNICODE);
        }
    }

    /**
     * Restore all deleted tours
     */
    public function restoreAll(): void
    {
        $sql = "UPDATE tours SET deleted = 0 WHERE deleted = 1";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute();

            http_response_code(200);
            echo json_encode([
                'success' => true,
                'msg' => "Sve ture su uspešno aktivirane."
            ], JSON_UNESCAPED_UNICODE);
            
        } catch (PDOException $e) {
            Logger::error('Failed to restore all tours in restoreAll()', [
                'error' => $e->getMessage(),
                'file' => __FILE__,
                'line' => __LINE__
            ]);
            
            http_response_code(500);
            echo json_encode([
                'error' => "Trenutno nije moguće aktivirati sve ture."
            ], JSON_UNESCAPED_UNICODE);
        }
    }

    /**
     * Permanent (soft) delete 
     */
    public function permanentDelete(): void
    {
        $sql = "UPDATE tours SET deleted = 2 WHERE id = :id";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':id', $this->id, PDO::PARAM_INT);
            $stmt->execute();

            http_response_code(200);
            echo json_encode([
                'success' => true,
                'msg' => 'Tura je zauvek obrisana'
            ], JSON_UNESCAPED_UNICODE);
            
        } catch (PDOException $e) {
            Logger::error('Failed to permanently delete tour in permanentDelete()', [
                'id' => $this->id,
                'error' => $e->getMessage(),
                'file' => __FILE__,
                'line' => __LINE__
            ]);
            
            http_response_code(500);
            echo json_encode([
                'error' => "Trenutno nije moguće zauvek obrisati ovu turu."
            ], JSON_UNESCAPED_UNICODE);
        }
    }
}

?>