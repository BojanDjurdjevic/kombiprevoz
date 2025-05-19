<?php

namespace Models;

use PDO;

class Tour {
    public $id;
    public $from_city;
    public $to_city;
    public $departures;
    public $time;
    public $duration;
    public $price;
    public $seats;
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

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

    public function getOne() {

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
            echo json_encode(['msg' => "Vožnja od '$this->from_city' do '$this->to_city' je uspešno dodata."], JSON_PRETTY_PRINT);
        } else
            echo json_encode(['msg' => "Vožnja od '$this->from_city' do '$this->to_city je uspešno dodata."], JSON_PRETTY_PRINT);
    }

    public function update()
    {
        $sql = "UPDATE tours SET
        from_city = :from_city, to_city = :to_city, departures = :departures,
        time = :time, duration = :duration, price = :price, seats = :seats
        WHERE id = :id
        ";
        $stmt = $this->db->prepare($sql);

        $this->from_city = htmlspecialchars(strip_tags($this->id));
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