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
                if(!isset($this->data->cities->id) && !isset($this->data->cities->country_id) && !isset($this->data->cities->countryID)) {
                    if(isset($this->data->cities->byID)) {
                        $this->city->country_id = $this->data->cities->byID;
                        $this->city->byID();
                    } else
                    $this->city->getAll();
                } else {
                    if(isset($this->data->cities->countryID)) {
                        $this->city->country_id = $this->data->cities->countryID;
                        return $this->city->getFullCitiesByCountryId();
                    } elseif(isset($this->data->cities->country_id)) {
                        $this->city->getByCountry($this->data->cities->country_id);
                    } else 
                    $this->city->getOne($this->data->cities->id);
                }
                break;
            case 'POST':
                if(isset($this->data->cities) && $this->data->cities == "create") {
                    $this->city->name = $this->data->name;
                    $this->city->country_id = $this->data->country_id;
                    $this->city->photos = $this->data->photos;
                    $this->city->create();
                }
                if(isset($this->data->cities) && $this->data->cities == "update") {
                    $this->city->name = $this->data->name;
                    $this->city->id = $this->data->city_id;
                    $this->city->photos = $this->data->photos;
                    $this->city->update();
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