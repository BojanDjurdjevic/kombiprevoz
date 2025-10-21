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
            if(empty($this->data->country->country_id)) {
                $this->country->getCountries();
            } else {
                $this->country->getCountry($this->data->country->country_id); 
            }
        } else if($request === 'POST') {
            if(isset($this->data->country) && !empty($this->data->country) && $this->data->country == "create") {
                $this->country->name = $this->data->country_name;
                $this->country->flag = $this->data->files['flag'];
                $this->country->create();
            }
        } elseif($request === 'PUT') {
            if(isset($this->data->country->country_id) && !empty($this->data->country->country_id)) {
                $this->country->id = $this->data->country->country_id;
                $this->country->name = $this->data->country->country_name;
                $this->country->update();
            }
        } elseif($request === 'DELETE') {
            if(isset($this->data->country->country_id) && !empty($this->data->country->country_id)) {
                $this->country->id = $this->data->country->country_id;
                $this->country->delete();
            }
        }
    }
}


?>