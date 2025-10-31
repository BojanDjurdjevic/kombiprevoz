<?php

namespace Models;

use PDO;
use PDOException;

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
            $countries = [];

            while($row = $result->fetch(PDO::FETCH_OBJ)) {
                array_push($countries, $row);
            }

            echo json_encode(['drzave' => $countries], JSON_PRETTY_PRINT);

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
        if(!isset($this->flag)) {
            http_response_code(422);
            echo json_encode(['error' => 'Slika nije poslata! Molimo Vas da pošaljete zastavu države.'], JSON_PRETTY_PRINT);
            exit();
        }

        $file = $this->flag;

        $allowed = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
        if (!in_array($file->type, $allowed)) {
            echo json_encode(['error' => 'Nepodržan tip fajla.']);
            return;
        }

        if ($file->size > 5 * 1024 * 1024) { // max 5MB
            echo json_encode(['error' => 'Fajl je prevelik! Molimo vas da smanjite sliku pre unosa.']);
            return;
        }

        $targetDir = __DIR__ . '/../assets/img/countries/';
        if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);

        $newName = $this->name . '.' . pathinfo($file->name, PATHINFO_EXTENSION);
        $targetFile = $targetDir . $newName;
        $flagPath = 'src/assets/img/countries/' . $newName;

        if (move_uploaded_file($file->tmp_name, $targetFile)) {
            $sql = "INSERT INTO countries 
                SET name = :name, file_path = :flag"
            ;
            $stmt = $this->db->prepare($sql);

            $this->name = htmlspecialchars(strip_tags($this->name));
            $stmt->bindParam(':name', $this->name);
            $stmt->bindParam(':flag', $flagPath);

            try {
                $stmt->execute();
                http_response_code(200);
                echo json_encode([
                    'success' => true,
                    'msg' => 'Uspešno ste dodali novu državu!',
                    'file' => $newName,
                    'path' => $flagPath
                ], JSON_PRETTY_PRINT);
                exit();

            } catch (PDOException $e) {
                echo json_encode([
                    'error' => 'Došlo je do greške pri konekciji na bazu!',
                    'msg' => $e->getMessage()
                ], JSON_PRETTY_PRINT);
            }
        } else {
            echo json_encode(['error' => 'Došlo je do greške pri snimanju fajla.'], JSON_PRETTY_PRINT);
            return;
        }
        
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