<?php
declare(strict_types=1);

namespace Models;

use Helpers\Logger;
use PDO;
use PDOException;

if (!defined('APP_ACCESS')) {
    http_response_code(403);
    die('Direct access forbidden');
}

class Departure {
    public ?int $id = null;
    public ?int $driver_id = null;
    public ?int $tour_id = null;
    public ?string $code = null;
    public ?string $path = null;
    public ?string $date = null;

    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    // ======================== GET METHODS ========================

    /**
     * Dobijanje rezervacija za specifičan polazak
     */
    public function getOrdersOfDep(): void
    {
        if (empty($this->id)) {
            http_response_code(400);
            echo json_encode([
                'error' => 'ID polaska je obavezan'
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        $sql = "SELECT 
                    order_items.id, 
                    order_items.tour_id, 
                    order_items.places,
                    tours.from_city, 
                    order_items.add_from as pickup, 
                    tours.to_city, 
                    order_items.add_to as dropoff,
                    order_items.date, 
                    tours.time as pickuptime, 
                    tours.duration,
                    order_items.price, 
                    orders.code, 
                    orders.file_path as voucher, 
                    users.name as user, 
                    users.email, 
                    users.phone
                FROM order_items
                INNER JOIN orders ON order_items.order_id = orders.id
                INNER JOIN tours ON order_items.tour_id = tours.id
                INNER JOIN users ON orders.user_id = users.id
                WHERE order_items.dep_id = :id 
                AND order_items.deleted = 0
                ORDER BY order_items.date, tours.time";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':id', $this->id, PDO::PARAM_INT);
            $stmt->execute();

            $departures = $stmt->fetchAll(PDO::FETCH_OBJ);

            if (!empty($departures)) {
                http_response_code(200);
                echo json_encode([
                    'drive' => $departures
                ], JSON_UNESCAPED_UNICODE);
            } else {
                http_response_code(404);
                echo json_encode([
                    'drive' => 'Nije pronađena vožnja!'
                ], JSON_UNESCAPED_UNICODE);
            }
        } catch (PDOException $e) {
            Logger::error('Failed to fetch departure orders in getOrdersOfDep()', [
                'id' => $this->id,
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

    /**
     * Dobijanje polazaka po filterima
     */
    public function getByFilter(): void
    {
        $sql = "SELECT 
                    departures.*, 
                    tours.from_city, 
                    tours.to_city,
                    tours.time, 
                    tours.price, 
                    users.name as driver, 
                    users.phone as driver_contact, 
                    users.email
                FROM departures
                INNER JOIN tours ON departures.tour_id = tours.id
                INNER JOIN users ON departures.driver_id = users.id
                WHERE departures.deleted = 0";

        $params = [];
        $conditions = [];

        // Filter po code-u
        if (!empty($this->code)) {
            $conditions[] = "departures.code = :code";
            $params[':code'] = $this->code;
        }

        // Filter po driver_id
        if (!empty($this->driver_id)) {
            $conditions[] = "departures.driver_id = :driver_id";
            $params[':driver_id'] = $this->driver_id;
        }

        // Filter po date
        if (!empty($this->date)) {
            $conditions[] = "departures.date = :date";
            $params[':date'] = $this->date;
        }

        // Filter po tour_id
        if (!empty($this->tour_id)) {
            $conditions[] = "departures.tour_id = :tour_id";
            $params[':tour_id'] = $this->tour_id;
        }

        // Dodavanje uslova u SQL
        if (!empty($conditions)) {
            $sql .= " AND " . implode(" AND ", $conditions);
        }

        $sql .= " ORDER BY departures.date, tours.time";

        try {
            $stmt = $this->db->prepare($sql);

            // Bind parametara
            foreach ($params as $key => $value) {
                if ($key === ':code') {
                    $stmt->bindValue($key, $value, PDO::PARAM_STR);
                } else {
                    $stmt->bindValue($key, $value, PDO::PARAM_INT);
                }
            }

            $stmt->execute();

            $deps = $stmt->fetchAll(PDO::FETCH_OBJ);

            if (!empty($deps)) {
                http_response_code(200);
                echo json_encode([
                    'drive' => $deps
                ], JSON_UNESCAPED_UNICODE);
            } else {
                http_response_code(404);
                echo json_encode([
                    'drive' => 'Nije pronađena vožnja!'
                ], JSON_UNESCAPED_UNICODE);
            }
        } catch (PDOException $e) {
            Logger::error('Failed to fetch departures by filter in getByFilter()', [
                'code' => $this->code ?? null,
                'driver_id' => $this->driver_id ?? null,
                'tour_id' => $this->tour_id ?? null,
                'date' => $this->date ?? null,
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
}

?>