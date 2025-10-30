<?php

namespace Models;

use PDO;
use PDOException;

class City {
    public $id;
    public $country_id;
    public $name;
    public $photos;
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

    public function create()
    {
        $sql = "INSERT INTO cities
                SET name = :name, country_id = :country_id
        ";
        $stmt = $this->db->prepare($sql);

        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->country_id = htmlspecialchars(strip_tags($this->country_id));

        $stmt->bindParam(':name', $this->name);
        $stmt->bindParam(':country_id', $this->country_id);

        try {
            $stmt->execute();

            $cityID = $this->db->lastInsertId();

            // if photos sent
            if(!empty($this->photos) && is_array($this->photos)) {
                foreach($this->photos as $file) {
                    // Check if the pics are valid:
                    if($file['error'] == UPLOAD_ERR_OK) {
                        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                        $fileName = uniqid('city_', true) . '.' . $extension;
                        $path = __DIR__ . '/../../assets/img/cities/' . $fileName;
                    }
                    // From TMP folder to folder on back
                    if(move_uploaded_file($file['tmp_name'], $path)) {
                        // Store the pics to DB
                        $picSql = "INSERT INTO t_pics SET file_path = :file_path, city_id = :city_id, deleted = 0";
                        $stmtPic = $this->db->prepare($picSql);
                        $stmtPic->bindParam(':file_path', $fileName);
                        $stmtPic->bindParam(':city_id', $cityID);
                        
                    } else {
                        http_response_code(422);
                        echo json_encode([
                            'success' => false,
                            'error' => 'Došlo je do greške prilikom podizanja slika!'
                        ]. JSON_PRETTY_PRINT);
                    }
                }
                echo json_encode([
                    'success' => true,
                    'msg' => 'Uspešno ste dodali novi grad!'
                ]. JSON_PRETTY_PRINT);
            } 
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode([
                'error' => 'Došlo je do greške pri konekciji na bazu!',
                'msg' => $e->getMessage()
            ], JSON_PRETTY_PRINT);
        }
        
    }

    public function update() 
    {
        $sql = "UPDATE cities
                SET name = :name, country_id = :country_id
                WHERE id = :id
        ";
        $stmt = $this->db->prepare($sql);

        $this->id = htmlspecialchars(strip_tags($this->id));
        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->country_id = htmlspecialchars(strip_tags($this->country_id));

        $stmt->bindParam(':id', $this->id);
        $stmt->bindParam(':name', $this->name);
        $stmt->bindParam(':country_id', $this->country_id);

        if($stmt->execute()) {
            echo json_encode(['msg' => 'Grad je uspešno izmenjen.']);
        } else
        echo json_encode(['msg' => 'Trenutno nije moguže izmeniti grad.']);
    }

    public function delete() {
        $sql = "DELETE from cities WHERE cities.id = :id";
        $stmt = $this->db->prepare($sql);

        $this->id = htmlspecialchars(strip_tags($this->id));
        $stmt->bindParam(':id', $this->id);

        if($stmt->execute()) {
            echo json_encode(
                ['msg' => 'Grad je obrisan.']
            );
        } else
        json_encode(['msg' => 'Trenutno nije moguće obrisati ovaj grad.']);
    }
}


?>