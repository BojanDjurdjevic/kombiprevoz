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
use Guards\DemoGuard;
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
        if (isset($this->data->users) || isset($this->data->user)) {
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

    /**
     * User routing - detektuje HTTP metodu i akciju
     */
    private function handleUsers(): void
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $controller = new UserController($this->db, $this->data);

        // Dodela podataka iz $data u User objekat (kroz controller)
        $this->assignUserDataToController($controller);

        // Demo middleware samo za PUT i DELETE
        if (in_array($method, ['PUT', 'DELETE'])) {
            DemoMiddleware::handle();
        }

        try {
            switch ($method) {
                case 'GET':
                    $this->handleUsersGet($controller);
                    break;
                
                case 'POST':
                    $this->handleUsersPost($controller);
                    break;
                
                case 'PUT':
                    $this->handleUsersPut($controller);
                    break;
                
                case 'DELETE':
                    $this->handleUsersDelete($controller);
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
     * Pomoćna metoda za dodelu podataka kontroleru
     */
    private function assignUserDataToController(UserController $controller): void
    {
        // Pozivamo privatnu metodu kontrolera preko reflection (ili preko javne metode ako je napravimo)
        // Za sada, kontroler sam dodeljuje podatke u konstruktoru
        // Alternativa: napraviti public metodu assignUserData() u UserController-u
    }

    /**
     * GET akcije za korisnike
     */
    private function handleUsersGet(UserController $controller): void
    {
        // GET user by email (admin)
        if (isset($this->data->users->byEmail) && !empty($this->data->users->byEmail)) {
            $this->requireAdmin();
            $controller->getUserByEmail();
            return;
        }

        // GET user logs (admin)
        if (isset($this->data->users->getLogs) && !empty($this->data->users->getLogs)) {
            //$this->requireAdmin();
            $controller->getUserLogs();
            return;
        }

        // GET akcije koje zahtevaju "user" parametar
        if (isset($this->data->user) && !empty($this->data->user)) {
            
            // GET all users (admin)
            if (isset($this->data->all) && !empty($this->data->all)) {
                $this->requireAdmin();
                $controller->getAllUsers();
                return;
            }

            // GET user by ID (admin)
            if (isset($this->data->byID) && !empty($this->data->byID)) {
                $this->requireAdmin();
                $controller->getUserById();
                return;
            }

            // GET users by name (admin)
            if (isset($this->data->byName) && !empty($this->data->byName)) {
                $this->requireAdmin();
                $controller->getUsersByName();
                return;
            }

            // GET users by city (admin)
            if (isset($this->data->byCity) && !empty($this->data->byCity)) {
                $this->requireAdmin();
                $controller->getUsersByCity();
                return;
            }

            // CHECK reset token
            if (isset($this->data->token) && !empty($this->data->token)) {
                $controller->checkResetToken();
                return;
            }
        }

        http_response_code(400);
        echo json_encode([
            'error' => 'Nevalidni parametri za GET zahtev'
        ], JSON_UNESCAPED_UNICODE);
    }

    /**
     * POST akcije za korisnike
     */
    private function handleUsersPost(UserController $controller): void
    {
        if(isset($this->data->user)) {
            $this->checkAuth($this->db);
            return;
        }
        // REGISTER user (self-registration)
        if (isset($this->data->users->signin) && !empty($this->data->users->signin)) {
            $controller->registerUser();
            return;
        }

        // CREATE user by admin
        if (isset($this->data->users->byAdmin) && !empty($this->data->users->byAdmin)) {
            $this->requireAdmin();
            $controller->createUserByAdmin();
            return;
        }

        // LOGIN
        if (isset($this->data->users->login) && !empty($this->data->users->login)) {
            $controller->loginUser();
            return;
        }

        // LOGOUT
        if (isset($this->data->logout) && !empty($this->data->logout)) {
            $controller->logoutUser();
            return;
        }

        http_response_code(400);
        echo json_encode([
            'error' => 'Nevalidna POST akcija'
        ], JSON_UNESCAPED_UNICODE);
    }

    /**
     * PUT akcije za korisnike
     */
    private function handleUsersPut(UserController $controller): void
    {
        // UPDATE profile
        if (isset($this->data->updateProfile) && !empty($this->data->updateProfile)) {
            $controller->updateProfile();
            return;
        }

        // UPDATE password
        if (isset($this->data->updatePass) && !empty($this->data->updatePass)) {
            $controller->updatePassword();
            return;
        }

        // REQUEST password reset
        if (isset($this->data->resetPass) && !empty($this->data->resetPass)) {
            $controller->requestPasswordReset();
            return;
        }

        // PROCESS password reset (with token)
        if (isset($this->data->token) && !empty($this->data->token)) {
            $controller->processPasswordReset();
            return;
        }

        // UPDATE by admin
        if (isset($this->data->updateByAdmin) && !empty($this->data->updateByAdmin)) {
            $this->requireAdmin();
            $controller->updateUserByAdmin();
            return;
        }

        http_response_code(400);
        echo json_encode([
            'error' => 'Nevalidna PUT akcija'
        ], JSON_UNESCAPED_UNICODE);
    }

    /**
     * DELETE akcije za korisnike
     */
    private function handleUsersDelete(UserController $controller): void
    {
        // RESTORE user (admin)
        if (isset($this->data->restore) && !empty($this->data->restore)) {
            $this->requireAdmin();
            $controller->restoreUser();
            return;
        }

        // DELETE user
        if (isset($this->data->delete) && !empty($this->data->delete)) {
            $controller->deleteUser();
            return;
        }

        http_response_code(400);
        echo json_encode([
            'error' => 'Nevalidna DELETE akcija'
        ], JSON_UNESCAPED_UNICODE);
    }

    // ======================== ORDER ROUTES ========================

    /**
     * Order routing - detektuje HTTP metodu i akciju
     */
    private function handleOrders(): void
    {
        // Provera autentifikacije
        if (!isset($_SESSION['user'])) {
            http_response_code(401);
            echo json_encode([
                'error' => 'Vaša sesija je istekla, molimo Vas da se ulogujete ponovo!'
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        $method = $_SERVER['REQUEST_METHOD'];
        $controller = new OrderController($this->db, $this->data, $this->sid);

        // Demo middleware za POST, PUT, DELETE
        if (in_array($method, ['POST', 'PUT', 'DELETE'])) {
            DemoMiddleware::handle();
        }

        try {
            switch ($method) {
                case 'GET':
                    $this->handleOrdersGet($controller);
                    break;
                
                case 'POST':
                    $this->handleOrdersPost($controller);
                    break;
                
                case 'PUT':
                    $this->handleOrdersPut($controller);
                    break;
                
                case 'DELETE':
                    $this->handleOrdersDelete($controller);
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
     * GET akcije za rezervacije
     */
    private function handleOrdersGet(OrderController $controller): void
    {
        if (!isset($this->data->orders) || empty($this->data->orders)) {
            http_response_code(400);
            echo json_encode([
                'error' => 'Nedostaju parametri za pretragu'
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        // GET by user_id (pojedinačni user)
        if (isset($this->data->orders->user_id) 
            && !empty($this->data->orders->user_id)
            && !isset($this->data->orders->adminOrders) 
            && !isset($this->data->orders->filters)) {
            
            $controller->getOrdersByUser();
            return;
        }

        // GET all orders (admin/driver)
        if (isset($this->data->orders->adminOrders->all) 
            && !empty($this->data->orders->adminOrders->all)) {
            
            $this->requireAdminOrDriver();
            $controller->getAllOrders();
            return;
        }

        // GET by filters (admin)
        if (isset($this->data->orders->filters) && !empty($this->data->orders->filters)) {
            $this->requireAdmin();
            $controller->getOrdersByFilters();
            return;
        }

        // GET by date range (admin/driver)
        if (isset($this->data->orders->from_date) || isset($this->data->orders->to_date)) {
            $this->requireAdminOrDriver();
            $controller->getOrdersByDateRange();
            return;
        }

        // GET by tour_id and date (admin/driver)
        if (isset($this->data->orders->tour_id) 
            && !empty($this->data->orders->tour_id)
            && isset($this->data->orders->date) 
            && !empty($this->data->orders->date)) {
            
            $this->requireAdminOrDriver();
            $controller->getOrdersByTourAndDate();
            return;
        }

        // GET by tour_id only (admin/driver)
        if (isset($this->data->orders->tour_id) && !isset($this->data->orders->date)) {
            $this->requireAdminOrDriver();
            $controller->getOrdersByTour();
            return;
        }

        http_response_code(400);
        echo json_encode([
            'error' => 'Nevalidni parametri za pretragu'
        ], JSON_UNESCAPED_UNICODE);
    }

    /**
     * POST akcije za rezervacije
     */
    private function handleOrdersPost(OrderController $controller): void
    {
        // CREATE order
        if (isset($this->data->orders->create) && !empty($this->data->orders->create)) {
            $controller->createOrder();
            return;
        }

        http_response_code(400);
        echo json_encode([
            'error' => 'Nevalidna POST akcija'
        ], JSON_UNESCAPED_UNICODE);
    }

    /**
     * PUT akcije za rezervacije
     */
    private function handleOrdersPut(OrderController $controller): void
    {
        // UPDATE order
        if (isset($this->data->orders->update)) {
            $controller->updateOrder();
            return;
        }

        // ASSIGN driver (admin)
        if (isset($this->data->orders->selected) 
            && !empty($this->data->orders->selected)
            && isset($this->data->orders->driver) 
            && !empty($this->data->orders->driver)) {
            
            $this->requireAdmin();
            $controller->assignDriver();
            return;
        }

        // REGENERATE voucher (admin)
        if (isset($this->data->orders->voucher) && !empty($this->data->orders->voucher)) {
            $this->requireAdmin();
            $controller->regenerateVoucher();
            return;
        }

        http_response_code(400);
        echo json_encode([
            'error' => 'Nevalidna PUT akcija'
        ], JSON_UNESCAPED_UNICODE);
    }

    /**
     * DELETE akcije za rezervacije
     */
    private function handleOrdersDelete(OrderController $controller): void
    {
        // RESTORE order (admin)
        if (isset($this->data->orders->restore) && !empty($this->data->orders->restore)) {
            $this->requireAdmin();
            $controller->restoreOrder();
            return;
        }

        // DELETE order
        if (isset($this->data->orders->delete) && !empty($this->data->orders->delete)) {
            $controller->deleteOrder();
            return;
        }

        http_response_code(400);
        echo json_encode([
            'error' => 'Nevalidna DELETE akcija'
        ], JSON_UNESCAPED_UNICODE);
    }

    /**
     * Helper za admin ili driver proveru
     */
    private function requireAdminOrDriver(): void
    {
        if (!Validator::isAdmin() && !Validator::isSuper() && !Validator::isDriver()) {
            http_response_code(403);
            echo json_encode([
                'error' => 'Nemate dozvolu za ovu akciju'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    // ======================== COUNTRY ROUTES ========================

    /**
     * Country routing - detektuje HTTP metodu i akciju
     */
    private function handleCountries(): void
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $controller = new CountryController($this->db, $this->data);

        // Demo middleware za POST, PUT, DELETE
        if (in_array($method, ['POST', 'PUT', 'DELETE'])) {
            DemoMiddleware::handle();
        }

        try {
            switch ($method) {
                case 'GET':
                    $this->handleCountriesGet($controller);
                    break;
                
                case 'POST':
                    $this->handleCountriesPost($controller);
                    break;
                
                case 'PUT':
                    $this->handleCountriesPut($controller);
                    break;
                
                case 'DELETE':
                    $this->handleCountriesDelete($controller);
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
     * GET akcije za države
     */
    private function handleCountriesGet(CountryController $controller): void
    {
        // GET single country by ID
        if (isset($this->data->country->country_id) && !empty($this->data->country->country_id)) {
            $controller->getCountryById();
            return;
        }

        // GET all countries (default)
        $controller->getAllCountries();
    }

    /**
     * POST akcije za države (admin only)
     */
    private function handleCountriesPost(CountryController $controller): void
    {
        $this->requireAdmin();

        // CREATE country
        if (isset($this->data->country) && $this->data->country === "create") {
            $controller->createCountry();
            return;
        }

        // UPDATE country (POST metoda za file upload)
        if (isset($this->data->country) && $this->data->country === "update") {
            $controller->updateCountry();
            return;
        }

        http_response_code(400);
        echo json_encode([
            'error' => 'Nevalidna POST akcija'
        ], JSON_UNESCAPED_UNICODE);
    }

    /**
     * PUT akcije za države (admin only)
     */
    private function handleCountriesPut(CountryController $controller): void
    {
        $this->requireAdmin();

        if (isset($this->data->country) && $this->data->country === "update") {
            $controller->updateCountry();
            return;
        }

        http_response_code(400);
        echo json_encode([
            'error' => 'Nevalidna PUT akcija'
        ], JSON_UNESCAPED_UNICODE);
    }

    /**
     * DELETE akcije za države (admin only)
     */
    private function handleCountriesDelete(CountryController $controller): void
    {
        $this->requireAdmin();

        if (isset($this->data->country->country_id) && !empty($this->data->country->country_id)) {
            $controller->deleteCountry();
            return;
        }

        http_response_code(400);
        echo json_encode([
            'error' => 'ID države je obavezan za brisanje'
        ], JSON_UNESCAPED_UNICODE);
    }

    // ======================== CITY ROUTES ========================

    /**
     * City routing - detektuje HTTP metodu i akciju
     */
    private function handleCities(): void
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $controller = new CityController($this->db, $this->data);

        // Demo middleware za POST, PUT, DELETE
        if (in_array($method, ['POST', 'PUT', 'DELETE'])) {
            DemoMiddleware::handle();
        }

        try {
            switch ($method) {
                case 'GET':
                    $this->handleCitiesGet($controller);
                    break;
                
                case 'POST':
                    $this->handleCitiesPost($controller);
                    break;
                
                case 'PUT':
                    $this->handleCitiesPut($controller);
                    break;
                
                case 'DELETE':
                    $this->handleCitiesDelete($controller);
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
     * GET akcije za gradove
     */
    private function handleCitiesGet(CityController $controller): void
    {
        // GET full cities with deleted photos (admin)
        if (isset($this->data->cities->countryID)) {
            $controller->getFullCitiesByCountry();
            return;
        }

        // GET cities by country (alternative parameter)
        if (isset($this->data->cities->byID)) {
            $controller->getCitiesByCountryIdAlt();
            return;
        }

        // GET cities by country_id
        if (isset($this->data->cities->country_id)) {
            $controller->getCitiesByCountry();
            return;
        }

        // GET single city by id
        if (isset($this->data->cities->id)) {
            $controller->getCityById();
            return;
        }

        // GET all cities (default)
        $controller->getAllCities();
    }

    /**
     * POST akcije za gradove (admin only)
     */
    private function handleCitiesPost(CityController $controller): void
    {
        $this->requireAdmin();

        // CREATE city
        if (isset($this->data->cities) && $this->data->cities === "create") {
            $controller->createCity();
            return;
        }

        // UPDATE city - add photos
        if (isset($this->data->cities) && $this->data->cities === "update") {
            $controller->addPhotosToCity();
            return;
        }

        http_response_code(400);
        echo json_encode([
            'error' => 'Nevalidna POST akcija'
        ], JSON_UNESCAPED_UNICODE);
    }

    /**
     * PUT akcije za gradove (admin only)
     */
    private function handleCitiesPut(CityController $controller): void
    {
        $this->requireAdmin();

        // DELETE city photos (soft delete)
        if (isset($this->data->cities->ids)) {
            $controller->deleteCityPhotos();
            return;
        }

        // RESTORE city photos
        if (isset($this->data->cities->ids_restore)) {
            $controller->restoreCityPhotos();
            return;
        }

        http_response_code(400);
        echo json_encode([
            'error' => 'Nevalidna PUT akcija'
        ], JSON_UNESCAPED_UNICODE);
    }

    /**
     * DELETE akcije za gradove (admin only)
     */
    private function handleCitiesDelete(CityController $controller): void
    {
        $this->requireAdmin();

        if (!isset($this->data->cities->id)) {
            http_response_code(400);
            echo json_encode([
                'error' => 'ID grada je obavezan'
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        // RESTORE city
        if (isset($this->data->cities->restore) && $this->data->cities->restore) {
            $controller->restoreCity();
            return;
        }

        // DELETE city (soft delete)
        $controller->deleteCity();
    }

    // ======================== TOUR ROUTES ========================

    /**
     * Tour routing - detektuje HTTP metodu i akciju, zatim poziva odgovarajuću controller metodu
     */
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

    /**
     * Departure routing - detektuje HTTP metodu i akciju
     */
    private function handleDepartures(): void
    {
        // Provera autentifikacije
        if (!isset($_SESSION['user'])) {
            http_response_code(401);
            echo json_encode([
                'error' => 'Vaša sesija je istekla!'
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        // Provera autorizacije - samo Driver/Admin/Super
        if (!Validator::isDriver() && !Validator::isAdmin() && !Validator::isSuper()) {
            http_response_code(403);
            echo json_encode([
                'error' => 'Niste autorizovani da pristupite ovom resursu!'
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        $method = $_SERVER['REQUEST_METHOD'];
        $controller = new DepartureController($this->db, $this->data);

        // Demo middleware za POST, PUT, DELETE
        if (in_array($method, ['POST', 'PUT', 'DELETE'])) {
            DemoMiddleware::handle();
        }

        try {
            switch ($method) {
                case 'GET':
                    $this->handleDeparturesGet($controller);
                    break;
                
                case 'POST':
                    $this->handleDeparturesPost($controller);
                    break;
                
                case 'PUT':
                    $this->handleDeparturesPut($controller);
                    break;
                
                case 'DELETE':
                    $this->handleDeparturesDelete($controller);
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
     * GET akcije za departure
     */
    private function handleDeparturesGet(DepartureController $controller): void
    {
        // GET orders of specific departure
        if (isset($this->data->departure->id) && !empty($this->data->departure->id)) {
            $controller->getOrdersOfDeparture();
            return;
        }

        // GET departures by filters (default)
        $controller->getDeparturesByFilter();
    }

    /**
     * POST akcije za departure (admin only)
     */
    private function handleDeparturesPost(DepartureController $controller): void
    {
        $this->requireAdmin();

        if (isset($this->data->drive->create)) {
            $controller->createDeparture();
            return;
        }

        http_response_code(400);
        echo json_encode([
            'error' => 'Nevalidna POST akcija'
        ], JSON_UNESCAPED_UNICODE);
    }

    /**
     * PUT akcije za departure (admin only)
     */
    private function handleDeparturesPut(DepartureController $controller): void
    {
        $this->requireAdmin();

        if (isset($this->data->drive->update)) {
            $controller->updateDeparture();
            return;
        }

        http_response_code(400);
        echo json_encode([
            'error' => 'Nevalidna PUT akcija'
        ], JSON_UNESCAPED_UNICODE);
    }

    /**
     * DELETE akcije za departure (admin only)
     */
    private function handleDeparturesDelete(DepartureController $controller): void
    {
        $this->requireAdmin();

        if (isset($this->data->drive->delete)) {
            $controller->deleteDeparture();
            return;
        }

        http_response_code(400);
        echo json_encode([
            'error' => 'Nevalidna DELETE akcija'
        ], JSON_UNESCAPED_UNICODE);
    }
    // ======================== CHAT ROUTES ========================

    /**
     * Chat routing - detektuje HTTP metodu i akciju
     */
    private function handleChats(): void
    {
        // Demo admin provera - nema pristup live chat-u
        if (Validator::isAdmin() && Validator::isDemo()) {
            DemoGuard::denyIfDemo('Demo Admin nema pristup live chat-u.');
        }

        $method = $_SERVER['REQUEST_METHOD'];
        $controller = new ChatController($this->db, $this->data);

        try {
            switch ($method) {
                case 'GET':
                    $this->handleChatsGet($controller);
                    break;
                
                case 'POST':
                    $this->handleChatsPost($controller);
                    break;
                
                case 'PUT':
                    // Trenutno nema PUT akcija za chat
                    http_response_code(405);
                    echo json_encode([
                        'error' => 'PUT metoda nije implementirana za chat'
                    ], JSON_UNESCAPED_UNICODE);
                    break;
                
                case 'DELETE':
                    // Trenutno nema DELETE akcija za chat
                    http_response_code(405);
                    echo json_encode([
                        'error' => 'DELETE metoda nije implementirana za chat'
                    ], JSON_UNESCAPED_UNICODE);
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
     * GET akcije za chat
     */
    private function handleChatsGet(ChatController $controller): void
    {
        // POLL messages (long polling)
        if (isset($this->data->chat->poll_messages) && $this->data->chat->poll_messages) {
            $controller->pollMessages();
            return;
        }

        // GET ticket messages
        if (isset($this->data->chat->messages) && $this->data->chat->messages) {
            $controller->getTicketMessages();
            return;
        }

        // GET typing indicator
        if (isset($this->data->chat->get_typing) && $this->data->chat->get_typing) {
            $controller->getTypingIndicator();
            return;
        }

        // GET all tickets (admin only)
        if (isset($this->data->chat->admin->tickets) && $this->data->chat->admin->tickets) {
            $this->requireAdmin();
            $controller->getAllTickets();
            return;
        }

        // POLL new tickets (admin only, long polling)
        if (isset($this->data->chat->admin->poll_tickets) && $this->data->chat->admin->poll_tickets) {
            $this->requireAdmin();
            $controller->pollNewTickets();
            return;
        }

        http_response_code(400);
        echo json_encode([
            'error' => 'Nevalidni parametri za GET zahtev'
        ], JSON_UNESCAPED_UNICODE);
    }

    /**
     * POST akcije za chat
     */
    private function handleChatsPost(ChatController $controller): void
    {
        // CREATE ticket
        if (isset($this->data->chat->create_ticket) && $this->data->chat->create_ticket) {
            $controller->createTicket();
            return;
        }

        // SEND message
        if (isset($this->data->chat->send_message) && $this->data->chat->send_message) {
            $controller->sendMessage();
            return;
        }

        // UPDATE typing indicator
        if (isset($this->data->chat->typing) && $this->data->chat->typing) {
            $controller->updateTypingIndicator();
            return;
        }

        // MARK messages as read
        if (isset($this->data->chat->mark_read) && $this->data->chat->mark_read) {
            $controller->markMessagesAsRead();
            return;
        }

        // ASSIGN ticket (admin only)
        if (isset($this->data->chat->admin->assign) && $this->data->chat->admin->assign) {
            $this->requireAdmin();
            $controller->assignTicket();
            return;
        }

        // CLOSE ticket (admin only)
        if (isset($this->data->chat->admin->close) && $this->data->chat->admin->close) {
            $this->requireAdmin();
            $controller->closeTicket();
            return;
        }

        // REOPEN ticket (admin only)
        if (isset($this->data->chat->admin->reopen) && $this->data->chat->admin->reopen) {
            $this->requireAdmin();
            $controller->reopenTicket();
            return;
        }

        http_response_code(400);
        echo json_encode([
            'error' => 'Nevalidna POST akcija'
        ], JSON_UNESCAPED_UNICODE);
    }

    // ======================== HELPER METHODS ========================

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
     * Statička metoda za proveru autentifikacije
     */
    public static function checkAuth(PDO $db): bool
    {
        return User::isLoged($db);
    }
}

?>