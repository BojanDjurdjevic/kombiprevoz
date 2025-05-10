<?php

require __DIR__ . "../vendor/autoload.php";

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$db = new Database();
//$db->connect();

$countries = new Models\Country($db->connect());

$countries->getCountries();

echo "Hello World!"


?>