<?php
declare(strict_types=1);

namespace Controllers;

use Models\City;
use PDO;

if (!defined('APP_ACCESS')) {
    http_response_code(403);
    die('Direct access forbidden');
}

/**
 * CityController
 * Svaka metoda odgovara jednoj akciji
 */
class CityController {
    private PDO $db;
    private object $data;
    private City $city;
    
    public function __construct(PDO $db, object $data)
    {
        $this->db = $db;
        $this->data = $data;
        $this->city = new City($this->db);
    }

    // ======================== GET AKCIJE ========================

    /**
     * GET sve gradove
     * Akcija: { "cities": {} }
     */
    public function getAllCities(): void
    {
        $this->city->getAll();
    }

    /**
     * GET gradove po ID-u države
     * Akcija: { "cities": { "country_id": N } }
     */
    public function getCitiesByCountry(): void
    {
        if (empty($this->data->cities->country_id)) {
            http_response_code(400);
            echo json_encode([
                'error' => 'ID države je obavezan'
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        $countryId = filter_var($this->data->cities->country_id, FILTER_VALIDATE_INT);
        
        if ($countryId === false) {
            http_response_code(400);
            echo json_encode([
                'error' => 'Neispravan ID države'
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        $this->city->country_id = $countryId;
        $this->city->getByCountryId();
    }

    /**
     * GET sve gradove sa svim slikama (uključujući obrisane) - admin view
     * Akcija: { "cities": { "countryID": N } }
     */
    public function getFullCitiesByCountry(): void
    {
        if (empty($this->data->cities->countryID)) {
            http_response_code(400);
            echo json_encode([
                'error' => 'ID države je obavezan'
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        $countryId = filter_var($this->data->cities->countryID, FILTER_VALIDATE_INT);
        
        if ($countryId === false) {
            http_response_code(400);
            echo json_encode([
                'error' => 'Neispravan ID države'
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        $this->city->country_id = $countryId;
        $this->city->getFullCitiesByCountryId();
    }

    /**
     * GET gradove po ID-u države sa slikama (alternativni parametar)
     * Akcija: { "cities": { "byID": N } }
     */
    public function getCitiesByCountryIdAlt(): void
    {
        if (empty($this->data->cities->byID)) {
            http_response_code(400);
            echo json_encode([
                'error' => 'ID države je obavezan'
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        $countryId = filter_var($this->data->cities->byID, FILTER_VALIDATE_INT);
        
        if ($countryId === false) {
            http_response_code(400);
            echo json_encode([
                'error' => 'Neispravan ID države'
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        $this->city->country_id = $countryId;
        $this->city->getByCountryId();
    }

    /**
     * GET jedan grad po ID-u
     * Akcija: { "cities": { "id": N } }
     */
    public function getCityById(): void
    {
        if (empty($this->data->cities->id)) {
            http_response_code(400);
            echo json_encode([
                'error' => 'ID grada je obavezan'
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        $cityId = filter_var($this->data->cities->id, FILTER_VALIDATE_INT);
        
        if ($cityId === false) {
            http_response_code(400);
            echo json_encode([
                'error' => 'Neispravan ID grada'
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        $this->city->id = $cityId;
        $this->city->getOne();
    }

    // ======================== POST AKCIJE ========================

    /**
     * CREATE novi grad sa slikama
     * Akcija: { "cities": "create", "name": "...", "country_id": N, "photos": [...] }
     */
    public function createCity(): void
    {
        // Validacija imena
        if (empty($this->data->name)) {
            http_response_code(400);
            echo json_encode([
                'error' => 'Ime grada je obavezno'
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        // Validacija country_id
        if (empty($this->data->country_id)) {
            http_response_code(400);
            echo json_encode([
                'error' => 'ID države je obavezan'
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        $countryId = filter_var($this->data->country_id, FILTER_VALIDATE_INT);
        
        if ($countryId === false) {
            http_response_code(400);
            echo json_encode([
                'error' => 'Neispravan ID države'
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        // Validacija dužine imena
        $nameLength = mb_strlen($this->data->name, 'UTF-8');
        if ($nameLength < 2 || $nameLength > 100) {
            http_response_code(400);
            echo json_encode([
                'error' => 'Ime grada mora biti između 2 i 100 karaktera'
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        $this->city->name = trim($this->data->name);
        $this->city->country_id = $countryId;
        $this->city->photos = $this->data->photos ?? [];
        $this->city->create();
    }

    /**
     * UPDATE grada - dodavanje novih slika
     * Akcija: { "cities": "update", "city_id": N, "photos": [...] }
     */
    public function addPhotosToCity(): void
    {
        // Validacija city_id
        if (empty($this->data->city_id)) {
            http_response_code(400);
            echo json_encode([
                'error' => 'ID grada je obavezan'
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        $cityId = filter_var($this->data->city_id, FILTER_VALIDATE_INT);
        
        if ($cityId === false) {
            http_response_code(400);
            echo json_encode([
                'error' => 'Neispravan ID grada'
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        // Validacija slika
        if (empty($this->data->photos) || !is_array($this->data->photos)) {
            http_response_code(400);
            echo json_encode([
                'error' => 'Slike su obavezne'
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        $this->city->id = $cityId;
        $this->city->name = $this->data->name ?? '';
        $this->city->photos = $this->data->photos;
        $this->city->update();
    }

    // ======================== PUT AKCIJE ========================

    /**
     * DELETE slike grada (soft delete)
     * Akcija: { "cities": { "ids": [1, 2, 3] } }
     */
    public function deleteCityPhotos(): void
    {
        if (empty($this->data->cities->ids) || !is_array($this->data->cities->ids)) {
            http_response_code(400);
            echo json_encode([
                'error' => 'ID-evi slika su obavezni'
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        $this->city->photos = $this->data->cities->ids;
        $this->city->name = $this->data->cities->name ?? '';
        $this->city->deleteCityPics(true);
    }

    /**
     * RESTORE slike grada
     * Akcija: { "cities": { "ids_restore": [1, 2, 3] } }
     */
    public function restoreCityPhotos(): void
    {
        if (empty($this->data->cities->ids_restore) || !is_array($this->data->cities->ids_restore)) {
            http_response_code(400);
            echo json_encode([
                'error' => 'ID-evi slika su obavezni'
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        $this->city->photos = $this->data->cities->ids_restore;
        $this->city->name = $this->data->cities->name ?? '';
        $this->city->deleteCityPics(false);
    }

    // ======================== DELETE AKCIJE ========================

    /**
     * DELETE grad (soft delete)
     * Akcija: { "cities": { "id": N } }
     */
    public function deleteCity(): void
    {
        if (empty($this->data->cities->id)) {
            http_response_code(400);
            echo json_encode([
                'error' => 'ID grada je obavezan'
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        $cityId = filter_var($this->data->cities->id, FILTER_VALIDATE_INT);
        
        if ($cityId === false) {
            http_response_code(400);
            echo json_encode([
                'error' => 'Neispravan ID grada'
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        $this->city->id = $cityId;
        $this->city->delete();
    }

    /**
     * RESTORE grada
     * Akcija: { "cities": { "restore": true, "id": N } }
     */
    public function restoreCity(): void
    {
        if (empty($this->data->cities->id)) {
            http_response_code(400);
            echo json_encode([
                'error' => 'ID grada je obavezan'
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        $cityId = filter_var($this->data->cities->id, FILTER_VALIDATE_INT);
        
        if ($cityId === false) {
            http_response_code(400);
            echo json_encode([
                'error' => 'Neispravan ID grada'
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        $this->city->id = $cityId;
        $this->city->restore();
    }
}

?>