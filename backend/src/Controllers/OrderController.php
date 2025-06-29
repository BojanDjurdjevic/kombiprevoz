<?php

namespace Controllers;

use Models\Order;
use Rules\Validator;

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
        //isset($this->data->orders->sid) && $this->data->orders->sid == session_id()
    public function handleRequest()
    {
        if(isset($_SESSION['user_id']) && $this->data->user->id == $_SESSION['user_id'] || Validator::isSuper() || Validator::isAdmin()) {
            $request = $_SERVER['REQUEST_METHOD'];

            switch($request) {
                case 'GET':
                    if(isset($this->data->orders) && !empty($this->data->orders)) {
                        if(isset($this->data->orders->userID) && !empty($this->data->orders->userID)) {
                            $this->order->user_id = $this->data->orders->userID;
                            $this->order->getByUser();
                        }
                        if(isset($this->data->orders->ord_code) && !empty($this->data->orders->ord_code)) {
                            $this->order->code = $this->data->orders->ord_code;
                            $this->order->getByCode();
                        }
                        //if(Validator::isAdmin() || Validator::isSuper() || Validator::isDriver()) {
                            if(isset($this->data->orders->all) && !empty($this->data->orders->all)) {
                                if(Validator::isAdmin() || Validator::isSuper() || Validator::isDriver()) $this->order->getAll();
                                else echo json_encode(['orders' => 'Niste autorizovani da vidite tuđe rezervacije!']);
                            }
                            if(isset($this->data->orders->date) && !empty($this->data->orders->date) 
                            && !isset($this->data->orders->tour_id) && !empty($this->data->orders->tour_id)) {
                                $this->order->date = $this->data->orders->date;
                                if(Validator::isAdmin() || Validator::isSuper() || Validator::isDriver()) $this->order->getAllByDate();
                                else echo json_encode(['orders' => 'Niste autorizovani da vidite tuđe rezervacije!']);
                            }
                            
                            if(isset($this->data->orders->from_date) && isset($this->data->orders->to_date)) {
                                if(Validator::isAdmin() || Validator::isSuper() || Validator::isDriver())
                                $this->order->getAllByDateRange($this->data->orders->from_date, $this->data->orders->to_date);
                                else echo json_encode(['orders' => 'Niste autorizovani da vidite tuđe rezervacije!']);
                            } elseif(isset($this->data->orders->from_date)) {
                                if(Validator::isAdmin() || Validator::isSuper() || Validator::isDriver())
                                $this->order->getAllByDateRange($this->data->orders->from_date, null);
                                else echo json_encode(['orders' => 'Niste autorizovani da vidite tuđe rezervacije!']);
                            } elseif(isset($this->data->orders->to_date)) {
                                if(Validator::isAdmin() || Validator::isSuper() || Validator::isDriver())
                                $this->order->getAllByDateRange(null, $this->data->orders->to_date);
                                else echo json_encode(['orders' => 'Niste autorizovani da vidite tuđe rezervacije!']);
                            }
                            if(isset($this->data->orders->tour_id) && !empty($this->data->orders->tour_id) 
                            && isset($this->data->orders->date) && !empty($this->data->orders->date)) {
                                $this->order->tour_id = $this->data->orders->tour_id;
                                $this->order->date = $this->data->orders->date;
                                if(Validator::isAdmin() || Validator::isSuper() || Validator::isDriver())
                                $this->order->getByTourAndDate();
                                else echo json_encode(['orders' => 'Niste autorizovani da vidite tuđe rezervacije!']);
                            }
                            if(isset($this->data->orders->tour_id) && !isset($this->data->orders->date) ) {
                                $this->order->tour_id = $this->data->orders->tour_id;
                                if(Validator::isAdmin() || Validator::isSuper() || Validator::isDriver())
                                $this->order->getByTour();
                                else echo json_encode(['orders' => 'Niste autorizovani da vidite tuđe rezervacije!']);
                            }
                        //} else echo json_encode(['orders' => 'Niste autorizovani da vidite tuđe rezervacije!']);
                    } else
                    echo json_encode([
                        'status' => 401,
                        'msg' => 'Proverite podatke. Nisu pronađene rezervacije.'
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
                        if(isset($this->data->orders->create->price) && !empty($this->data->orders->create->price))
                        $this->order->price = $this->data->orders->create->price;
                        $this->order->create();
                    }
                    break;
                case 'PUT':
                    if(isset($this->data->orders->update)) {
                        if(isset($this->data->orders->update->order_id) && !empty($this->data->orders->update->order_id)) {
                            $this->order->id = $this->data->orders->update->order_id;
                        } /*
                        if(isset($this->data->orders->update->tour_id) && !empty($this->data->orders->update->tour_id)) {
                            $this->order->tour_id = $this->data->orders->update->tour_id;
                        }
                        if(isset($this->data->orders->update->user_id) && !empty($this->data->orders->update->user_id)) {
                            $this->order->user_id = $this->data->orders->update->user_id;
                        } */
                        if(isset($this->data->orders->address->add_from) && !empty($this->data->orders->address->add_from)) {
                            $this->order->new_add_from = $this->data->orders->address->add_from;
                        }
                        if(isset($this->data->orders->address->add_to) && !empty($this->data->orders->address->add_to)) {
                            $this->order->new_add_to = $this->data->orders->address->add_to;
                        }
                        if(isset($this->data->orders->new_places) && !empty($this->data->orders->new_places)) {
                            $this->order->newPlaces = $this->data->orders->new_places;
                        }
                        if(isset($this->data->orders->reschedule) && !empty($this->data->orders->reschedule)) {
                            $this->order->newDate = $this->data->orders->reschedule;
                        } /*
                        if(isset($this->data->orders->update->total) && !empty($this->data->orders->update->total)) {
                            $this->order->price = $this->data->orders->update->total;
                        }
                        if(isset($this->data->orders->update->ord_code) && !empty($this->data->orders->update->ord_code)) {
                            $this->order->code = $this->data->orders->update->ord_code;
                        } */

                        if($this->order->findUserId() || Validator::isAdmin() || Validator::isSuper()) {
                            if($this->order->checkDeadline()) {
                                if(isset($this->data->orders->address) && !empty($this->data->orders->address)) {
                                    $this->order->updateAddress();
                                }
                                if(isset($this->data->orders->new_places) && !empty($this->data->orders->new_places) 
                                && isset($this->data->orders->reschedule) && !empty($this->data->orders->reschedule)) {
                                    if($this->order->isUnlocked($this->data->orders->reschedule)) {
                                        $this->order->rescheduleAndPlaces();
                                    }
                                    else echo json_encode(["reschedule" => "Odabrani datum je ili nepostojeći, ili je već prošao. Novi odabrani polazak mora biti najmanje 24 časa od ovog momenta!"]);
                                } else {
                                    if(isset($this->data->orders->new_places) && !empty($this->data->orders->new_places)) {
                                        if($this->order->newPlaces != $this->order->places) {
                                            $this->order->updatePlaces();
                                        } else {
                                            $places = $this->order->newPlaces;
                                            echo json_encode(["places" => "Naveli ste broj mesta koji već imate u rezervaciji: $places"]);
                                        } 
                                    }
                                    if(isset($this->data->orders->reschedule) && !empty($this->data->orders->reschedule)) {
                                        if($this->order->isUnlocked($this->data->orders->reschedule)) {
                                            if($this->order->newDate != $this->order->date) {
                                                $this->order->reschedule();
                                            } else {
                                                $d = $this->order->date;
                                                echo json_encode(["reschedule" => "Naveli ste datum koji već imate u rezervaciji: $d"]);
                                            }  
                                        } else
                                           echo json_encode(["reschedule" => "Odabrani datum je ili nepostojeći, ili je već prošao. Novi odabrani polazak mora biti najmanje 24 časa od ovog momenta!"]); 
                                    }
                                }
                            } else {
                                echo json_encode(["msg" => "Nije moguće izmeniti rezervaciju, jer je do polaska ostalo manje od 48 sati."], JSON_PRETTY_PRINT);
                            }
                        } else echo json_encode(["msg" => "Niste autorizovani da izmenite ovu rezervaciju!"], JSON_PRETTY_PRINT);
                    }
                    break;
                case 'DELETE':
                    if(isset($this->data->orders->delete) && !empty($this->data->orders->delete)) {
                        $this->order->id = $this->data->orders->delete->order_id;
                        //$this->order->user_id = $this->data->orders->delete->user_id;
                        if($this->order->findUserId() || Validator::isAdmin() || Validator::isSuper()) {
                            if($this->order->checkDeadline()) {
                                $this->order->delete();
                            } else
                                echo json_encode(["msg" => "Nije moguće izmeniti rezervaciju, jer je do polaska ostalo manje od 48 sati."], JSON_PRETTY_PRINT);
                        } else echo json_encode(["msg" => "Niste autorizovani da izmenite ovu rezervaciju!"], JSON_PRETTY_PRINT);
                    }
                    if(isset($this->data->orders->restore) && !empty($this->data->orders->restore)) {
                        $this->order->id = $this->data->orders->restore->order_id;
                        if(Validator::isAdmin() || Validator::isSuper()) {
                            $this->order->restore();
                        } else echo json_encode(["msg" => "Niste autorizovani da aktivirate ovu rezervaciju!"], JSON_PRETTY_PRINT);
                    }
                    break;
            }    
        } else
        echo json_encode([
            'msg' => 'Vaša sesija je istekla.',
            'user' => 404,
            'userid' => $_SESSION['user_id']
        ], JSON_PRETTY_PRINT);
        
    }
}

?>