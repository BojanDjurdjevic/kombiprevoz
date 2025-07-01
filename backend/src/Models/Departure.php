<?php

namespace Models;

use PDO;
use PDOException;

class Departure {
    public $id;
    public $driver_id;
    public $dep_orders;
    public $code;
    public $path;
    public $time;

    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function getAll() 
    {
        $sql = "SELECT * FROM departures WHERE deleted = 0";

        try {
            $res = $this->db->query($sql);
            $num = $res->rowCount();

            if($num > 0) {
                $departures = [];
                while($row = $res->fetch(PDO::FETCH_OBJ)) {
                    array_push($departures, $row);
                }
                echo json_encode([
                    'departures' => $departures
                ], JSON_PRETTY_PRINT);
            }
        } catch (PDOException $e) {
            echo json_encode([
                'departure' => 'Došlo je do problema pri konekciji na bazu!',
                'msg' => $e->getMessage()
            ], JSON_PRETTY_PRINT);
        }
    }

    public function getByDriver()
    {
        $sql = "SELECT * from departures WHERE driver_id = :driver_id";
        $stmt = $this->db->prepare($sql);
        $this->driver_id = htmlspecialchars(strip_tags($this->driver_id));
        $stmt->bindParam(':driver_id', $this->driver_id);

        $departures = [];

        try {
            if($stmt->execute()) {
                $dep = $stmt->fetch(PDO::FETCH_OBJ);
                if($dep) {
                    while($dep) {
                        $ords = [];
                        $ord_ids = explode(",", $dep->dep_orders);
                        foreach($ord_ids as $id) {
                            $query = "
                                SELECT orders.id, orders.tour_id, orders.user_id, orders.places, tours.from_city, 
                                orders.add_from as pickup, tours.to_city, orders.add_to as dropoff,
                                orders.date, tours.time as pickuptime, tours.duration,
                                orders.total as price, orders.code, orders.file_path as voucher, users.name as user, users.email, users.phone
                                from orders 
                                INNER JOIN tours on orders.tour_id = tours.id
                                INNER JOIN users on orders.user_id = users.id
                                WHERE orders.id = $id
                            ";
                            $res = $this->db->query($query);
                            while($order = $res->fetch(PDO::FETCH_OBJ)) {
                                array_push($ords, $order);
                            }   
                        }
                        array_push($departures, [
                            'departure' => $dep,
                            'orders' => $ords
                        ]);
                    }
                    echo json_encode([
                        'departures' => $departures
                    ], JSON_PRETTY_PRINT); 
                } else {
                   echo json_encode([
                        'departure' => 'Nemate nijednu dodeljenu vožnju!',
                    ], JSON_PRETTY_PRINT); 
                }
            }
        } catch (PDOException $e) {
            echo json_encode([
                'departure' => 'Došlo je do greške pri konekciji na bazu!',
                'msg' => $e->getMessage()
            ], JSON_PRETTY_PRINT);
        }
    }
}
?>