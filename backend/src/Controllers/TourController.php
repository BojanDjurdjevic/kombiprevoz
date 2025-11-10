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
                    if(isset($this->data->tours->tour) && $this->data->tours->tour == 'all') {
                        $this->tour->getAll();
                    } else {
                        if(isset($this->data->tours->days)) {
                            $this->tour->from_city = $this->data->tours->days->from;
                            $this->tour->to_city = $this->data->tours->days->to;
                            $full_days = $this->tour->fullyBooked($this->data->tours->days->format);
                            echo json_encode($full_days, JSON_PRETTY_PRINT); 
                        }
                        if(isset($this->data->tours->search)) {
                            $this->tour->from_city = $this->data->tours->search->from;
                            $this->tour->to_city = $this->data->tours->search->to;
                            $this->tour->date = $this->data->tours->search->date;
                            if(isset($this->data->tours->search->inbound) && !empty($this->data->tours->search->inbound))
                            $this->tour->inbound = $this->data->tours->search->inbound;
                            else $this->tour->inbound = null;
                            $this->tour->requestedSeats = $this->data->tours->search->seats;
                            $this->tour->getBySearch();
                        } 
                    } 
                }
                break;
            case 'POST':
                if(isset($this->data->tours)) {
                    $this->tour->from_city = $this->data->tours->from;
                    $this->tour->to_city = $this->data->tours->to;
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