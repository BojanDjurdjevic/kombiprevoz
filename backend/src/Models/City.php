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
        $sql = "SELECT cities.*, t_pics.id as pic_id, t_pics.file_path from cities
                LEFT JOIN t_pics ON t_pics.city_id = cities.id
        ";
        $res = $this->db->query($sql);
        $num = $res->rowCount();

        if($num > 0) {
            $allCities = [];
            $rows = $res->fetchAll(PDO::FETCH_OBJ);

            foreach($rows as $row) {
                $cityID = $row->id;

                if(!isset($allCities[$cityID])) {
                    $city = [
                        'id' => $row->id,
                        'name' => $row->name,
                        'country_id' => $row->country_id,
                        'pictures' => [] 
                    ];
                }

                if($row->pic_id && $row->file_path) {
                    $allCities[$cityID]['pictures'][] = [
                        'pic_id' => $row->pic_id,
                        'file_path' => $row->file_path
                    ];
                }                
            }

            echo json_encode(['cities' => array_values($allCities)], JSON_PRETTY_PRINT);
        } else {
            echo json_encode(['cities' => []]);
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

    public function getFullCitiesByCountryId()
    {
        $sql = "SELECT countries.*, cities.id as city_id, cities.name as city_name,
                t_pics.id as photo_id, t_pics.file_path as city_photo_path, t_pics.deleted 
                from countries
                INNER JOIN cities ON cities.country_id = countries.id
                INNER JOIN t_pics ON t_pics.city_id = cities.id
                WHERE countries.id = :id AND t_pics.deleted = 0"
        ;
    }

    public function byID() {
        $sql = "SELECT cities.*, t_pics.id as pic_id, t_pics.file_path from cities
                LEFT JOIN t_pics ON t_pics.city_id = cities.id
                WHERE cities.country_id = '$this->country_id'
        ";
        $res = $this->db->query($sql);
        $num = $res->rowCount();

        if($num > 0) {
            $allCities = [];
            $rows = $res->fetchAll(PDO::FETCH_OBJ);

            foreach($rows as $row) {
                $cityID = $row->id;

                if(!isset($allCities[$cityID])) {
                    $allCities[$cityID] = [
                        'id' => $row->id,
                        'name' => $row->name,
                        'country_id' => $row->country_id,
                        'pictures' => [] 
                    ];
                }

                if($row->pic_id && $row->file_path) {
                    $allCities[$cityID]['pictures'][] = [
                        'pic_id' => $row->pic_id,
                        'file_path' => $row->file_path
                    ];
                }                
            }

            echo json_encode(['cities' => array_values($allCities)], JSON_PRETTY_PRINT);
        } else {
            echo json_encode(['cities' => []]);
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
                    if($file->error == UPLOAD_ERR_OK) {
                        $extension = pathinfo($file->name, PATHINFO_EXTENSION);
                        $fileName = uniqid('city_', true) . '.' . $extension;
                        $path = __DIR__ . '/../assets/img/cities/' . $fileName;
                        $picture_path = 'src/assets/img/cities/' . $fileName;
                    }
                    // Check the size of file
                    if ($file->size > 6 * 1024 * 1024) { // max 6MB
                        http_response_code(422);
                        echo json_encode([
                            'error' => 'Fajl je prevelik! Molimo vas da smanjite sliku pre unosa.',
                            'file' => $file
                        ], JSON_PRETTY_PRINT);
                        return;
                    }
                    // From TMP folder to folder on back
                    if(move_uploaded_file($file->tmp_name, $path)) {
                        // Store the pics to DB
                        $picSql = "INSERT INTO t_pics SET file_path = :file_path, city_id = :city_id, deleted = 0";
                        $stmtPic = $this->db->prepare($picSql);
                        $stmtPic->bindParam(':file_path', $picture_path);
                        $stmtPic->bindParam(':city_id', $cityID);
                        $stmtPic->execute();
                        
                    } else {
                        http_response_code(422);
                        echo json_encode([
                            'success' => false,
                            'error' => 'Došlo je do greške prilikom podizanja slika!'
                        ], JSON_PRETTY_PRINT);
                    }
                }
                echo json_encode([
                    'success' => true,
                    'msg' => 'Uspešno ste dodali novi grad!'
                ], JSON_PRETTY_PRINT);
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