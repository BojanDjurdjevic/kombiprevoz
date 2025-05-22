<?php

namespace Controllers;

use Models\Tour;

class TourController {
    public $db;
    public $data;
    public $tour;

    public function __construct($db, $data)
    {
        $this->db = $db;
        $this->data = $data;
        $this->tour = new Tour($this->db);
    }

    public function handleRequest() {
        $request = $_SERVER['REQUEST_METHOD'];

        switch($request) {
            case 'GET':
                if(isset($this->data->tours)) {
                    if($this->data->tours == 'all') {
                        $this->tour->getAll();
                    } else {
                        echo json_encode(['msg' => 'Šipak'], JSON_PRETTY_PRINT);
                    } 
                }
                break;
            case 'POST':
                if(isset($this->data->tours)) {
                    $this->tour->from_city = $this->data->tours->from_city;
                    $this->tour->to_city = $this->data->tours->to_city;
                    $this->tour->departures = $this->data->tours->departures;
                    $this->tour->time = $this->data->tours->time;
                    $this->tour->duration = $this->data->tours->duration;
                    $this->tour->price = $this->data->tours->price;
                    $this->tour->seats = $this->data->tours->seats;
                    $this->tour->create();
                }
                break;
            case 'PUT':
                $this->tour->id = $this->data->tours->id;
                $this->tour->from_city = $this->data->tours->from_city;
                $this->tour->to_city = $this->data->tours->to_city;
                $this->tour->departures = $this->data->tours->departures;
                $this->tour->time = $this->data->tours->time;
                $this->tour->duration = $this->data->tours->duration;
                $this->tour->price = $this->data->tours->price;
                $this->tour->seats = $this->data->tours->seats;
                if(isset($this->data->tours->update)) {
                    $this->tour->update();
                }
                break;
            case 'DELETE':
                if(isset($this->data->tours->id)) {
                    $this->tour->id = $this->data->tours->id;
                }
                
                if(isset($this->data->tours->delete)) {
                    $this->tour->delete();
                } elseif(isset($this->data->tours->restore)) {
                    $this->tour->restore();
                } elseif(isset($this->data->tours->restoreAll)) {
                    $this->tour->restoreAll();
                }
                break;
        }   
    }
}

?>