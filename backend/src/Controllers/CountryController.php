<?php
declare(strict_types=1);

namespace Controllers;

use Models\Country;
use PDO;

if (!defined('APP_ACCESS')) {
    http_response_code(403);
    die('Direct access forbidden');
}

/**
 * CountryController
 * Svaka metoda odgovara jednoj akciji - bez switch/if blokova
 */
class CountryController {
    private PDO $db;
    private object $data;
    private Country $country;

    public function __construct(PDO $db, object $data)
    {
        $this->db = $db;
        $this->data = $data;
        $this->country = new Country($this->db);
    }

    // ======================== GET AKCIJE ========================

    /**
     * GET sve države
     * Akcija: { "country": {} } ili { "country": { "country_id": null } }
     */
    public function getAllCountries(): void
    {
        $this->country->getCountries();
    }

    /**
     * GET jednu državu po ID-u
     * Akcija: { "country": { "country_id": N } }
     */
    public function getCountryById(): void
    {
        if (empty($this->data->country->country_id)) {
            http_response_code(400);
            echo json_encode([
                'error' => 'ID države je obavezan'
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        $countryId = filter_var($this->data->country->country_id, FILTER_VALIDATE_INT);
        
        if ($countryId === false) {
            http_response_code(400);
            echo json_encode([
                'error' => 'Neispravan ID države'
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        $this->country->id = $countryId;
        $this->country->getCountry();
    }

    // ======================== POST AKCIJA ========================

    /**
     * CREATE nova država
     * Akcija: { "country": "create", "country_name": "...", "flag": {...} }
     */
    public function createCountry(): void
    {
        // Validacija imena
        if (empty($this->data->country_name)) {
            http_response_code(400);
            echo json_encode([
                'error' => 'Ime države je obavezno'
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        // Validacija fajla
        if (empty($this->data->flag)) {
            http_response_code(400);
            echo json_encode([
                'error' => 'Zastava je obavezna'
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        // Validacija dužine imena
        $nameLength = mb_strlen($this->data->country_name, 'UTF-8');
        if ($nameLength < 2 || $nameLength > 100) {
            http_response_code(400);
            echo json_encode([
                'error' => 'Ime države mora biti između 2 i 100 karaktera'
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        $this->country->name = trim($this->data->country_name);
        $this->country->flag = $this->data->flag;
        $this->country->create();
    }

    // ======================== PUT AKCIJA ========================

    /**
     * UPDATE zastave države
     * Akcija: { "country": "update", "country_id": N, "country_name": "...", "flag": {...} }
     */
    public function updateCountry(): void
    {
        // Validacija ID-a
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

        // Validacija imena
        if (empty($this->data->country_name)) {
            http_response_code(400);
            echo json_encode([
                'error' => 'Ime države je obavezno'
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        // Validacija fajla
        if (empty($this->data->flag)) {
            http_response_code(400);
            echo json_encode([
                'error' => 'Zastava je obavezna'
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        $this->country->id = $countryId;
        $this->country->name = trim($this->data->country_name);
        $this->country->flag = $this->data->flag;
        $this->country->update();
    }

    // ======================== DELETE AKCIJA ========================

    /**
     * DELETE država (soft delete)
     * Akcija: { "country": { "country_id": N } }
     */
    public function deleteCountry(): void
    {
        if (empty($this->data->country->country_id)) {
            http_response_code(400);
            echo json_encode([
                'error' => 'ID države je obavezan'
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        $countryId = filter_var($this->data->country->country_id, FILTER_VALIDATE_INT);
        
        if ($countryId === false) {
            http_response_code(400);
            echo json_encode([
                'error' => 'Neispravan ID države'
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        $this->country->id = $countryId;
        $this->country->delete();
    }
}

?>