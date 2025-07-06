<?php

namespace Controllers;

use Models\Departure;
use Models\Order;
use Models\User;
use Rules\Validator;

class DepartureController {
    public $db;
    public $data;
    public $departure;

    private $user;
    private $order;

    public function __construct($db, $data)
    {
        $this->db = $db;
        $this->data = $data;
        $this->departure = new Departure($this->db);
        $this->user = new User($this->db);
        $this->order = new Order($this->db);
    }

    public function handleRequest()
    {
        $request = $_SERVER['REQUEST_METHOD'];

        if(isset($this->data->departure->id)) $this->departure->id = $this->data->departure->id; else $this->departure->id = null;
        if(isset($this->data->departure->driver_id)) $this->departure->driver_id = $this->data->departure->driver_id; else $this->departure->driver_id = null;
        if(isset($this->data->departure->tour_id)) $this->departure->tour_id = $this->data->departure->tour_id; else $this->departure->tour_id = null;
        if(isset($this->data->departure->code)) $this->departure->code = $this->data->departure->code; else $this->departure->code = null;
        if(isset($this->data->departure->path)) $this->departure->path = $this->data->departure->path; else $this->departure->path = null;
        if(isset($this->data->departure->date)) $this->departure->date = $this->data->departure->date; else $this->departure->date = null;

        switch($request) {
            case 'GET':
                if(isset($this->data->departure->id) && !empty($this->data->departure->id)) {
                    if(Validator::isDriver() || Validator::isAdmin() || Validator::isSuper()) $this->departure->getOrdersOfDep();
                    else echo json_encode(['user' => 'Niste autorizovani da vidite vožnje!']);
                } else {
                    if(Validator::isDriver() || Validator::isAdmin() || Validator::isSuper()) $this->departure->getByFilter();
                    else echo json_encode(['user' => 'Niste autorizovani da vidite vožnje!']);
                } 
                break;
            case 'POST':
                if(isset($this->data->drive->create)) {
                    
                }
                break;
            case 'PUT':
                if(isset($this->data->drive->update)) {
                    
                }
                break;
            case 'DELETE':
                if(isset($this->data->drive->delete)) {
                    
                }
                break;
        }  
    }
}