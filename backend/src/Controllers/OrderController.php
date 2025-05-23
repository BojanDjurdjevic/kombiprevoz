<?php

namespace Controllers;

use Models\Order;

class OrderController {
    public $db;
    public $data;
    public $order;

    public function __construct($db, $data)
    {
        $this->db = $db;
        $this->data = $data;
        $this->order = new Order($this->db);
    }

    public function handleRequest()
    {
        $request = $_SERVER['REQUEST_METHOD'];

        switch($request) {
            case 'GET':
                if(isset($this->data->orders->all)) {
                    $this->order->getAll();
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