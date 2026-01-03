<?php
declare(strict_types=1);

namespace Core;

use Controllers\ChatController;
use Controllers\CityController;
use Controllers\CountryController;
use Controllers\DepartureController;
use Controllers\OrderController;
use Controllers\TourController;
use Controllers\UserController;
use Middleware\DemoMiddleware;
use Models\User;
use PDO;
use Rules\Validator;

if (!defined('APP_ACCESS')) {
    http_response_code(403);
    die('Direct access forbidden');
}

class Router {
    private PDO $db;
    private object $data;
    private string $sid;

    public function __construct(PDO $db, object $data, string $sid)
    {
        $this->db = $db;
        $this->data = $data;
        $this->sid = $sid;
    }

    /**
     * Glavna metoda koja rutira zahteve
     */
    public function route(): void
    {
        if (isset($this->data->users) && !empty($this->data->users)) {
            $this->handleUsers();
            return;
        }

        if (isset($this->data->orders) && !empty($this->data->orders)) {
            $this->handleOrders();
            return;
        }

        if (isset($this->data->country) && !empty($this->data->country)) {
            $this->handleCountries();
            return;
        }

        if (isset($this->data->cities) && !empty($this->data->cities)) {
            $this->handleCities();
            return;
        }

        if (isset($this->data->tours) && !empty($this->data->tours)) {
            $this->handleTours();
            return;
        }

        if (isset($this->data->departure) && !empty($this->data->departure)) {
            $this->handleDepartures();
            return;
        }

        if (isset($this->data->chat) && !empty($this->data->chat)) {
            $this->handleChats();
            return;
        }

        http_response_code(400);
        echo json_encode([
            'error' => 'Nepoznat tip zahteva'
        ], JSON_UNESCAPED_UNICODE);
    }

    // ======================== USER ROUTES ========================

    private function handleUsers(): void
    {
        $controller = new UserController($this->db, $this->data);
        $controller->handleRequest();
    }

    // ======================== ORDER ROUTES ========================

    private function handleOrders(): void
    {
        if (!isset($_SESSION['user'])) {
            http_response_code(401);
            echo json_encode([
                'user' => 404,
                'error' => 'Vaša sesija je istekla, molimo Vas da se ulogujete ponovo!'
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        $controller = new OrderController($this->db, $this->data, $this->sid);
        $controller->handleRequest();
    }

    // ======================== COUNTRY ROUTES ========================

    private function handleCountries(): void
    {
        $controller = new CountryController($this->db, $this->data);
        $controller->handleRequest();
    }

    // ======================== CITY ROUTES ========================

    private function handleCities(): void
    {
        $controller = new CityController($this->db, $this->data);
        $controller->handleRequest();
    }

    // ======================== TOUR ROUTES ========================
    private function handleTours(): void
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $controller = new TourController($this->db, $this->data);

        // Demo middleware za POST, PUT, DELETE
        if (in_array($method, ['POST', 'PUT', 'DELETE'])) {
            DemoMiddleware::handle();
        }

        try {
            switch ($method) {
                case 'GET':
                    $this->handleToursGet($controller);
                    break;
                
                case 'POST':
                    $this->handleToursPost($controller);
                    break;
                
                case 'PUT':
                    $this->handleToursPut($controller);
                    break;
                
                case 'DELETE':
                    $this->handleToursDelete($controller);
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

    /**
     * GET akcije za ture
     */
    private function handleToursGet(TourController $controller): void
    {
        // GET all tours
        if (isset($this->data->tours->tour) && $this->data->tours->tour === 'all') {
            $controller->getAllTours();
            return;
        }

        // GET fully booked days
        if (isset($this->data->tours->days)) {
            $controller->getFullyBookedDays();
            return;
        }

        // SEARCH tours
        if (isset($this->data->tours->search)) {
            $controller->searchTours();
            return;
        }

        // GET by filters (admin only)
        if (isset($this->data->tours->byFilter)) {
            $this->requireAdmin();
            $controller->getToursByFilter();
            return;
        }

        // GET destination cities
        if (isset($this->data->tours->city->name)) {
            $controller->getDestinationCities();
            return;
        }

        http_response_code(400);
        echo json_encode([
            'error' => 'Nevalidni parametri za GET zahtev'
        ], JSON_UNESCAPED_UNICODE);
    }

    /**
     * POST akcije za ture (admin only)
     */
    private function handleToursPost(TourController $controller): void
    {
        $this->requireAdmin();
        
        if (!isset($this->data->tours)) {
            http_response_code(400);
            echo json_encode([
                'error' => 'Nedostaju podaci za kreiranje ture'
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        $controller->createTour();
    }

    /**
     * PUT akcije za ture (admin only)
     */
    private function handleToursPut(TourController $controller): void
    {
        $this->requireAdmin();
        
        if (!isset($this->data->tours->update)) {
            http_response_code(400);
            echo json_encode([
                'error' => 'Nedostaje update parametar'
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        $controller->updateTour();
    }

    /**
     * DELETE akcije za ture (admin only)
     */
    private function handleToursDelete(TourController $controller): void
    {
        $this->requireAdmin();

        if (!isset($this->data->tours->id) || empty($this->data->tours->id)) {
            http_response_code(400);
            echo json_encode([
                'error' => 'ID ture je obavezan'
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        // Restore
        if (isset($this->data->tours->restore)) {
            $controller->restoreTour();
            return;
        }

        // Restore all
        if (isset($this->data->tours->restoreAll)) {
            $controller->restoreAllTours();
            return;
        }

        // Permanent delete
        if (isset($this->data->tours->permanentDelete)) {
            $controller->permanentDeleteTour();
            return;
        }

        // Regular delete
        if (isset($this->data->tours->delete)) {
            $controller->deleteTour();
            return;
        }

        http_response_code(400);
        echo json_encode([
            'error' => 'Nepoznata DELETE akcija'
        ], JSON_UNESCAPED_UNICODE);
    }

    // ======================== DEPARTURE ROUTES ========================

    private function handleDepartures(): void
    {
        if (!isset($_SESSION['user'])) {
            http_response_code(401);
            echo json_encode([
                'user' => 404,
                'error' => 'Vaša sesija je istekla!'
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        if (!Validator::isDriver() && !Validator::isAdmin() && !Validator::isSuper()) {
            http_response_code(403);
            echo json_encode([
                'error' => 'Niste autorizovani da pristupite ovom resursu!'
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        $controller = new DepartureController($this->db, $this->data);
        $controller->handleRequest();
    }

    // ======================== CHAT ROUTES ========================

    private function handleChats(): void
    {
        $controller = new ChatController($this->db, $this->data);
        $controller->handleRequest();
    }

    // ======================== HELPER METHODS -> auths and roles ========================

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


    public static function checkAuth(PDO $db): bool
    {
        return User::isLoged($db);
    }
}

?>