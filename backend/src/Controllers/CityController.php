<?php

namespace Controllers;

use Models\City;

class CityController {
    private $db;
    private $data;
    public $city;
    
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
                if(!isset($this->data->cities->id) && !isset($this->data->cities->country_id)) {
                    $this->city->getAll();
                } else {
                    if(isset($this->data->cities->country_id)) {
                        $this->city->getByCountry($this->data->cities->country_id);
                    } else 
                    $this->city->getOne($this->data->cities->id);
                }
                break;
            case 'POST':
                if(isset($this->data->cities->name) && isset($this->data->cities->country_id)) {
                    $this->city->name = $this->data->cities->name;
                    $this->city->country_id = $this->data->cities->country_id;
                    $this->city->create();
                }
                break;
            case 'PUT':
                if(isset($this->data->cities->id)) {
                    $this->city->id = $this->data->cities->id;
                    $this->city->name = $this->data->cities->name;
                    $this->city->country_id = $this->data->cities->country_id;
                    $this->city->update();
                }
                break;
            case 'DELETE':
                if(isset($this->data->cities->id)) {
                    $this->city->id = $this->data->cities->id;
                    $this->city->delete();
                }
                break;
        }    
    }
}


?>