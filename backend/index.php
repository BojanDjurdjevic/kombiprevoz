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

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
/*
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Access-Control-Allow-Headers, Content-Type,
Access-Control-Allow-Methods, Authorization, X-Requested-With'); */

require __DIR__ . "../vendor/autoload.php";

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$database = new Database();
$db = $database->connect();

$data = json_decode((file_get_contents("php://input")));

$user = new UserController($db, $data);
$countries = new CountryController($db, $data);
$cities = new CityController($db, $data);
$tours = new TourController($db, $data);
$orders = new OrderController($db, $data, $sid);
$departures = new DepartureController($db, $data);

$isLoged = User::isLoged($data->user->id, $data->user->email, $db);


if(isset($data->users) && !empty($data->users)) {
    $user->handleRequest();
}
if(isset($data->country_id) || isset($data->country_name)) {
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
if(isset($data->drive) && !empty($data->drive)) {
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