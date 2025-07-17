<?php
session_start();
$sid = session_id();
date_default_timezone_set("Europe/Belgrade");

use Controllers\CityController;
use Controllers\CountryController;
use Controllers\DepartureController;
use Controllers\OrderController;
use Controllers\TourController;
use Controllers\UserController;
use Models\User;
use Rules\Validator;
use Rules\Input;

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Content-Type: multipart/form-data');

header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS'); /*
header('Access-Control-Allow-Headers: Access-Control-Allow-Headers, Content-Type,
Access-Control-Allow-Methods, Authorization, X-Requested-With'); */
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Access-Control-Allow-Credentials: true');

require __DIR__ . "../vendor/autoload.php";

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$database = new Database();
$db = $database->connect();
/*
$method = $_SERVER['REQUEST_METHOD'];
if($method === 'GET' && isset($_GET['data'])) {
    $data = json_decode(json_encode($_GET['data']));
} else $data = json_decode((file_get_contents("php://input")), true); */

$data = Input::all();
//$get = $_GET['data'];
//echo json_encode(['primio' => $data]);
//die();
$user = new UserController($db, $data);
$countries = new CountryController($db, $data);
$cities = new CityController($db, $data);
$tours = new TourController($db, $data);
$orders = new OrderController($db, $data, $sid);
$departures = new DepartureController($db, $data);

if(isset($data->user) && !empty($data->user->id)) 
$isLoged = User::isLoged($data->user->id, $data->user->email, $db);
else $isLoged = false;


if(isset($data->users) && !empty($data->users)) {
    $user->handleRequest();
}
if(isset($data->country) && !empty($data->country)) {
    $countries->handleRequest();
} elseif(isset($data->cities) && !empty($data->cities)) {
    $cities->handleRequest();
} elseif(isset($data->tours) && !empty($data->tours)) {
    $tours->handleRequest();
} 
if(isset($data->orders) && !empty($data->orders)) {
    if($isLoged) $orders->handleRequest();
    else {
        echo json_encode([
            'user' => 404,
            'msg' => 'Vaša sesija je istekla, molimo Vas da se ulogujete ponovo!'
        ], JSON_PRETTY_PRINT);
    }
}
if(isset($data->departure) && !empty($data->departure)) {
    if($isLoged && Validator::isDriver() || Validator::isAdmin() || Validator::isSuper()) 
    $departures->handleRequest();
    else {
        echo json_encode([
            'user' => 404,
            'msg' => 'Vaša sesija je istekla, ili niste autorizovani da pristupite!'
        ], JSON_PRETTY_PRINT);
    }
}
?>