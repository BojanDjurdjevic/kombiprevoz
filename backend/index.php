<?php
/* OLD CODE
define('APP_ACCESS', true);
ini_set('session.gc_maxlifetime', 3600);
session_start();
$sid = session_id();
date_default_timezone_set("Europe/Belgrade");

use Controllers\ChatController;
use Controllers\CityController;
use Controllers\CountryController;
use Controllers\DepartureController;
use Controllers\OrderController;
use Controllers\TourController;
use Controllers\UserController;
use Helpers\Logger;

use Models\User;
use Rules\Validator;
use Rules\Input;

header('Access-Control-Allow-Origin: http://localhost:5173');
header('Content-Type: application/json');
//header('Content-Type: multipart/form-data');

header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS'); /*
header('Access-Control-Allow-Headers: Access-Control-Allow-Headers, Content-Type,
Access-Control-Allow-Methods, Authorization, X-Requested-With'); */ 
/*
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Access-Control-Allow-Credentials: true');

require __DIR__ . "../vendor/autoload.php";

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$database = Database::getInstance();

$db = $database->connect();

$data = Input::all();



$user = new UserController($db, $data);
$countries = new CountryController($db, $data);
$cities = new CityController($db, $data);
$tours = new TourController($db, $data);
$orders = new OrderController($db, $data, $sid);
$departures = new DepartureController($db, $data);
$chats = new ChatController($db, $data); 

if(isset($data->user) && !empty($data->user)) 
$isLoged = User::isLoged( $db);
else $isLoged = false;

Logger::rotateLog('errors.log', 10);
Logger::rotateLog('security.log', 10);
Logger::rotateLog('audit.log', 50);


if(isset($data->users) && !empty($data->users)) {
    
    $user->handleRequest();
}
if(isset($data->orders) && !empty($data->orders) /* || (isset($data->adminOrders) && !empty($data->adminOrders)) */ /*---) {
    if(isset($_SESSION['user'])) $orders->handleRequest();
    else {
        http_response_code(422);
        echo json_encode([
            'user' => 404,
            'error' => 'Vaša sesija je istekla, molimo Vas da se ulogujete ponovo!'
        ], JSON_PRETTY_PRINT);
    }
}

if(isset($data->country) && !empty($data->country)) {
    $countries->handleRequest();
} elseif(isset($data->cities) && !empty($data->cities)) {
    $cities->handleRequest();
} elseif(isset($data->tours) && !empty($data->tours)) {
    $tours->handleRequest();
} 

if(isset($data->departure) && !empty($data->departure)) {
    if(isset($_SESSION['user']) && Validator::isDriver() || Validator::isAdmin() || Validator::isSuper()) 
    $departures->handleRequest();
    else {
        echo json_encode([
            'user' => 404,
            'msg' => 'Vaša sesija je istekla, ili niste autorizovani da pristupite!'
        ], JSON_PRETTY_PRINT);
    }
}

if(isset($data->chat) && !empty($data->chat)) {
    $chats->handleRequest();
}
*/

declare(strict_types=1);

define('APP_ACCESS', true);

// ======================== SESSION & TIMEZONE ========================
ini_set('session.gc_maxlifetime', '3600');
session_start();
$sid = session_id();
date_default_timezone_set("Europe/Belgrade");

// ======================== AUTOLOAD & ENV ========================
require __DIR__ . "/vendor/autoload.php";

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// ======================== CORS HEADERS ========================
header('Access-Control-Allow-Origin: http://localhost:5173');
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Access-Control-Allow-Credentials: true');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// ======================== IMPORTS ========================
use Core\Router;
use Helpers\Logger;
use Rules\Input;

// ======================== DATABASE CONNECTION ========================
try {
    $database = Database::getInstance();
    $db = $database->connect();
} catch (Exception $e) {
    Logger::error('Database connection failed in index.php', [
        'error' => $e->getMessage(),
        'file' => __FILE__,
        'line' => __LINE__
    ]);
    
    http_response_code(500);
    echo json_encode([
        'error' => 'Greška pri konekciji na bazu podataka'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// ======================== INPUT HANDLING ========================
try {
    $data = Input::all();
} catch (Exception $e) {
    Logger::error('Failed to parse input in index.php', [
        'error' => $e->getMessage(),
        'file' => __FILE__,
        'line' => __LINE__
    ]);
    
    http_response_code(400);
    echo json_encode([
        'error' => 'Nevalidan format podataka'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// ======================== LOG ROTATION ========================
Logger::rotateLog('errors.log', 10);
Logger::rotateLog('security.log', 10);
Logger::rotateLog('audit.log', 50);

// ======================== ROUTING ========================
try {
    $router = new Router($db, $data, $sid);
    $router->route();
} catch (Exception $e) {
    Logger::error('Routing error in index.php', [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
        'file' => __FILE__,
        'line' => __LINE__
    ]);
    
    http_response_code(500);
    echo json_encode([
        'error' => 'Greška pri obradi zahteva'
    ], JSON_UNESCAPED_UNICODE);
}
?>