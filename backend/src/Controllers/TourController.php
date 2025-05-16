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
                
                break;
            case 'PUT':
                
                break;
            case 'DELETE':
                
                break;
        }   
    }
}

?>