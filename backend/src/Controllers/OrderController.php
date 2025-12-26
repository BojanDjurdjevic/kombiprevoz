<?php

namespace Controllers;

use Middleware\DemoMiddleware;
use Models\Order;
use PDOException;
use Rules\Validator;

if (!defined('APP_ACCESS')) {
    http_response_code(403);
    die('Direct access forbidden');
}

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
        if(isset($_SESSION['user']) && $this->data->orders->user_id == $_SESSION['user']['id'] || Validator::isSuper() || Validator::isAdmin()) {
            $request = $_SERVER['REQUEST_METHOD'];

            switch($request) {
                case 'GET':
                    if(isset($this->data->orders) && !empty($this->data->orders) ) {
                        if(isset($this->data->orders->user_id) && !empty($this->data->orders->user_id) 
                            && !isset($this->data->orders->adminOrders) && !isset($this->data->orders->filters)) {
                            $this->order->user_id = $this->data->orders->user_id;
                            $this->order->getByUser();
                        } /*
                        if(isset($this->data->orders->ord_code) && !empty($this->data->orders->ord_code)) {
                            $this->order->code = $this->data->orders->ord_code;
                            $this->order->getByCode();
                        } */
                        //if(Validator::isAdmin() || Validator::isSuper() || Validator::isDriver()) {
                            if(isset($this->data->orders->adminOrders->all) && !empty($this->data->orders->adminOrders->all)) {
                                if(Validator::isAdmin() || Validator::isSuper() || Validator::isDriver()) 
                                $this->order->getAll($this->data->orders->adminOrders->in24, $this->data->orders->adminOrders->in48);
                                else {
                                    http_response_code(402);
                                    echo json_encode([
                                        'error' => 'Niste autorizovani da vidite sve rezervacije!'
                                    ]);
                                } 
                            }
                            if(isset($this->data->orders->filters) && !empty($this->data->orders->filters)) {
                                $email = null;
                                if(isset($this->data->orders->filters->departure) && !empty($this->data->orders->filters->departure))
                                $this->order->date = $this->data->orders->filters->departure;
                                else $this->order->date = null;
                                if(isset($this->data->orders->filters->code) && !empty($this->data->orders->filters->code))
                                $this->order->code = $this->data->orders->filters->code;
                                else $this->order->code = null;
                                if(isset($this->data->orders->filters->from_city) && !empty($this->data->orders->filters->from_city))
                                $this->order->from_city = $this->data->orders->filters->from_city;
                                else $this->order->from_city = null;
                                if(isset($this->data->orders->filters->to_city) && !empty($this->data->orders->filters->to_city))
                                $this->order->to_city = $this->data->orders->filters->to_city;
                                else $this->order->to_city = null;
                                if(isset($this->data->orders->filters->tour_id) && !empty($this->data->orders->filters->tour_id))
                                $this->order->tour_id = $this->data->orders->filters->tour_id;
                                else $this->order->tour_id = null;
                                if(isset($this->data->orders->filters->user_email) && !empty($this->data->orders->filters->user_email))
                                $email = $this->data->orders->filters->user_email;
                                if(Validator::isAdmin() || Validator::isSuper()) 
                                $this->order->getAllByFilter($email);
                                else {
                                    http_response_code(422);
                                    echo json_encode([
                                        'error' => 'Niste autorizovani da vidite tuđe rezervacije!'
                                    ]);
                                } 
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
                    } else {
                        http_response_code(401);
                        echo json_encode([
                            'error' => 'Proverite podatke. Nisu pronađene rezervacije.'
                        ]);
                    }
                    /*
                    if(isset($this->data->adminOrders) && !empty($this->data->adminOrders)) {

                    } */
                    break;
                case 'POST':
                    if(isset($this->data->orders->create) && !empty($this->data->orders->create)) { /*
                        $this->order->tour_id = $this->data->orders->create->tour_id;
                        $this->order->user_id = $this->data->orders->create->user_id;
                        $this->order->places = $this->data->orders->create->places;
                        $this->order->add_from = $this->data->orders->create->add_from;
                        $this->order->add_to = $this->data->orders->create->add_to;
                        $this->order->date = $this->data->orders->create->date;
                        if(isset($this->data->orders->create->price) && !empty($this->data->orders->create->price))
                        $this->order->price = $this->data->orders->create->price; */
                        $this->order->items = $this->data->orders;
                        $this->order->create();
                    }
                    break;
                case 'PUT':
                    if(isset($this->data->orders->update)) {
                        if(isset($this->data->orders->update->id) && !empty($this->data->orders->update->id)) {
                            $this->order->id = $this->data->orders->update->id;
                            $this->order->getFromDB($this->order->id);
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
                            if(isset($this->data->orders->reschedule->outDate) && !empty($this->data->orders->reschedule->outDate))
                            $this->order->newDate = $this->data->orders->reschedule->outDate;
                            else $this->order->newDate = null;
                            if(isset($this->data->orders->reschedule->inDate) && !empty($this->data->orders->reschedule->inDate))
                            $this->order->newDateIn = $this->data->orders->reschedule->inDate;
                            else $this->order->newDateIn = null;
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
                                    $address = $this->order->updateAddress();
                                } else
                                $address = [
                                    'msg' => ''
                                ];
                                /*
                                if(isset($this->data->orders->new_places) && !empty($this->data->orders->new_places) 
                                && isset($this->data->orders->reschedule) && !empty($this->data->orders->reschedule)) {
                                    if($this->order->isUnlocked($this->data->orders->reschedule)) {
                                        $this->order->rescheduleAndPlaces();
                                    }
                                    else echo json_encode(["reschedule" => "Odabrani datum je ili nepostojeći, ili je već prošao. Novi odabrani polazak mora biti najmanje 24 časa od ovog momenta!"]);
                                } else { */
                                    if(isset($this->data->orders->new_places) && !empty($this->data->orders->new_places)) {
                                        if($this->order->newPlaces != $this->order->places) {
                                            $myplaces = $this->order->updatePlaces();
                                        } else {
                                            $places = $this->order->newPlaces; /*
                                            http_response_code(401);
                                            echo json_encode(["error" => "Naveli ste broj mesta koji već imate u rezervaciji: $places"]); */
                                            $myplaces = ["error" => "Naveli ste broj mesta koji već imate u rezervaciji: $places"];
                                        } 
                                    } else $myplaces = ['msg' => ''];
                                    if(isset($this->data->orders->reschedule) && (!empty($this->data->orders->reschedule->outDate)
                                        || !empty($this->data->orders->reschedule->inDate))) {
                                        if($this->order->isUnlocked($this->data->orders->reschedule->outDate) ||
                                            $this->order->isUnlocked($this->data->orders->reschedule->inDate) || Validator::isSuper()
                                            || Validator::isAdmin()) {
                                            if($this->order->newDate != $this->order->date && $this->order->items->items[1]->date != 
                                                $this->order->newDateIn) {
                                                $reschedule = $this->order->reschedule();
                                            } else {
                                                $d = $this->order->date;
                                                $dIn = $this->order->items->items[1]->date; /*
                                                http_response_code(422);
                                                echo json_encode(["error" => "Naveli ste datume koje već imate u rezervaciji: 
                                                                   Polazak - $d, Povratak - $dIn"]); */
                                                $reschedule = ["error" => "Naveli ste datume koje već imate u rezervaciji: 
                                                                Polazak - $d, Povratak - $dIn"];
                                            }  
                                        } else {
                                            http_response_code(422);
                                            echo json_encode(["error" => "Odabrani datum je ili nepostojeći, ili je već prošao. Novi odabrani polazak mora biti najmanje 24 časa od ovog momenta!"]);
                                            exit(); 
                                        }
                                           
                                    } else $reschedule = ['msg' => ''];
                                //}
                            } else {
                                http_response_code(422);
                                echo json_encode(["error" => "Nije moguće izmeniti rezervaciju, jer je do polaska ostalo manje od 48 sati."], JSON_PRETTY_PRINT);
                                exit();
                            }
                        } else {
                            http_response_code(402);
                            echo json_encode(["error" => "Niste autorizovani da izmenite ovu rezervaciju!"], JSON_PRETTY_PRINT);
                            exit();
                        }

                        if(!isset($address['error']) && !isset($myplaces['error']) && !isset($reschedule['error'])) {
                            echo json_encode([
                                'success' => true,
                                'msg' => [
                                    $address['msg'],
                                    " ",
                                    $myplaces['msg'],
                                    " ",
                                    $reschedule['msg']
                                ]
                            ]);
                        } else {
                            if(isset($address['error']) or isset($myplaces['error']) or isset($reschedule['error'])) {
                                http_response_code(500);
                                echo json_encode(['error' => 'Došlo je do greške pri konekciji na bazu!']);
                                exit();
                            }
                            $arr = [$address, $myplaces, $reschedule];
                            $errors = [];
                            foreach($arr as $a) {
                                if(is_array($a) && isset($a['error'])) $errors[] = $a['error'];
                            }
                            http_response_code(422);
                            header('Content-Type: application/json');
                            echo json_encode([
                                'error' => $errors
                            ]);
                        }
                    }
                    if(isset($this->data->orders->selected) && !empty($this->data->orders->selected)
                        && isset($this->data->orders->driver) && !empty($this->data->orders->driver)) {
                            $this->order->selected = $this->data->orders->selected;
                            $this->order->driver = $this->data->orders->driver;
                            $this->order->tour_id = $this->data->orders->tour_id;
                        ;
                            $this->order->assignDriverTo();
                    }
                    if(isset($this->data->orders->voucher) && !empty($this->data->orders->voucher)) {
                        $this->order->id = $this->data->orders->voucher->item_id;
                        $this->order->getFromDB($this->order->id);
                        try {
                            $mydata = $this->order->reGenerateVoucher();
                            $this->order->sendVoucher($mydata['email'], $mydata['name'], $mydata['path'], $this->order->code, '');
                            http_response_code(200);
                            header('Content-Type: application/json');
                            echo json_encode([
                                'success' => true,
                                'msg' => 'Vaučer je uspešno poslat na email adresu korisnika!'
                            ]);
                        } catch(PDOException $e) {
                            http_response_code(500);
                            header('Content-Type: application/json');
                            echo json_encode([
                                'error' => 'Došlo je do greške pri konekciji na bazu podataka!',
                                'msg' => $e->getMessage()
                            ]);
                        }
                    }
                    break;
                case 'DELETE':
                    if(isset($this->data->orders->delete) && !empty($this->data->orders->delete)) {
                        $this->order->id = $this->data->orders->delete->item_id;
                        //$this->order->user_id = $this->data->orders->delete->user_id;
                        if($this->order->findUserId() || Validator::isAdmin() || Validator::isSuper()) {
                            if($this->order->checkDeadline() || Validator::isAdmin() || Validator::isSuper()) {
                                $this->order->delete();
                            } else {
                                http_response_code(422);
                                echo json_encode(["error" => "Nije moguće izmeniti rezervaciju, jer je do polaska ostalo manje od 48 sati."], JSON_PRETTY_PRINT);
                            }
                        } else {
                            http_response_code(422);
                            echo json_encode(["error" => "Niste autorizovani da izmenite ovu rezervaciju!"], JSON_PRETTY_PRINT);
                        } 
                    }
                    if(isset($this->data->orders->restore) && !empty($this->data->orders->restore)) {
                        $this->order->id = $this->data->orders->restore->item_id;
                        if(Validator::isAdmin() || Validator::isSuper()) {
                            $this->order->restore();
                        } else {
                            http_response_code(422);
                            echo json_encode(["error" => "Niste autorizovani da aktivirate ovu rezervaciju!"], JSON_PRETTY_PRINT);
                        }
                    }
                    break;
            }    
        } else {
            http_response_code(422);
            echo json_encode([
                'error' => 'Vaša sesija je istekla.'
            ], JSON_PRETTY_PRINT);
        }
        
        
    }
}

?>