<?php

namespace Controllers;

use Models\Tour;
use Rules\Validator;

if (!defined('APP_ACCESS')) {
    http_response_code(403);
    die('Direct access forbidden');
}

use Middleware\DemoMiddleware;

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
        if (in_array($_SERVER['REQUEST_METHOD'], ['POST', 'PUT', 'DELETE'])) {
            DemoMiddleware::handle();
        }

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
                            echo json_encode($full_days, JSON_UNESCAPED_UNICODE); 
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
                        if(isset($this->data->tours->byFilter)) {
                            if(Validator::isSuper() || Validator::isAdmin()) {
                                if(isset($this->data->tours->byFilter->id)) $this->tour->id = $this->data->tours->byFilter->id;
                                else $this->tour->id = null;
                                if(isset($this->data->tours->byFilter->from_city)) $this->tour->from_city = $this->data->tours->byFilter->from_city;
                                else $this->tour->from_city = null;
                                if(isset($this->data->tours->byFilter->to_city)) $this->tour->to_city = $this->data->tours->byFilter->to_city;
                                else $this->tour->to_city = null;
                                
                                $this->tour->getByFilters();
                            } else {
                                http_response_code(403);
                                echo json_encode([
                                    'error' => 'Niste autorizovani da pristupite svim turama'
                                ], JSON_PRETTY_PRINT);
                            }
                        }
                        if(isset($this->data->tours->city->name)) {
                            $from = filter_var(
                                $this->data->tours->city->from,
                                FILTER_VALIDATE_BOOLEAN,
                                FILTER_NULL_ON_FAILURE
                            );
                            $this->data->tours->city->from ?
                            $this->tour->getToCities($this->data->tours->city->name, $from === true) :
                            $this->tour->getToCities($this->data->tours->city->name, $from === false);
                        }
                    } 
                }
                break;
            case 'POST':
                if(Validator::isAdmin() || Validator::isSuper()) {
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
                } else {
                    http_response_code(403);
                    echo json_encode([
                        'error'=> 'Niste autorizovani da dodajete ture!'
                    ], JSON_UNESCAPED_UNICODE);
                }
                break;
            case 'PUT':
                if(Validator::isAdmin() || Validator::isSuper()) {
                    $this->tour->id = $this->data->tours->id;
                    $this->tour->departures = $this->data->tours->departures;
                    $this->tour->time = $this->data->tours->time;
                    $this->tour->duration = $this->data->tours->duration;
                    $this->tour->price = $this->data->tours->price;
                    $this->tour->seats = $this->data->tours->seats;
                    if(isset($this->data->tours->update)) {
                        $this->tour->update();
                    }
                } else {
                    http_response_code(403);
                    echo json_encode([
                        'error'=> 'Niste autorizovani da uređujete ture!'
                    ], JSON_UNESCAPED_UNICODE);
                }
                break;
            case 'DELETE':
                if(Validator::isAdmin() || Validator::isSuper()) {
                    if(isset($this->data->tours->id)) {
                        $this->tour->id = $this->data->tours->id;
                        $this->tour->to_city = $this->data->tours->to_city ?? null;
                    }
                    
                    if(isset($this->data->tours->delete)) {
                        $this->tour->delete();
                    } elseif(isset($this->data->tours->restore)) {
                        $this->tour->restore();
                    } elseif(isset($this->data->tours->restoreAll)) {
                        $this->tour->restoreAll();
                    } elseif(isset($this->data->tours->permanentDelete)) {
                        $this->tour->permanentDelete();
                    }
                } else {
                    http_response_code(403);
                    echo json_encode([
                        'error'=> 'Niste autorizovani da uređujete ture!'
                    ], JSON_UNESCAPED_UNICODE);
                }
                break;
        }   
    }
}

?>