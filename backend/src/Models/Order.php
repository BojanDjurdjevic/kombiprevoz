<?php

namespace Models;

use PDO;

class Order {
    public $id;
    public $tour_id;
    public $user_id;
    public $places;
    public $add_from;
    public $add_to;
    public $date;
    public $price;
    public $deleted;
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function getAll() 
    {
        $sql = "SELECT orders.id, orders.places, tours.from_city, 
                orders.add_from as pickup, tours.to_city, orders.add_to as dropoff,
                orders.date, tours.time as pickuptime, tours.duration,
                orders.total as price, users.name as user, users.email, users.phone
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
        $sql = "SELECT orders.id, orders.places, tours.from_city, 
                orders.add_from as pickup, tours.to_city, orders.add_to as dropoff,
                orders.date, tours.time as pickuptime, tours.duration,
                orders.total as price, users.name as user, users.email, users.phone
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
            $sql = "SELECT orders.id, orders.places, tours.from_city, 
                orders.add_from as pickup, tours.to_city, orders.add_to as dropoff,
                orders.date, tours.time as pickuptime, tours.duration,
                orders.total as price, users.name as user, users.email, users.phone
                from orders 
                INNER JOIN tours on orders.tour_id = tours.id
                INNER JOIN users on orders.user_id = users.id
                WHERE orders.date >= '$from' AND orders.date <= '$to' AND orders.deleted = 0
                ORDER BY orders.date"
            ;
        } elseif(isset($from) && !isset($to)) {
            $sql = "SELECT orders.id, orders.places, tours.from_city, 
                orders.add_from as pickup, tours.to_city, orders.add_to as dropoff,
                orders.date, tours.time as pickuptime, tours.duration,
                orders.total as price, users.name as user, users.email, users.phone
                from orders 
                INNER JOIN tours on orders.tour_id = tours.id
                INNER JOIN users on orders.user_id = users.id
                WHERE orders.date >= '$from' AND orders.deleted = 0
                ORDER BY orders.date"
            ;
        } elseif(!isset($from) && isset($to)) {
            $sql = "SELECT orders.id, orders.places, tours.from_city, 
                orders.add_from as pickup, tours.to_city, orders.add_to as dropoff,
                orders.date, tours.time as pickuptime, tours.duration,
                orders.total as price, users.name as user, users.email, users.phone
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
        $sql = "SELECT orders.id, orders.places, tours.from_city, 
                orders.add_from as pickup, tours.to_city, orders.add_to as dropoff,
                orders.date, tours.time as pickuptime, tours.duration,
                orders.total as price, users.name as user, users.email, users.phone
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
        } 
    }

    public function getByTour() {
        $sql = "SELECT orders.id, orders.places, tours.from_city, 
                orders.add_from as pickup, tours.to_city, orders.add_to as dropoff,
                orders.date, tours.time as pickuptime, tours.duration,
                orders.total as price, users.name as user, users.email, users.phone
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
        $sql = "SELECT orders.id, orders.places, tours.from_city, 
                orders.add_from as pickup, tours.to_city, orders.add_to as dropoff,
                orders.date, tours.time as pickuptime, tours.duration,
                orders.total as price, users.name as user, users.email, users.phone
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

    public function availability() {
        $sql = "SELECT orders.places, tours.seats from orders 
                INNER JOIN tours on tours.id = orders.tour_id
                WHERE orders.date = '$this->date'
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

    public function create() 
    {
        if($this->places <= $this->availability()) {
            $sql = "INSERT INTO orders SET
                    tour_id = :tour_id, user_id = :user_id, places = :places,
                    add_from = :add_from, add_to = :add_to, date = :date, total = :price
            ";
            $stmt = $this->db->prepare($sql);

            $this->tour_id = htmlspecialchars(strip_tags($this->tour_id));
            $this->user_id = htmlspecialchars(strip_tags($this->user_id));
            $this->places = htmlspecialchars(strip_tags($this->places));
            $this->add_from = htmlspecialchars(strip_tags($this->add_from));
            $this->add_to = htmlspecialchars(strip_tags($this->add_to));
            $this->date = htmlspecialchars(strip_tags($this->date));
            $this->price = htmlspecialchars(strip_tags($this->price));

            $stmt->bindParam(':tour_id', $this->tour_id);
            $stmt->bindParam(':user_id', $this->user_id);
            $stmt->bindParam(':places', $this->places);
            $stmt->bindParam(':add_from', $this->add_from);
            $stmt->bindParam(':add_to', $this->add_to);
            $stmt->bindParam(':date', $this->date);
            $stmt->bindParam(':price', $this->price);

            if($stmt->execute()) {
                echo json_encode(['msg' => 'Uspešno ste rezervisali vožnju.'], JSON_PRETTY_PRINT);
            }
            else echo json_encode(['msg' => 'Trenutno nije moguće rezervisati ovu vožnju.'], JSON_PRETTY_PRINT);
        } else
        echo json_encode(['msg' => 'Žao nam je, ali nema više slobodnih mesta za ovu vožnju.']);
    }

    public function update()
    {

    }

    public function delete()
    {

    }
}

?>