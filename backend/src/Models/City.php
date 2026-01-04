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

class City {
    public ?int $id = null;
    public ?int $country_id = null;
    public ?string $name = null;
    public ?array $photos = null;
    public ?int $photo_id = null;
    
    private PDO $db;
    private const UPLOAD_DIR = __DIR__ . '/../assets/img/cities/';
    private const PUBLIC_PATH = 'src/assets/img/cities/';
    private const ALLOWED_TYPES = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
    private const MAX_FILE_SIZE = 6 * 1024 * 1024; // 6MB

    public function __construct(PDO $db) 
    {
        $this->db = $db;
    }

    // ======================== GET METHODS ========================

    /**
     * Dobijanje svih gradova sa slikama
     */
    public function getAll(): void
    {
        $sql = "SELECT 
                    cities.id, 
                    cities.name, 
                    cities.country_id,
                    cities.deleted,
                    t_pics.id as pic_id, 
                    t_pics.file_path
                FROM cities
                LEFT JOIN t_pics ON t_pics.city_id = cities.id AND t_pics.deleted = 0
                WHERE cities.deleted = 0
                ORDER BY cities.name ASC";
        
        try {
            $res = $this->db->query($sql);
            $rows = $res->fetchAll(PDO::FETCH_OBJ);

            $allCities = [];

            foreach ($rows as $row) {
                $cityID = (int) $row->id;

                if (!isset($allCities[$cityID])) {
                    $allCities[$cityID] = [
                        'id' => $cityID,
                        'name' => $row->name,
                        'country_id' => (int) $row->country_id,
                        'deleted' => (int) $row->deleted,
                        'pictures' => [] 
                    ];
                }

                if ($row->pic_id && $row->file_path) {
                    $allCities[$cityID]['pictures'][] = [
                        'pic_id' => (int) $row->pic_id,
                        'file_path' => $row->file_path
                    ];
                }                
            }

            http_response_code(200);
            echo json_encode([
                'cities' => array_values($allCities)
            ], JSON_UNESCAPED_UNICODE);
            
        } catch (PDOException $e) {
            Logger::error('Failed to fetch all cities in getAll()', [
                'error' => $e->getMessage(),
                'file' => __FILE__,
                'line' => __LINE__
            ]);
            
            http_response_code(500);
            echo json_encode([
                'error' => 'Došlo je do greške pri učitavanju gradova!'
            ], JSON_UNESCAPED_UNICODE);
        }
    }

    /**
     * Dobijanje gradova po ID-u države (sa slikama)
     */
    public function getByCountryId(): void
    {
        if (empty($this->country_id)) {
            http_response_code(400);
            echo json_encode([
                'error' => 'ID države je obavezan'
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        $sql = "SELECT 
                    cities.id, 
                    cities.name, 
                    cities.country_id,
                    cities.deleted,
                    t_pics.id as pic_id, 
                    t_pics.file_path
                FROM cities
                LEFT JOIN t_pics ON t_pics.city_id = cities.id AND t_pics.deleted = 0
                WHERE cities.country_id = :country_id 
                AND cities.deleted = 0
                ORDER BY cities.name ASC";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':country_id', $this->country_id, PDO::PARAM_INT);
            $stmt->execute();
            
            $rows = $stmt->fetchAll(PDO::FETCH_OBJ);
            $citiesByCountry = [];

            foreach ($rows as $row) {
                $cityID = (int) $row->id;

                if (!isset($citiesByCountry[$cityID])) {
                    $citiesByCountry[$cityID] = [
                        'id' => $cityID,
                        'name' => $row->name,
                        'country_id' => (int) $row->country_id,
                        'deleted' => (int) $row->deleted,
                        'pictures' => [] 
                    ];
                }

                if ($row->pic_id && $row->file_path) {
                    $citiesByCountry[$cityID]['pictures'][] = [
                        'pic_id' => (int) $row->pic_id,
                        'file_path' => $row->file_path
                    ];
                }
            }

            http_response_code(200);
            echo json_encode([
                'cities' => array_values($citiesByCountry)
            ], JSON_UNESCAPED_UNICODE);
            
        } catch (PDOException $e) {
            Logger::error('Failed to fetch cities by country in getByCountryId()', [
                'country_id' => $this->country_id,
                'error' => $e->getMessage(),
                'file' => __FILE__,
                'line' => __LINE__
            ]);
            
            http_response_code(500);
            echo json_encode([
                'error' => 'Došlo je do greške pri učitavanju gradova!'
            ], JSON_UNESCAPED_UNICODE);
        }
    }

    /**
     * Dobijanje svih gradova po državi (uključujući obrisane slike) - admin view
     */
    public function getFullCitiesByCountryId(): void
    {
        if (empty($this->country_id)) {
            http_response_code(400);
            echo json_encode([
                'error' => 'ID države je obavezan'
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        $sql = "SELECT 
                    cities.id as city_id, 
                    cities.name as city_name, 
                    cities.deleted as deleted_city,
                    t_pics.id as photo_id, 
                    t_pics.file_path as city_photo_path, 
                    t_pics.deleted 
                FROM cities
                LEFT JOIN t_pics ON cities.id = t_pics.city_id
                WHERE cities.country_id = :id
                ORDER BY cities.name ASC";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':id', $this->country_id, PDO::PARAM_INT);
            $stmt->execute();

            $rows = $stmt->fetchAll(PDO::FETCH_OBJ);
            $cities = [];

            foreach ($rows as $row) {
                $cId = (int) $row->city_id;

                if (!isset($cities[$cId])) {
                    $cities[$cId] = [
                        'city_id' => $cId,
                        'name' => $row->city_name,
                        'deleted_city' => (int) $row->deleted_city,
                        'pictures' => []
                    ];
                }

                if ($row->photo_id) {
                    $cities[$cId]['pictures'][] = [
                        'photo_id' => (int) $row->photo_id,
                        'file_path' => $row->city_photo_path,
                        'deleted' => (int) $row->deleted
                    ];
                }
            }

            http_response_code(200);
            echo json_encode([
                'cities' => array_values($cities),
                'has_cities' => !empty($cities)
            ], JSON_UNESCAPED_UNICODE);
            
        } catch (PDOException $e) {
            Logger::error('Failed to fetch full cities in getFullCitiesByCountryId()', [
                'country_id' => $this->country_id,
                'error' => $e->getMessage(),
                'file' => __FILE__,
                'line' => __LINE__
            ]);
            
            http_response_code(500);
            echo json_encode([
                'error' => 'Došlo je do greške pri konekciji na bazu podataka!'
            ], JSON_UNESCAPED_UNICODE);
        }
    }

    /**
     * Dobijanje jednog grada po ID-u
     */
    public function getOne(): void
    {
        if (empty($this->id)) {
            http_response_code(400);
            echo json_encode([
                'error' => 'ID grada je obavezan'
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        $sql = "SELECT 
                    cities.id, 
                    cities.name, 
                    cities.country_id,
                    cities.deleted,
                    t_pics.id as pic_id, 
                    t_pics.file_path
                FROM cities
                LEFT JOIN t_pics ON t_pics.city_id = cities.id AND t_pics.deleted = 0
                WHERE cities.id = :id
                LIMIT 1";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':id', $this->id, PDO::PARAM_INT);
            $stmt->execute();
            
            $rows = $stmt->fetchAll(PDO::FETCH_OBJ);

            if (empty($rows)) {
                http_response_code(404);
                echo json_encode([
                    'error' => 'Grad nije pronađen'
                ], JSON_UNESCAPED_UNICODE);
                return;
            }

            $city = [
                'id' => (int) $rows[0]->id,
                'name' => $rows[0]->name,
                'country_id' => (int) $rows[0]->country_id,
                'deleted' => (int) $rows[0]->deleted,
                'pictures' => []
            ];

            foreach ($rows as $row) {
                if ($row->pic_id && $row->file_path) {
                    $city['pictures'][] = [
                        'pic_id' => (int) $row->pic_id,
                        'file_path' => $row->file_path
                    ];
                }
            }

            http_response_code(200);
            echo json_encode([
                'city' => $city
            ], JSON_UNESCAPED_UNICODE);
            
        } catch (PDOException $e) {
            Logger::error('Failed to fetch city in getOne()', [
                'id' => $this->id,
                'error' => $e->getMessage(),
                'file' => __FILE__,
                'line' => __LINE__
            ]);
            
            http_response_code(500);
            echo json_encode([
                'error' => 'Došlo je do greške pri učitavanju grada!'
            ], JSON_UNESCAPED_UNICODE);
        }
    }

    // ======================== CREATE METHOD ========================

    /**
     * Kreiranje novog grada sa slikama
     */
    public function create(): void
    {
        // Validacija imena
        if (empty($this->name)) {
            http_response_code(400);
            echo json_encode([
                'error' => 'Ime grada je obavezno'
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        // Validacija country_id
        if (empty($this->country_id)) {
            http_response_code(400);
            echo json_encode([
                'error' => 'ID države je obavezan'
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        // Provera da li grad već postoji
        if ($this->cityExists()) {
            http_response_code(409);
            echo json_encode([
                'error' => 'Grad sa ovim imenom već postoji u ovoj državi!'
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        // Insert grada
        $sql = "INSERT INTO cities SET name = :name, country_id = :country_id, deleted = 0";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':name', $this->name, PDO::PARAM_STR);
            $stmt->bindParam(':country_id', $this->country_id, PDO::PARAM_INT);
            $stmt->execute();

            $cityID = (int) $this->db->lastInsertId();

            // Upload slika ako postoje
            $uploadedCount = 0;
            if (!empty($this->photos) && is_array($this->photos)) {
                foreach ($this->photos as $file) {
                    $uploadResult = $this->uploadPhoto($file, $cityID);
                    if ($uploadResult['success']) {
                        $uploadedCount++;
                    }
                }
            }

            http_response_code(201);
            echo json_encode([
                'success' => true,
                'msg' => 'Uspešno ste dodali novi grad!',
                'city_id' => $cityID,
                'photos_uploaded' => $uploadedCount
            ], JSON_UNESCAPED_UNICODE);
            
        } catch (PDOException $e) {
            Logger::error('Failed to create city in create()', [
                'name' => $this->name,
                'country_id' => $this->country_id,
                'error' => $e->getMessage(),
                'file' => __FILE__,
                'line' => __LINE__
            ]);
            
            http_response_code(500);
            echo json_encode([
                'error' => 'Došlo je do greške pri dodavanju grada!'
            ], JSON_UNESCAPED_UNICODE);
        }
    }

    // ======================== UPDATE METHOD ========================

    /**
     * Dodavanje novih slika postojećem gradu
     */
    public function update(): void
    {
        if (empty($this->id)) {
            http_response_code(400);
            echo json_encode([
                'error' => 'ID grada je obavezan'
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        if (empty($this->photos) || !is_array($this->photos)) {
            http_response_code(400);
            echo json_encode([
                'error' => 'Niste poslali slike!'
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        $uploadedCount = 0;
        $errors = [];

        foreach ($this->photos as $file) {
            $uploadResult = $this->uploadPhoto($file, $this->id);
            
            if ($uploadResult['success']) {
                $uploadedCount++;
            } else {
                $errors[] = $uploadResult['error'];
            }
        }

        if ($uploadedCount > 0) {
            http_response_code(200);
            echo json_encode([
                'success' => true,
                'msg' => "Uspešno ste dodali $uploadedCount slika gradu!",
                'photos_uploaded' => $uploadedCount,
                'errors' => $errors
            ], JSON_UNESCAPED_UNICODE);
        } else {
            http_response_code(400);
            echo json_encode([
                'error' => 'Nijedna slika nije uspešno uploadovana',
                'errors' => $errors
            ], JSON_UNESCAPED_UNICODE);
        }
    }

    // ======================== DELETE METHODS ========================

    /**
     * Soft delete jedne slike grada
     */
    private function deleteCityPicture(int $id, bool $delete): bool
    {
        $sql = "UPDATE t_pics SET deleted = :deleted WHERE id = :id";
        $deletedValue = $delete ? 1 : 0;
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(":id", $id, PDO::PARAM_INT);
            $stmt->bindParam(":deleted", $deletedValue, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->rowCount() > 0;
            
        } catch (PDOException $e) {
            Logger::error("Failed to delete city picture", [
                'id' => $id,
                'delete' => $delete,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Bulk delete/restore slika grada
     */
    public function deleteCityPics(bool $delete): void
    {     
        if (empty($this->photos) || !is_array($this->photos)) {
            http_response_code(400);
            echo json_encode([
                'error' => 'Nije poslata nijedna slika za brisanje!'
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        $picNum = count($this->photos);
        $deletedCount = 0;
        $failedIds = [];

        foreach ($this->photos as $photo) {
            $photoId = filter_var($photo, FILTER_VALIDATE_INT);
            
            if ($photoId === false) {
                $failedIds[] = $photo;
                continue;
            }
            
            if ($this->deleteCityPicture($photoId, $delete)) {
                $deletedCount++;
            } else {
                $failedIds[] = $photo;
            }
        }

        if ($deletedCount === $picNum) {
            // Srpska gramatika za brojeve 😊
            if ($picNum == 1) {
                $pic = 'fotografiju';
            } elseif ($picNum >= 2 && $picNum <= 4) {
                $pic = 'fotografije';
            } else {
                $pic = 'fotografija';
            }

            $action = $delete ? 'obrisali' : 'aktivirali';

            http_response_code(200);
            echo json_encode([
                'success' => true,
                'msg' => "Uspešno ste $action $picNum $pic grada"
            ], JSON_UNESCAPED_UNICODE);
            
        } else {
            http_response_code(500);
            echo json_encode([
                'error' => 'Neke slike nisu obrisane',
                'deleted' => $deletedCount,
                'failed' => $failedIds
            ], JSON_UNESCAPED_UNICODE);
        }
    }

    /**
     * Soft delete grada
     */
    public function delete(): void
    {
        if (empty($this->id)) {
            http_response_code(400);
            echo json_encode([
                'error' => 'ID grada je obavezan'
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        $sql = "UPDATE cities SET deleted = 1 WHERE id = :id";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':id', $this->id, PDO::PARAM_INT);
            $stmt->execute();
            
            http_response_code(200);
            echo json_encode([
                'success' => true,
                'msg' => 'Grad je uspešno deaktiviran.'
            ], JSON_UNESCAPED_UNICODE);
            
        } catch (PDOException $e) {
            Logger::error('Failed to delete city in delete()', [
                'id' => $this->id,
                'error' => $e->getMessage(),
                'file' => __FILE__,
                'line' => __LINE__
            ]);
            
            http_response_code(500);
            echo json_encode([
                'error' => 'Trenutno nije moguće deaktivirati ovaj grad.'
            ], JSON_UNESCAPED_UNICODE);
        }
    }

    /**
     * Restore grada
     */
    public function restore(): void
    {
        if (empty($this->id)) {
            http_response_code(400);
            echo json_encode([
                'error' => 'ID grada je obavezan'
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        $sql = "UPDATE cities SET deleted = 0 WHERE id = :id";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':id', $this->id, PDO::PARAM_INT);
            $stmt->execute();
            
            http_response_code(200);
            echo json_encode([
                'success' => true,
                'msg' => 'Grad je uspešno aktiviran.'
            ], JSON_UNESCAPED_UNICODE);
            
        } catch (PDOException $e) {
            Logger::error('Failed to restore city in restore()', [
                'id' => $this->id,
                'error' => $e->getMessage(),
                'file' => __FILE__,
                'line' => __LINE__
            ]);
            
            http_response_code(500);
            echo json_encode([
                'error' => 'Trenutno nije moguće aktivirati ovaj grad.'
            ], JSON_UNESCAPED_UNICODE);
        }
    }

    // ======================== HELPER METHODS ========================

    /**
     * Upload jedne slike
     */
    private function uploadPhoto(object $file, int $cityId): array
    {
        // Validacija upload greške
        if ($file->error !== UPLOAD_ERR_OK) {
            return [
                'success' => false,
                'error' => 'Greška pri upload-u fajla'
            ];
        }

        // Validacija tipa
        if (!in_array($file->type, self::ALLOWED_TYPES)) {
            return [
                'success' => false,
                'error' => 'Nepodržan tip fajla'
            ];
        }

        // Validacija veličine
        if ($file->size > self::MAX_FILE_SIZE) {
            return [
                'success' => false,
                'error' => 'Fajl je prevelik (max 6MB)'
            ];
        }

        // Kreiranje direktorijuma
        if (!is_dir(self::UPLOAD_DIR)) {
            mkdir(self::UPLOAD_DIR, 0755, true);
        }

        // Generisanje unique imena
        $extension = pathinfo($file->name, PATHINFO_EXTENSION);
        $fileName = uniqid('city_', true) . '.' . $extension;
        $path = self::UPLOAD_DIR . $fileName;
        $picturePath = self::PUBLIC_PATH . $fileName;

        // Move uploaded file
        if (!move_uploaded_file($file->tmp_name, $path)) {
            return [
                'success' => false,
                'error' => 'Greška pri snimanju fajla'
            ];
        }

        // Set permissions
        chmod($path, 0644);

        // Insert u bazu
        try {
            $picSql = "INSERT INTO t_pics SET file_path = :file_path, city_id = :city_id, deleted = 0";
            $stmtPic = $this->db->prepare($picSql);
            $stmtPic->bindParam(':file_path', $picturePath, PDO::PARAM_STR);
            $stmtPic->bindParam(':city_id', $cityId, PDO::PARAM_INT);
            $stmtPic->execute();
            
            return [
                'success' => true,
                'file' => $fileName,
                'path' => $picturePath
            ];
            
        } catch (PDOException $e) {
            // Brisanje fajla ako insert ne uspe
            if (file_exists($path)) {
                unlink($path);
            }
            
            Logger::error('Failed to insert photo to database', [
                'city_id' => $cityId,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'error' => 'Greška pri snimanju u bazu'
            ];
        }
    }

    /**
     * Provera da li grad već postoji
     */
    private function cityExists(): bool
    {
        $sql = "SELECT id FROM cities 
                WHERE name = :name 
                AND country_id = :country_id 
                AND deleted = 0 
                LIMIT 1";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':name', $this->name, PDO::PARAM_STR);
            $stmt->bindParam(':country_id', $this->country_id, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            Logger::error('Failed to check if city exists', [
                'name' => $this->name,
                'country_id' => $this->country_id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}

?>