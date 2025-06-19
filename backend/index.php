<?php
session_start();
date_default_timezone_set("Europe/Belgrade");

//$_SESSION['user_id'] = 1;

use Controllers\CityController;
use Controllers\CountryController;
use Controllers\OrderController;
use Controllers\TourController;
use Controllers\UserController;

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

$sid = session_id();
$orders = new OrderController($db, $data, $sid);


if(isset($data->user)) {
    $user->handleRequest();
}
elseif(isset($data->country_id) || isset($data->country_name)) {
    $countries->handleRequest();
} elseif(isset($data->cities)) {
    $cities->handleRequest();
} elseif(isset($data->tours)) {
    $tours->handleRequest();
} elseif(isset($data->orders)) {
    $orders->handleRequest();
}

?>