<?php

namespace Models;

use PDO;

class Country {
    public $id;
    public $name;
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

            while($row = $result->fetch(PDO::FETCH_ASSOC)) {
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

    public function addCountry() {
        $sql = "INSERT INTO countries 
                SET name = :name"
        ;
        $stmt = $this->db->prepare($sql);

        $this->name = htmlspecialchars(strip_tags($this->name));
        $stmt->bindParam(':name', $this->name);
        
    }

}

?>