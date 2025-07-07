<?php

namespace Models;

use PDO;

class Country {
    public $id;
    public $name;
    public $flag;
    public $db;

    public function __construct($db) {
        $this->db = $db;
        //$this->db->connect();
    }

    public function getCountries() {
        $query = "SELECT * from countries";
        /*
        $stmt = $this->db->prepare($query);
        $result = $stmt->execute(); */

        $result = $this->db->query($query);
        $num = $result->rowCount();

        //$num = $result->num_rows();

        if($num > 0) {
            $count = [
                'Broj drzava' => $num
            ];
            $countries = [];

            while($row = $result->fetch(PDO::FETCH_OBJ)) {
                array_push($countries, $row);
            }

            echo json_encode([['Broj' => $count], ['Drzave' => $countries]], JSON_PRETTY_PRINT);

        } else {
            $msg = [
                "message" => 'Nije pronađena nijedna država'
            ];
            echo json_encode($msg, JSON_PRETTY_PRINT);
        }
    }

    public function getCountry($id) {
        $sql = "SELECT * from countries WHERE id = '$id'";
        $res = $this->db->query($sql);
        $num = $res->rowCount();

        if($num == 1) {
            echo json_encode($res->fetch(PDO::FETCH_OBJ), JSON_PRETTY_PRINT);
        } else
            echo json_encode(['Msg' => 'Nije pronadjena nijedna drzava', JSON_PRETTY_PRINT]);

    }

    public function create() {
        $sql = "INSERT INTO countries 
                SET name = :name"
        ;
        $stmt = $this->db->prepare($sql);

        $this->name = htmlspecialchars(strip_tags($this->name));
        $stmt->bindParam(':name', $this->name);

        if($stmt->execute()) {
            echo json_encode(
                ['message' => 'Nova država je dodata.']
            );
        } else
        json_encode(['message' => 'Trenutno nije moguće dodati državu']);
        
    }

    public function update() {
        $sql = "UPDATE countries 
                SET name = :name
                WHERE id = :id"
        ;
        $stmt = $this->db->prepare($sql);

        $this->id = htmlspecialchars(strip_tags($this->id));
        $this->name = htmlspecialchars(strip_tags($this->name));
        
        $stmt->bindParam(':id', $this->id);
        $stmt->bindParam(':name', $this->name);

        if($stmt->execute()) {
            echo json_encode(
                ['message' => 'Država je izmenjena.']
            );
        } else
        json_encode(['message' => 'Trenutno nije moguće izmeniti ovu državu']);
    }

    public function delete() {
        $sql = "DELETE from countries WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $this->id = htmlspecialchars(strip_tags($this->id));
        $stmt->bindParam(':id', $this->id);

        if($stmt->execute()) {
            echo json_encode(
                ['message' => 'Država je obrisana.']
            );
        } else
        json_encode(['message' => 'Trenutno nije moguće obrisati ovu državu']);
    }

}

?>