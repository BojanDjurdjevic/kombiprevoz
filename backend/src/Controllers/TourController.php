<?php
// REFACTOR ---------
declare(strict_types=1);

namespace Controllers;

use Models\Tour;
use PDO;
use Rules\Validator;
use Middleware\DemoMiddleware;

if (!defined('APP_ACCESS')) {
    http_response_code(403);
    die('Direct access forbidden');
}

class TourController {
    private PDO $db;
    private object $data;
    private Tour $tour;

    public function __construct(PDO $db, object $data)
    {
        $this->db = $db;
        $this->data = $data;
        $this->tour = new Tour($this->db);
    }

    /**
     * Provera admin/super privilegija
     */
    private function requireAdmin(): void
    {
        if (!Validator::isAdmin() && !Validator::isSuper()) {
            http_response_code(403);
            echo json_encode([
                'error' => 'Nemate dozvolu za ovu akciju'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    /**
     * GET metoda - pretraga tura
     */
    private function get(): void
    {
        if (!isset($this->data->tours)) {
            http_response_code(400);
            echo json_encode([
                'error' => 'Nedostaju parametri'
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        // GET all tours
        if (isset($this->data->tours->tour) && $this->data->tours->tour === 'all') {
            $this->tour->getAll();
            return;
        }

        // GET fully booked days
        if (isset($this->data->tours->days)) {
            if (empty($this->data->tours->days->from) 
                || empty($this->data->tours->days->to) 
                || empty($this->data->tours->days->format)) {
                http_response_code(400);
                echo json_encode([
                    'error' => 'Nedostaju parametri (from, to, format)'
                ], JSON_UNESCAPED_UNICODE);
                return;
            }

            $this->tour->from_city = (string)$this->data->tours->days->from;
            $this->tour->to_city = (string)$this->data->tours->days->to;
            
            $fullDays = $this->tour->fullyBooked((string)$this->data->tours->days->format);
            
            echo json_encode($fullDays, JSON_UNESCAPED_UNICODE); 
            return;
        }

        // SEARCH tours by from/to/date
        if (isset($this->data->tours->search)) {
            if (empty($this->data->tours->search->from) 
                || empty($this->data->tours->search->to) 
                || empty($this->data->tours->search->date)
                || empty($this->data->tours->search->seats)) {
                http_response_code(400);
                echo json_encode([
                    'error' => 'Nedostaju obavezni parametri za pretragu'
                ], JSON_UNESCAPED_UNICODE);
                return;
            }

            // Validacija datuma
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $this->data->tours->search->date)) {
                http_response_code(400);
                echo json_encode([
                    'error' => 'Neispravan format datuma'
                ], JSON_UNESCAPED_UNICODE);
                return;
            }

            // Validacija seats
            $seats = filter_var($this->data->tours->search->seats, FILTER_VALIDATE_INT);
            
            if ($seats === false || $seats < 1) {
                http_response_code(400);
                echo json_encode([
                    'error' => 'Broj mesta mora biti veći od 0'
                ], JSON_UNESCAPED_UNICODE);
                return;
            }

            $this->tour->from_city = (string)$this->data->tours->search->from;
            $this->tour->to_city = (string)$this->data->tours->search->to;
            $this->tour->date = (string)$this->data->tours->search->date;
            $this->tour->requestedSeats = $seats;
            
            // Inbound (opciono)
            $this->tour->inbound = isset($this->data->tours->search->inbound) 
                && !empty($this->data->tours->search->inbound)
                ? (string)$this->data->tours->search->inbound
                : null;

            $this->tour->getBySearch();
            return;
        }

        // GET by filters (admin only)
        if (isset($this->data->tours->byFilter)) {
            $this->requireAdmin();

            $this->tour->id = isset($this->data->tours->byFilter->id) 
                ? filter_var($this->data->tours->byFilter->id, FILTER_VALIDATE_INT) 
                : null;

            if ($this->tour->id === false) {
                http_response_code(400);
                echo json_encode([
                    'error' => 'Neispravan ID ture'
                ], JSON_UNESCAPED_UNICODE);
                return;
            }

            $this->tour->from_city = isset($this->data->tours->byFilter->from_city) 
                ? (string)$this->data->tours->byFilter->from_city 
                : null;
            
            $this->tour->to_city = isset($this->data->tours->byFilter->to_city) 
                ? (string)$this->data->tours->byFilter->to_city 
                : null;

            $this->tour->getByFilters();
            return;
        }

        // GET to_cities based on from_city
        if (isset($this->data->tours->city->name)) {
            if (empty($this->data->tours->city->name)) {
                http_response_code(400);
                echo json_encode([
                    'error' => 'Ime grada je obavezno'
                ], JSON_UNESCAPED_UNICODE);
                return;
            }

            $from = filter_var(
                $this->data->tours->city->from ?? false,
                FILTER_VALIDATE_BOOLEAN,
                FILTER_NULL_ON_FAILURE
            );

            $this->tour->getToCities(
                (string)$this->data->tours->city->name, 
                $from === true
            );
            return;
        }

        // Ako ništa nije matchovano
        http_response_code(400);
        echo json_encode([
            'error' => 'Nevalidni parametri za pretragu'
        ], JSON_UNESCAPED_UNICODE);
    }

    /**
     * POST metoda - kreiranje nove ture
     */
    private function post(): void
    {
        $this->requireAdmin();

        if (!isset($this->data->tours)) {
            http_response_code(400);
            echo json_encode([
                'error' => 'Nedostaju podaci za kreiranje ture'
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        // Validacija obaveznih polja
        $required = ['from', 'to', 'departures', 'time', 'duration', 'price', 'seats'];
        
        foreach ($required as $field) {
            if (!isset($this->data->tours->$field) || empty($this->data->tours->$field)) {
                http_response_code(400);
                echo json_encode([
                    'error' => "Polje '{$field}' je obavezno"
                ], JSON_UNESCAPED_UNICODE);
                return;
            }
        }

        // Validacija duration, price, seats
        $duration = filter_var($this->data->tours->duration, FILTER_VALIDATE_INT);
        $price = filter_var($this->data->tours->price, FILTER_VALIDATE_INT);
        $seats = filter_var($this->data->tours->seats, FILTER_VALIDATE_INT);

        if ($duration === false || $duration < 1) {
            http_response_code(400);
            echo json_encode([
                'error' => 'Trajanje mora biti veći od 0'
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        if ($price === false || $price < 1) {
            http_response_code(400);
            echo json_encode([
                'error' => 'Cena mora biti veća od 0'
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        if ($seats === false || $seats < 1 || $seats > 50) {
            http_response_code(400);
            echo json_encode([
                'error' => 'Broj sedišta mora biti između 1 i 50'
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        // Validacija time formata (HH:MM)
        if (!preg_match('/^\d{2}:\d{2}$/', $this->data->tours->time)) {
            http_response_code(400);
            echo json_encode([
                'error' => 'Neispravan format vremena (mora biti HH:MM)'
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        // Validacija departures (mora biti 0-6, comma separated)
        if (!preg_match('/^[0-6](,[0-6])*$/', $this->data->tours->departures)) {
            http_response_code(400);
            echo json_encode([
                'error' => 'Neispravan format dana polaska (0-6, razdvojeni zarezom)'
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        // Setuj property-je
        $this->tour->from_city = (string)$this->data->tours->from;
        $this->tour->to_city = (string)$this->data->tours->to;
        $this->tour->departures = (string)$this->data->tours->departures;
        $this->tour->time = (string)$this->data->tours->time;
        $this->tour->duration = $duration;
        $this->tour->price = $price;
        $this->tour->seats = $seats;

        $this->tour->create();
    }

    /**
     * PUT metoda - izmena ture
     */
    private function put(): void
    {
        $this->requireAdmin();

        if (!isset($this->data->tours->update)) {
            http_response_code(400);
            echo json_encode([
                'error' => 'Nedostaje update parametar'
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        if (!isset($this->data->tours->id) || empty($this->data->tours->id)) {
            http_response_code(400);
            echo json_encode([
                'error' => 'ID ture je obavezan'
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        $tourId = filter_var($this->data->tours->id, FILTER_VALIDATE_INT);
        
        if ($tourId === false) {
            http_response_code(400);
            echo json_encode([
                'error' => 'Neispravan ID ture'
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        // Validacija obaveznih polja
        $required = ['departures', 'time', 'duration', 'price', 'seats'];
        
        foreach ($required as $field) {
            if (!isset($this->data->tours->$field)) {
                http_response_code(400);
                echo json_encode([
                    'error' => "Polje '{$field}' je obavezno"
                ], JSON_UNESCAPED_UNICODE);
                return;
            }
        }

        // Validacija integer polja
        $duration = filter_var($this->data->tours->duration, FILTER_VALIDATE_INT);
        $price = filter_var($this->data->tours->price, FILTER_VALIDATE_INT);
        $seats = filter_var($this->data->tours->seats, FILTER_VALIDATE_INT);

        if ($duration === false || $duration < 1 
            || $price === false || $price < 1 
            || $seats === false || $seats < 1) {
            http_response_code(400);
            echo json_encode([
                'error' => 'Nevalidne vrednosti za trajanje, cenu ili broj sedišta'
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        // Setuj property-je
        $this->tour->id = $tourId;
        $this->tour->departures = (string)$this->data->tours->departures;
        $this->tour->time = (string)$this->data->tours->time;
        $this->tour->duration = $duration;
        $this->tour->price = $price;
        $this->tour->seats = $seats;

        $this->tour->update();
    }

    /**
     * DELETE metoda - brisanje/restore ture
     */
    private function delete(): void
    {
        $this->requireAdmin();

        if (!isset($this->data->tours->id) || empty($this->data->tours->id)) {
            http_response_code(400);
            echo json_encode([
                'error' => 'ID ture je obavezan'
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        $tourId = filter_var($this->data->tours->id, FILTER_VALIDATE_INT);
        
        if ($tourId === false) {
            http_response_code(400);
            echo json_encode([
                'error' => 'Neispravan ID ture'
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        $this->tour->id = $tourId;
        $this->tour->to_city = isset($this->data->tours->to_city) 
            ? (string)$this->data->tours->to_city 
            : null;

        // Određivanje akcije
        if (isset($this->data->tours->delete)) {
            $this->tour->delete();
        } elseif (isset($this->data->tours->restore)) {
            $this->tour->restore();
        } elseif (isset($this->data->tours->restoreAll)) {
            $this->tour->restoreAll();
        } elseif (isset($this->data->tours->permanentDelete)) {
            $this->tour->permanentDelete();
        } else {
            http_response_code(400);
            echo json_encode([
                'error' => 'Nepoznata akcija'
            ], JSON_UNESCAPED_UNICODE);
        }
    }

    /**
     * Glavni handler
     */
    public function handleRequest(): void
    {
        $request = $_SERVER['REQUEST_METHOD'];

        // Demo middleware za POST, PUT, DELETE
        if (in_array($request, ['POST', 'PUT', 'DELETE'])) {
            DemoMiddleware::handle();
        }

        try {
            switch ($request) {
                case 'GET':
                    $this->get();
                    break;
                
                case 'POST':
                    $this->post();
                    break;
                
                case 'PUT':
                    $this->put();
                    break;
                
                case 'DELETE':
                    $this->delete();
                    break;
                
                default:
                    http_response_code(405);
                    echo json_encode([
                        'error' => 'Metoda nije dozvoljena'
                    ], JSON_UNESCAPED_UNICODE);
            }
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode([
                'error' => 'Neočekivana greška',
                'message' => $_ENV['APP_ENV'] === 'development' ? $e->getMessage() : null
            ], JSON_UNESCAPED_UNICODE);
        }
    }
}

?>