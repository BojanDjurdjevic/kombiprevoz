<?php

namespace Models;

use PDO;

class City {
    private $id;
    private $country_id;
    private $name;
    private $db;

    public function __construct($db) 
    {
        $this->db = $db;
    }

    public function getAll() {
        $sql = "SELECT * from cities";
        $res = $this->db->query($sql);
        $num = $res->rowCount();

        if($num > 0) {
            $allCities = [];

            while($row = $res->fetch(PDO::FETCH_OBJ)) {
                array_push($allCities, $row);
            }

            echo json_encode(['cities' => $allCities], JSON_PRETTY_PRINT);
        } else {
            echo json_encode(['cities' => 'Nema pronađenih gradova.']);
        }

    }

    public function getByCountry($id) {
        $sql = "SELECT * from cities WHERE country_id = '$id'";
        //$stmt = $this->db->prepare($sql);
        //$res = $stmt->execute();
        $res = $this->db->query($sql);
        $num = $res->rowCount();

        if($num > 0) {
            $citiesByCountry = [];

            while($row = $res->fetch(PDO::FETCH_OBJ)) {
                array_push($citiesByCountry, $row);
            }

            echo json_encode(['cities' => $citiesByCountry], JSON_PRETTY_PRINT);
        } else {
            echo json_encode(['cities' => 'Nema pronađenih gradova.']);
        }
    }

    public function getOne($id) {
        $sql = "SELECT * from cities WHERE cities.id = '$id'";
        $res = $this->db->query($sql);
        $num = $res->rowCount();

        if($num == 1) {
            echo json_encode(['cities' => $res->fetch(PDO::FETCH_OBJ)], JSON_PRETTY_PRINT);
        } else {
            echo json_encode(['cities' => 'Nije pronađen traženi grad.']);
        }
    }
}


?>