<?php

use Controllers\CountryController;

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

$countries = new CountryController($db, $data);


if($data->country_id || $data->country_name) {
    $countries->handleRequest();
}

?>