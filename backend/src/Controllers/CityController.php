<?php

namespace Controllers;

use Models\City;

class CityController {
    private $db;
    private $data;
    private $city;
    
    public function __construct($db, $data)
    {
        $this->db = $db;
        $this->data = $data;
        $this->city = new City($this->db);
    }

    public function handleRequest()
    {
        $request = $_SERVER['REQUEST_METHOD'];

        switch($request) {
            case 'GET':
                if(!$this->data->cities->id && !$this->data->cities->country_id) {
                    $this->city->getAll();
                } else {
                    if($this->data->cities->country_id) {
                        $this->city->getByCountry($this->data->cities->country_id);
                    } else 
                    $this->city->getOne($this->data->cities->id);
                }
                break;
            case 'POST':

                break;
            case 'PUT':

                break;
            case 'DELETE':

                break;
        }    
    }
}


?>