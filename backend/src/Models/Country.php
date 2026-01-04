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

class Country {
    public ?int $id = null;
    public ?string $name = null;
    public ?object $flag = null;
    
    private PDO $db;
    private const UPLOAD_DIR = __DIR__ . '/../assets/img/countries/';
    private const PUBLIC_PATH = 'src/assets/img/countries/';
    private const ALLOWED_TYPES = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
    private const MAX_FILE_SIZE = 5 * 1024 * 1024; // 5MB

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    // ======================== GET METHODS ========================

    /**
     * Dobijanje svih država
     */
    public function getCountries(): void
    {
        $sql = "SELECT id, name, file_path FROM countries WHERE deleted = 0 ORDER BY name ASC";
        
        try {
            $result = $this->db->query($sql);
            $num = $result->rowCount();

            if ($num > 0) {
                $countries = [];

                while ($row = $result->fetch(PDO::FETCH_OBJ)) {
                    $countries[] = [
                        'id' => (int) $row->id,
                        'name' => $row->name,
                        'file_path' => $row->file_path
                    ];
                }

                http_response_code(200);
                echo json_encode([
                    'drzave' => $countries
                ], JSON_UNESCAPED_UNICODE);
            } else {
                http_response_code(200);
                echo json_encode([
                    'message' => 'Nije pronađena nijedna država'
                ], JSON_UNESCAPED_UNICODE);
            }
        } catch (PDOException $e) {
            Logger::error('Failed to fetch countries in getCountries()', [
                'error' => $e->getMessage(),
                'file' => __FILE__,
                'line' => __LINE__
            ]);
            
            http_response_code(500);
            echo json_encode([
                'error' => 'Došlo je do greške pri učitavanju država!'
            ], JSON_UNESCAPED_UNICODE);
        }
    }

    /**
     * Dobijanje jedne države po ID-u
     */
    public function getCountry(): void
    {
        if (empty($this->id)) {
            http_response_code(400);
            echo json_encode([
                'error' => 'ID države je obavezan'
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        $sql = "SELECT id, name, file_path FROM countries WHERE id = :id AND deleted = 0 LIMIT 1";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':id', $this->id, PDO::PARAM_INT);
            $stmt->execute();
            
            $country = $stmt->fetch(PDO::FETCH_OBJ);

            if ($country) {
                http_response_code(200);
                echo json_encode([
                    'id' => (int) $country->id,
                    'name' => $country->name,
                    'file_path' => $country->file_path
                ], JSON_UNESCAPED_UNICODE);
            } else {
                http_response_code(404);
                echo json_encode([
                    'error' => 'Država nije pronađena'
                ], JSON_UNESCAPED_UNICODE);
            }
        } catch (PDOException $e) {
            Logger::error('Failed to fetch country in getCountry()', [
                'id' => $this->id,
                'error' => $e->getMessage(),
                'file' => __FILE__,
                'line' => __LINE__
            ]);
            
            http_response_code(500);
            echo json_encode([
                'error' => 'Došlo je do greške pri učitavanju države!'
            ], JSON_UNESCAPED_UNICODE);
        }
    }

    // ======================== CREATE METHOD ========================

    /**
     * Kreiranje nove države sa zastavom
     */
    public function create(): void
    {
        // Validacija imena
        if (empty($this->name)) {
            http_response_code(400);
            echo json_encode([
                'error' => 'Ime države je obavezno'
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        // Validacija fajla
        if (!isset($this->flag)) {
            http_response_code(400);
            echo json_encode([
                'error' => 'Slika nije poslata! Molimo Vas da pošaljete zastavu države.'
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        // Provera da li država već postoji
        if ($this->countryExists()) {
            http_response_code(409);
            echo json_encode([
                'error' => 'Država sa ovim imenom već postoji!'
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        // Upload fajla
        $uploadResult = $this->uploadFlag();
        
        if (!$uploadResult['success']) {
            http_response_code(400);
            echo json_encode([
                'error' => $uploadResult['error']
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        // Insert u bazu
        $sql = "INSERT INTO countries SET name = :name, file_path = :flag";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':name', $this->name, PDO::PARAM_STR);
            $stmt->bindParam(':flag', $uploadResult['path'], PDO::PARAM_STR);
            $stmt->execute();

            http_response_code(201);
            echo json_encode([
                'success' => true,
                'msg' => 'Uspešno ste dodali novu državu!',
                'file' => $uploadResult['file'],
                'path' => $uploadResult['path']
            ], JSON_UNESCAPED_UNICODE);
            
        } catch (PDOException $e) {
            // Brisanje uploadovanog fajla ako insert ne uspe
            if (file_exists(self::UPLOAD_DIR . $uploadResult['file'])) {
                unlink(self::UPLOAD_DIR . $uploadResult['file']);
            }
            
            Logger::error('Failed to create country in create()', [
                'name' => $this->name,
                'error' => $e->getMessage(),
                'file' => __FILE__,
                'line' => __LINE__
            ]);
            
            http_response_code(500);
            echo json_encode([
                'error' => 'Došlo je do greške pri dodavanju države!'
            ], JSON_UNESCAPED_UNICODE);
        }
    }

    // ======================== UPDATE METHOD ========================

    /**
     * Izmena zastave države
     */
    public function update(): void
    {
        // Validacija ID-a
        if (empty($this->id)) {
            http_response_code(400);
            echo json_encode([
                'error' => 'ID države je obavezan'
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        // Validacija imena
        if (empty($this->name)) {
            http_response_code(400);
            echo json_encode([
                'error' => 'Ime države je obavezno'
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        // Validacija fajla
        if (!isset($this->flag)) {
            http_response_code(400);
            echo json_encode([
                'error' => 'Slika nije poslata! Molimo Vas da pošaljete zastavu države.'
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        // Dobijanje stare putanje (za brisanje)
        $oldPath = $this->getOldFilePath();

        // Upload novog fajla
        $uploadResult = $this->uploadFlag();
        
        if (!$uploadResult['success']) {
            http_response_code(400);
            echo json_encode([
                'error' => $uploadResult['error']
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        // Update u bazi
        $sql = "UPDATE countries SET file_path = :flag WHERE id = :id";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':id', $this->id, PDO::PARAM_INT);
            $stmt->bindParam(':flag', $uploadResult['path'], PDO::PARAM_STR);
            $stmt->execute();

            // Brisanje starog fajla
            if ($oldPath && file_exists(self::UPLOAD_DIR . basename($oldPath))) {
                unlink(self::UPLOAD_DIR . basename($oldPath));
            }

            http_response_code(200);
            echo json_encode([
                'success' => true,
                'msg' => "Uspešno ste zamenili zastavu države: {$this->name}!",
                'file' => $uploadResult['file'],
                'path' => $uploadResult['path']
            ], JSON_UNESCAPED_UNICODE);
            
        } catch (PDOException $e) {
            // Brisanje uploadovanog fajla ako update ne uspe
            if (file_exists(self::UPLOAD_DIR . $uploadResult['file'])) {
                unlink(self::UPLOAD_DIR . $uploadResult['file']);
            }
            
            Logger::error('Failed to update country in update()', [
                'id' => $this->id,
                'name' => $this->name,
                'error' => $e->getMessage(),
                'file' => __FILE__,
                'line' => __LINE__
            ]);
            
            http_response_code(500);
            echo json_encode([
                'error' => 'Došlo je do greške pri izmeni države!'
            ], JSON_UNESCAPED_UNICODE);
        }
    }

    // ======================== DELETE METHOD ========================

    /**
     * Soft delete države
     */
    public function delete(): void
    {
        if (empty($this->id)) {
            http_response_code(400);
            echo json_encode([
                'error' => 'ID države je obavezan'
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        // Provera da li postoje gradovi vezani za ovu državu
        if ($this->hasCities()) {
            http_response_code(409);
            echo json_encode([
                'error' => 'Ne možete obrisati državu koja ima vezane gradove!'
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        $sql = "UPDATE countries SET deleted = 1 WHERE id = :id";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':id', $this->id, PDO::PARAM_INT);
            $stmt->execute();

            http_response_code(200);
            echo json_encode([
                'success' => true,
                'message' => 'Država je uspešno obrisana.'
            ], JSON_UNESCAPED_UNICODE);
            
        } catch (PDOException $e) {
            Logger::error('Failed to delete country in delete()', [
                'id' => $this->id,
                'error' => $e->getMessage(),
                'file' => __FILE__,
                'line' => __LINE__
            ]);
            
            http_response_code(500);
            echo json_encode([
                'error' => 'Trenutno nije moguće obrisati ovu državu'
            ], JSON_UNESCAPED_UNICODE);
        }
    }

    // ======================== HELPER METHODS ========================

    /**
     * Upload zastave
     */
    private function uploadFlag(): array
    {
        $file = $this->flag;

        // Validacija tipa fajla
        if (!in_array($file->type, self::ALLOWED_TYPES)) {
            return [
                'success' => false,
                'error' => 'Nepodržan tip fajla. Dozvoljeni formati: JPEG, JPG, PNG, WEBP'
            ];
        }

        // Validacija veličine
        if ($file->size > self::MAX_FILE_SIZE) {
            return [
                'success' => false,
                'error' => 'Fajl je prevelik! Maksimalna veličina je 5MB.'
            ];
        }

        // Kreiranje direktorijuma ako ne postoji
        if (!is_dir(self::UPLOAD_DIR)) {
            mkdir(self::UPLOAD_DIR, 0755, true);
        }

        // Sanitizacija imena fajla
        $extension = pathinfo($file->name, PATHINFO_EXTENSION);
        $safeName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $this->name);
        $newName = $safeName . '.' . $extension;
        
        $targetFile = self::UPLOAD_DIR . $newName;
        $publicPath = self::PUBLIC_PATH . $newName;

        // Move uploaded file
        if (move_uploaded_file($file->tmp_name, $targetFile)) {
            // Set proper permissions
            chmod($targetFile, 0644);
            
            return [
                'success' => true,
                'file' => $newName,
                'path' => $publicPath
            ];
        }

        return [
            'success' => false,
            'error' => 'Došlo je do greške pri snimanju fajla.'
        ];
    }

    /**
     * Provera da li država već postoji
     */
    private function countryExists(): bool
    {
        $sql = "SELECT id FROM countries WHERE name = :name AND deleted = 0 LIMIT 1";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':name', $this->name, PDO::PARAM_STR);
            $stmt->execute();
            
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            Logger::error('Failed to check if country exists', [
                'name' => $this->name,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Dobijanje stare putanje fajla
     */
    private function getOldFilePath(): ?string
    {
        $sql = "SELECT file_path FROM countries WHERE id = :id LIMIT 1";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':id', $this->id, PDO::PARAM_INT);
            $stmt->execute();
            
            $result = $stmt->fetch(PDO::FETCH_OBJ);
            return $result ? $result->file_path : null;
        } catch (PDOException $e) {
            Logger::error('Failed to get old file path', [
                'id' => $this->id,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Provera da li država ima vezane gradove
     */
    private function hasCities(): bool
    {
        $sql = "SELECT COUNT(*) as count FROM cities WHERE country_id = :id AND deleted = 0";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':id', $this->id, PDO::PARAM_INT);
            $stmt->execute();
            
            $result = $stmt->fetch(PDO::FETCH_OBJ);
            return $result && $result->count > 0;
        } catch (PDOException $e) {
            Logger::error('Failed to check if country has cities', [
                'id' => $this->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}

?>