<?php

namespace Controllers;

use Models\Order;

class OrderController {
    public $db;
    public $data;
    public $order;
    public $sid;

    public function __construct($db, $data, $sid)
    {
        $this->db = $db;
        $this->data = $data;
        $this->order = new Order($this->db);
        $this->sid = $sid;
    }

    public function handleRequest()
    {
        if(isset($this->data->orders->sid) && $this->data->orders->sid == session_id()) {
            $request = $_SERVER['REQUEST_METHOD'];

            switch($request) {
                case 'GET':
                    if(isset($this->data->orders) && !empty($this->data->orders)) {
                        if(isset($this->data->orders->all)) {
                            $this->order->getAll();
                        }
                        if(isset($this->data->orders->date)) {
                            $this->order->date = $this->data->orders->date;
                            $this->order->getAllByDate();
                        }
                        if(isset($this->data->orders->userID)) {
                            $this->order->user_id = $this->data->orders->userID;
                            $this->order->getByUser();
                        }
                        if(isset($this->data->orders->from_date) && isset($this->data->orders->to_date)) {
                            $this->order->getAllByDateRange($this->data->orders->from_date, $this->data->orders->to_date);
                        } elseif(isset($this->data->orders->from_date)) {
                            $this->order->getAllByDateRange($this->data->orders->from_date, null);
                        } elseif(isset($this->data->orders->to_date)) {
                            $this->order->getAllByDateRange(null, $this->data->orders->to_date);
                        }
                        if(isset($this->data->orders->tour_id) && isset($this->data->orders->date)) {
                            $this->order->tour_id = $this->data->orders->tour_id;
                            $this->order->date = $this->data->orders->date;
                            $this->order->getByTourAndDate();
                        }
                        if(isset($this->data->orders->tour_id) && !isset($this->data->orders->date) ) {
                            $this->order->tour_id = $this->data->orders->tour_id;
                            $this->order->getByTour();
                        }
                    } else
                    echo json_encode([
                        'status' => 401,
                        'msg' => 'Peoverite podatke. Nisu pronađene rezervacije.'
                    ]);
                    break;
                case 'POST':
                    if(isset($this->data->orders->create)) {
                        $this->order->tour_id = $this->data->orders->create->tour_id;
                        $this->order->user_id = $this->data->orders->create->user_id;
                        $this->order->places = $this->data->orders->create->places;
                        $this->order->add_from = $this->data->orders->create->add_from;
                        $this->order->add_to = $this->data->orders->create->add_to;
                        $this->order->date = $this->data->orders->create->date;
                        $this->order->price = $this->data->orders->create->price;
                        $this->order->create();
                    }
                    break;
                case 'PUT':
                    if(isset($this->data->orders->update)) {
                        if(isset($this->data->orders->update->id) && !empty($this->data->orders->update->id)) {
                            $this->order->id = $this->data->orders->update->id;
                        }
                        if(isset($this->data->orders->update->add_from) && !empty($this->data->orders->update->add_from)) {
                            $this->order->add_from = $this->data->orders->update->add_from;
                        }
                        if(isset($this->data->orders->update->add_to) && !empty($this->data->orders->update->add_to)) {
                            $this->order->add_to = $this->data->orders->update->add_to;
                        }
                        if(isset($this->data->orders->update->places) && !empty($this->data->orders->update->places)) {
                            $this->order->places = $this->data->orders->update->places;
                        }
                    }
                    break;
                case 'DELETE':
                    if(isset($this->data->orders->delete)) {
                        $this->order->id = $this->data->orders->delete->order_id;
                        $this->order->user_id = $this->data->orders->delete->user_id;
                        $this->order->delete();
                    }
                    break;
            }    
        } else
        echo json_encode([
            'msg' => 'Vaša sesija je istekla.',
            session_id()
        ], JSON_PRETTY_PRINT);
        
    }
}

?>