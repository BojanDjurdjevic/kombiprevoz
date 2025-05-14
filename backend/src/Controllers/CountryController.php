<?php

namespace Controllers;

//use Database;
use Models\Country;

class CountryController {
    private $db;
    private $country;
    public $data;

    public function __construct($db, $data)
    {
        $this->db = $db;
        $this->country = new Country($this->db);
        $this->data = $data;
    }

    public function handleRequest() {
        $request = $_SERVER['REQUEST_METHOD'];

        if($request === 'GET') {
            if($this->data->country_id != "" || $this->data->country_id != null) {
                $this->country->getCountry($this->data->country_id);
            } else {
                $this->country->getCountries();
            }
        } else if($request === 'POST') {
            
            if(isset($this->data->country_name)) {
                $this->country->name = $this->data->country_name;
                $this->country->create();
            }
        } elseif($request === 'PUT') {
            if(isset($this->data->country_id)) {
                $this->country->id = $this->data->country_id;
                $this->country->name = $this->data->country_name;
                $this->country->update();
            }
        } elseif($request === 'DELETE') {
            if(isset($this->data->country_id)) {
                $this->country->id = $this->data->country_id;
                $this->country->delete();
            }
        }
    }
}


?>