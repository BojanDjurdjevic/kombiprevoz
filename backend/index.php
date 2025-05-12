<?php

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

$country = new Models\Country($db);
$request = $_SERVER['REQUEST_METHOD'];

$data = json_decode((file_get_contents("php://input")));

if($request === 'GET') {
    if(isset($_GET['id']) /* && is_int($_GET['id']) */) {
        $country->getCountry($_GET['id']);
    } else {
        $country->getCountries();
    }
} else if($request === 'POST') {
    
    if(isset($data->country_name)) {
        $country->name = $data->country_name;
        $country->create();
    }
} elseif($request === 'PUT') {
    if(isset($data->country_id)) {
        $country->id = $data->country_id;
        $country->name = $data->country_name;
        $country->update();
    }
} elseif($request === 'DELETE') {
    if(isset($data->country_id)) {
        $country->id = $data->country_id;
        $country->delete();
    }
}




?>