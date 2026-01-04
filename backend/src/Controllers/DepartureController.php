<?php
declare(strict_types=1);

namespace Controllers;

use Models\Departure;
use PDO;

if (!defined('APP_ACCESS')) {
    http_response_code(403);
    die('Direct access forbidden');
}

/**
 * DepartureController
 * Svaka metoda = jedna akcija
 */
class DepartureController {
    private PDO $db;
    private object $data;
    private Departure $departure;

    public function __construct(PDO $db, object $data)
    {
        $this->db = $db;
        $this->data = $data;
        $this->departure = new Departure($this->db);
    }

    /**
     * Pomoćna metoda za dodelu podataka
     */
    private function assignDepartureData(): void
    {
        $this->departure->id = isset($this->data->departure->id) 
            ? filter_var($this->data->departure->id, FILTER_VALIDATE_INT) ?: null
            : null;
        
        $this->departure->driver_id = isset($this->data->departure->driver_id) 
            ? filter_var($this->data->departure->driver_id, FILTER_VALIDATE_INT) ?: null
            : null;
        
        $this->departure->tour_id = isset($this->data->departure->tour_id) 
            ? filter_var($this->data->departure->tour_id, FILTER_VALIDATE_INT) ?: null
            : null;
        
        $this->departure->code = $this->data->departure->code ?? null;
        $this->departure->path = $this->data->departure->path ?? null;
        $this->departure->date = $this->data->departure->date ?? null;
    }

    // ======================== GET METHODS ========================

    /**
     * GET orders for specific departure
     * Akcija: { "departure": { "id": N } }
     */
    public function getOrdersOfDeparture(): void
    {
        $this->assignDepartureData();

        if (empty($this->departure->id)) {
            http_response_code(400);
            echo json_encode([
                'error' => 'ID polaska je obavezan'
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        $this->departure->getOrdersOfDep();
    }

    /**
     * GET departures by filters
     * Akcija: { "departure": { "driver_id": N, "tour_id": N, "date": "...", "code": "..." } }
     */
    public function getDeparturesByFilter(): void
    {
        $this->assignDepartureData();

        // Validacija datuma ako postoji
        if (!empty($this->departure->date)) {
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $this->departure->date)) {
                http_response_code(400);
                echo json_encode([
                    'error' => 'Neispravan format datuma (mora biti YYYY-MM-DD)'
                ], JSON_UNESCAPED_UNICODE);
                return;
            }
        }

        $this->departure->getByFilter();
    }

    // ======================== POST METHOD (za buduću implementaciju) ========================

    /**
     * CREATE new departure
     * Akcija: { "drive": { "create": true, ... } }
     */
    public function createDeparture(): void
    {
        // Implementacija kada bude potrebno
        http_response_code(501);
        echo json_encode([
            'error' => 'CREATE departure nije implementiran'
        ], JSON_UNESCAPED_UNICODE);
    }

    // ======================== PUT METHOD (za buduću implementaciju) ========================

    /**
     * UPDATE departure
     * Akcija: { "drive": { "update": true, "id": N, ... } }
     */
    public function updateDeparture(): void
    {
        // Implementacija kada bude potrebno
        http_response_code(501);
        echo json_encode([
            'error' => 'UPDATE departure nije implementiran'
        ], JSON_UNESCAPED_UNICODE);
    }

    // ======================== DELETE METHOD (za buduću implementaciju) ========================

    /**
     * DELETE departure
     * Akcija: { "drive": { "delete": true, "id": N } }
     */
    public function deleteDeparture(): void
    {
        //Implementacija kada bude potrebno
        http_response_code(501);
        echo json_encode([
            'error' => 'DELETE departure nije implementiran'
        ], JSON_UNESCAPED_UNICODE);
    }
}

?>