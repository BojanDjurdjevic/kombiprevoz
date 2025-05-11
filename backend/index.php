<?php

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');

require __DIR__ . "../vendor/autoload.php";

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$database = new Database();
$db = $database->connect();

$countries = new Models\Country($db);
$request = $_SERVER['REQUEST_METHOD'];

if($request === 'GET') {
    if(isset($_GET['id'])) {
        $countries->getCountry($_GET['id']);
    } else {
        $countries->getCountries();
    }
} else
echo 'NOT';




?>