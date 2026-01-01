<?php
/*
namespace Controllers;

use Guards\DemoGuard;
use Helpers\Logger;
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
            $isDemo = !empty($_SESSION['user']['is_demo']);

            $request = $_SERVER['REQUEST_METHOD'];
            if (in_array($_SERVER['REQUEST_METHOD'], ['POST', 'PUT', 'DELETE'])) {
                DemoMiddleware::handle();
            }

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
                        /* ------------------------------
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
                    break;
                case 'POST':
                    if(isset($this->data->orders->create) && !empty($this->data->orders->create)) {
                        if($isDemo) { // NOVO: demo fake create
                            echo json_encode([
                                'success' => true,
                                'msg' => 'Demo rezervacija je kreirana lokalno i privremeno.',
                                'fake' => true
                            ]);
                            exit();
                        }
                        $this->order->items = $this->data->orders;
                        $this->order->create();
                    }
                    break;
                case 'PUT':
                    /*
                    if(isset($this->data->orders->update)) {
                        if($isDemo) {
                            echo json_encode([
                                'success' => true,
                                'msg' => 'Demo update izvršen lokalno i privremeno.',
                                'fake' => true
                            ]);
                            exit();
                        }

                        if(isset($this->data->orders->update->id) && !empty($this->data->orders->update->id)) {
                            $this->order->id = $this->data->orders->update->id;
                            $this->order->getFromDB($this->order->id);
                        }
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
                        }

                        if($this->order->findUserId() || Validator::isAdmin() || Validator::isSuper()) {
                            if($this->order->checkDeadline()) {
                                if(isset($this->data->orders->address) && !empty($this->data->orders->address)) {
                                    $address = $this->order->updateAddress();
                                } else
                                $address = [
                                    'msg' => ''
                                ];

                                if(isset($this->data->orders->new_places) && !empty($this->data->orders->new_places)) {
                                    if($this->order->newPlaces != $this->order->places) {
                                        $myplaces = $this->order->updatePlaces();
                                    } else {
                                        $places = $this->order->newPlaces; 
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
                                            $dIn = $this->order->items->items[1]->date; 

                                            $reschedule = ["error" => "Naveli ste datume koje već imate u rezervaciji: 
                                                            Polazak - $d, Povratak - $dIn"];
                                        }  
                                    } else {
                                        http_response_code(422);
                                        echo json_encode(["error" => "Odabrani datum je ili nepostojeći, ili je već prošao. Novi odabrani polazak mora biti najmanje 24 časa od ovog momenta!"]);
                                        exit(); 
                                    }
                                       
                                } else $reschedule = ['msg' => ''];

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
                    } */
                    /* --------------------------------------------------------------------
                        if (isset($this->data->orders->update)) {
                            // Demo guard
                            if ($isDemo) {
                                echo json_encode([
                                    'success' => true,
                                    'msg' => 'Demo update izvršen lokalno i privremeno.',
                                    'fake' => true
                                ]);
                                exit();
                            }


                            // VALIDACIJA I UČITAVANJE PODATAKA
                            
                            if (!isset($this->data->orders->update->id) || empty($this->data->orders->update->id)) {
                                http_response_code(422);
                                echo json_encode(['error' => 'ID rezervacije je obavezan'], JSON_UNESCAPED_UNICODE);
                                exit();
                            }

                            $this->order->id = $this->data->orders->update->id;
                            $this->order->getFromDB($this->order->id);

                            // Proveri da li order postoji
                            if (!$this->order->order_id) {
                                http_response_code(404);
                                echo json_encode(['error' => 'Rezervacija nije pronađena'], JSON_UNESCAPED_UNICODE);
                                exit();
                            }

                            // 2. AUTORIZACIJA
                            
                            if (!($this->order->findUserId() || Validator::isAdmin() || Validator::isSuper())) {
                                http_response_code(403);
                                echo json_encode(['error' => 'Niste autorizovani da izmenite ovu rezervaciju!'], JSON_UNESCAPED_UNICODE);
                                exit();
                            }

                            // 3. DEADLINE CHECK
                            
                            if (!$this->order->checkDeadline()) {
                                http_response_code(422);
                                echo json_encode(['error' => 'Nije moguće izmeniti rezervaciju, jer je do polaska ostalo manje od 48 sati.'], JSON_UNESCAPED_UNICODE);
                                exit();
                            }

                            // PRIPREMA PODATAKA
                            
                            $hasAddressUpdate = isset($this->data->orders->address) && 
                                            (!empty($this->data->orders->address->add_from) || 
                                                !empty($this->data->orders->address->add_to));
                            
                            $hasPlacesUpdate = isset($this->data->orders->new_places) && 
                                            !empty($this->data->orders->new_places);
                            
                            $hasReschedule = isset($this->data->orders->reschedule) && 
                                            (!empty($this->data->orders->reschedule->outDate) || 
                                            !empty($this->data->orders->reschedule->inDate));

                            // Ako nema nijednog update-a
                            if (!$hasAddressUpdate && !$hasPlacesUpdate && !$hasReschedule) {
                                http_response_code(422);
                                echo json_encode(['error' => 'Nema podataka za ažuriranje'], JSON_UNESCAPED_UNICODE);
                                exit();
                            }

                            // Dodeli podatke
                            if ($hasAddressUpdate) {
                                $this->order->new_add_from = $this->data->orders->address->add_from ?? null;
                                $this->order->new_add_to = $this->data->orders->address->add_to ?? null;
                            }

                            if ($hasPlacesUpdate) {
                                $this->order->newPlaces = $this->data->orders->new_places;
                            }

                            if ($hasReschedule) {
                                $this->order->newDate = $this->data->orders->reschedule->outDate ?? null;
                                $this->order->newDateIn = $this->data->orders->reschedule->inDate ?? null;
                            }

                            // IZVRŠAVANJE UPDATE-A (SA TRANSAKCIJOM)
                            
                            $this->db->beginTransaction();
                            
                            try {
                                $results = [];
                                $errors = [];

                                // UPDATE ADDRESS
                                if ($hasAddressUpdate) {
                                    $addressResult = $this->order->updateAddress();
                                    
                                    if (isset($addressResult['error'])) {
                                        $errors[] = $addressResult['error'];
                                    } else if (isset($addressResult['success'])) {
                                        $results[] = $addressResult['msg'];
                                    }
                                }

                                // UPDATE PLACES
                                if ($hasPlacesUpdate) {
                                    // Proveri da li je isti broj mesta
                                    if ($this->order->newPlaces == $this->order->places) {
                                        $errors[] = "Naveli ste broj mesta koji već imate u rezervaciji: {$this->order->places}";
                                    } else {
                                        $placesResult = $this->order->updatePlaces();
                                        
                                        if (isset($placesResult['error'])) {
                                            $errors[] = $placesResult['error'];
                                        } else if (isset($placesResult['success'])) {
                                            $results[] = $placesResult['msg'];
                                        }
                                    }
                                }

                                // RESCHEDULE
                                if ($hasReschedule) {
                                    // Proveri da li je isti datum
                                    $sameOutDate = ($this->order->newDate && $this->order->newDate == $this->order->date);
                                    
                                    $sameInDate = false;
                                    if (isset($this->order->items->items[1])) {
                                        $sameInDate = ($this->order->newDateIn && 
                                                    $this->order->newDateIn == $this->order->items->items[1]->date);
                                    }

                                    if ($sameOutDate && $sameInDate) {
                                        $errors[] = "Naveli ste datume koje već imate u rezervaciji";
                                    } else {
                                        $rescheduleResult = $this->order->reschedule();
                                        
                                        if (isset($rescheduleResult['error'])) {
                                            $errors[] = $rescheduleResult['error'];
                                        } else if (isset($rescheduleResult['success'])) {
                                            $results[] = $rescheduleResult['msg'];
                                        }
                                    }
                                }

                                // PROVERA REZULTATA
                                
                                if (!empty($errors)) {
                                    // Ima grešaka - rollback
                                    $this->db->rollBack();
                                    
                                    http_response_code(422);
                                    echo json_encode([
                                        'error' => count($errors) === 1 ? $errors[0] : $errors
                                    ], JSON_UNESCAPED_UNICODE);
                                    
                                } else {
                                    // Sve je OK - commit
                                    $this->db->commit();
                                    
                                    http_response_code(200);
                                    echo json_encode([
                                        'success' => true,
                                        'msg' => !empty($results) 
                                            ? implode(' ', array_filter($results)) 
                                            : 'Rezervacija je uspešno ažurirana'
                                    ], JSON_UNESCAPED_UNICODE);
                                }

                            } catch (PDOException $e) {
                                // Rollback pri bilo kojoj grešci
                                $this->db->rollBack();
                                
                                Logger::error("Order update failed", [
                                    'order_id' => $this->order->id,
                                    'user_id' => $_SESSION['user']['id'],
                                    'error' => $e->getMessage(),
                                    'file' => __FILE__,
                                    'line' => __LINE__
                                ]);
                                
                                http_response_code(500);
                                echo json_encode(['error' => 'Došlo je do greške pri ažuriranju rezervacije'], JSON_UNESCAPED_UNICODE);
                            }
                        }

                    if(isset($this->data->orders->selected) && !empty($this->data->orders->selected)
                        && isset($this->data->orders->driver) && !empty($this->data->orders->driver)) {
                            DemoGuard::denyIfDemo();
                            $this->order->selected = $this->data->orders->selected;
                            $this->order->driver = $this->data->orders->driver;
                            $this->order->tour_id = $this->data->orders->tour_id;
                        ;
                            $this->order->assignDriverTo();
                    }
                    if(isset($this->data->orders->voucher) && !empty($this->data->orders->voucher)) {
                        DemoGuard::denyIfDemo();
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
                        if($isDemo) {
                            echo json_encode([
                                'success' => true,
                                'msg' => 'Demo update izvršen lokalno i privremeno.',
                                'fake' => true
                            ]);
                            exit();
                        }
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
*/

declare(strict_types=1);

namespace Controllers;

use Guards\DemoGuard;
use Helpers\Logger;
use Middleware\DemoMiddleware;
use Models\Order;
use PDO;
use PDOException;
use Rules\Validator;

if (!defined('APP_ACCESS')) {
    http_response_code(403);
    die('Direct access forbidden');
}

class OrderController {
    private PDO $db;
    private object $data;
    private Order $order;
    private string $sid;
    private Logger $logger;

    public function __construct(PDO $db, object $data, string $sid)
    {
        $this->db = $db;
        $this->data = $data;
        $this->order = new Order($this->db);
        $this->sid = $sid;
        $this->logger = new Logger($this->db);
    }

    // Validate if User can access
    private function canAccessOrder(int $userId): bool
    {
        // Admin i Superadmin mogu pristupiti svim order-ima
        if (Validator::isAdmin() || Validator::isSuper()) {
            return true;
        }

        // User može pristupiti samo svojim order-ima
        if (isset($_SESSION['user']['id'])) {
            return $_SESSION['user']['id'] === $userId;
        }

        return false;
    }

    // IS Auth USER?
    private function requireAuth(): void
    {
        if (!isset($_SESSION['user'])) {
            http_response_code(401);
            echo json_encode([
                'error' => 'Morate biti ulogovani za ovu akciju'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    // Allowed ROLE
    private function requireAdmin(): void
    {
        if (!Validator::isAdmin() && !Validator::isSuper() && !Validator::isDriver()) {
            http_response_code(403);
            echo json_encode([
                'error' => 'Nemate dozvolu za ovu akciju'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    // Search and DISPLAY Orders
    private function get(): void
    {
        $this->requireAuth();

        if (!isset($this->data->orders) || empty($this->data->orders)) {
            http_response_code(400);
            echo json_encode([
                'error' => 'Nedostaju parametri za pretragu'
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        // GET by user_id (pojedinačni user)
        if (isset($this->data->orders->user_id) 
            && !empty($this->data->orders->user_id)
            && !isset($this->data->orders->adminOrders) 
            && !isset($this->data->orders->filters)) {
            
            $userId = filter_var($this->data->orders->user_id, FILTER_VALIDATE_INT);
            
            if ($userId === false) {
                http_response_code(400);
                echo json_encode(['error' => 'Neispravan ID korisnika'], JSON_UNESCAPED_UNICODE);
                return;
            }

            // Provera autorizacije
            if (!$this->canAccessOrder($userId)) {
                http_response_code(403);
                echo json_encode([
                    'error' => 'Nemate dozvolu da vidite ove rezervacije'
                ], JSON_UNESCAPED_UNICODE);
                return;
            }

            $this->order->user_id = $userId;
            $this->order->getByUser();
            return;
        }

        // GET all orders (24h ili 48h) - samo admin/driver
        if (isset($this->data->orders->adminOrders->all) 
            && !empty($this->data->orders->adminOrders->all)) {
            
            $this->requireAdmin();

            $in24 = $this->data->orders->adminOrders->in24 ?? false;
            $in48 = $this->data->orders->adminOrders->in48 ?? false;

            $this->order->getAll($in24, $in48);
            return;
        }

        // GET by filters - samo admin
        if (isset($this->data->orders->filters) && !empty($this->data->orders->filters)) {
            
            if (!Validator::isAdmin() && !Validator::isSuper()) {
                http_response_code(403);
                echo json_encode([
                    'error' => 'Niste autorizovani da koristite filtere'
                ], JSON_UNESCAPED_UNICODE);
                return;
            }

            $filters = $this->data->orders->filters;

            $this->order->date = $filters->departure ?? null;
            $this->order->code = $filters->code ?? null;
            $this->order->from_city = $filters->from_city ?? null;
            $this->order->to_city = $filters->to_city ?? null;
            $this->order->tour_id = isset($filters->tour_id) 
                ? filter_var($filters->tour_id, FILTER_VALIDATE_INT) 
                : null;
            
            $email = $filters->user_email ?? null;

            $this->order->getAllByFilter($email);
            return;
        }

        // GET by date range
        if (isset($this->data->orders->from_date) || isset($this->data->orders->to_date)) {
            
            $this->requireAdmin();

            $fromDate = $this->data->orders->from_date ?? null;
            $toDate = $this->data->orders->to_date ?? null;

            // Validacija datuma
            if ($fromDate && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fromDate)) {
                http_response_code(400);
                echo json_encode(['error' => 'Neispravan format datuma (from_date)'], JSON_UNESCAPED_UNICODE);
                return;
            }

            if ($toDate && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $toDate)) {
                http_response_code(400);
                echo json_encode(['error' => 'Neispravan format datuma (to_date)'], JSON_UNESCAPED_UNICODE);
                return;
            }

            $this->order->getAllByDateRange($fromDate, $toDate);
            return;
        }

        // GET by tour_id and date
        if (isset($this->data->orders->tour_id) 
            && !empty($this->data->orders->tour_id)
            && isset($this->data->orders->date) 
            && !empty($this->data->orders->date)) {
            
            $this->requireAdmin();

            $tourId = filter_var($this->data->orders->tour_id, FILTER_VALIDATE_INT);
            
            if ($tourId === false) {
                http_response_code(400);
                echo json_encode(['error' => 'Neispravan ID ture'], JSON_UNESCAPED_UNICODE);
                return;
            }

            $this->order->tour_id = $tourId;
            $this->order->date = $this->data->orders->date;
            $this->order->getByTourAndDate();
            return;
        }

        // GET by tour_id only
        if (isset($this->data->orders->tour_id) && !isset($this->data->orders->date)) {
            
            $this->requireAdmin();

            $tourId = filter_var($this->data->orders->tour_id, FILTER_VALIDATE_INT);
            
            if ($tourId === false) {
                http_response_code(400);
                echo json_encode(['error' => 'Neispravan ID ture'], JSON_UNESCAPED_UNICODE);
                return;
            }

            $this->order->tour_id = $tourId;
            $this->order->getByTour();
            return;
        }

        // Ako ništa nije matchovano
        http_response_code(400);
        echo json_encode([
            'error' => 'Nevalidni parametri za pretragu'
        ], JSON_UNESCAPED_UNICODE);
    }

    //-------------------------------------------- POST METHODS ----------------------------------------//
    private function post(): void
    {
        $this->requireAuth();

        if (!isset($this->data->orders->create) || empty($this->data->orders->create)) {
            http_response_code(400);
            echo json_encode([
                'error' => 'Nedostaju podaci za kreiranje rezervacije'
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        // Demo guard - fake create
        if (Validator::isDemo()) {
            echo json_encode([
                'success' => true,
                'msg' => 'Demo rezervacija je kreirana lokalno i privremeno.',
                'fake' => true
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        // Validation
        if (!is_array($this->data->orders->create) || count($this->data->orders->create) === 0) {
            http_response_code(400);
            echo json_encode([
                'error' => 'Morate uneti bar jednu stavku rezervacije'
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        // Check if the User try to create his own ORDER
        $firstItem = $this->data->orders->create[0];
        
        if (!isset($firstItem->user_id)) {
            http_response_code(400);
            echo json_encode([
                'error' => 'Nedostaje ID korisnika'
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        $userId = filter_var($firstItem->user_id, FILTER_VALIDATE_INT);
        
        if ($userId === false) {
            http_response_code(400);
            echo json_encode(['error' => 'Neispravan ID korisnika'], JSON_UNESCAPED_UNICODE);
            return;
        }

        // Admin can create for all -> USER only for him/her
        if (!$this->canAccessOrder($userId)) {
            http_response_code(403);
            echo json_encode([
                'error' => 'Ne možete kreirati rezervaciju za drugog korisnika'
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        foreach ($this->data->orders->create as $index => $item) {
            if (empty($item->tour_id) || empty($item->places) 
                || empty($item->add_from) || empty($item->add_to) 
                || empty($item->date)) {
                
                http_response_code(400);
                echo json_encode([
                    'error' => "Nepotpuni podaci u stavci #" . ($index + 1)
                ], JSON_UNESCAPED_UNICODE);
                return;
            }

            // Validate TOUR_ID
            if (filter_var($item->tour_id, FILTER_VALIDATE_INT) === false) {
                http_response_code(400);
                echo json_encode([
                    'error' => "Neispravan ID ture u stavci #" . ($index + 1)
                ], JSON_UNESCAPED_UNICODE);
                return;
            }

            // Validate PLACES
            if (filter_var($item->places, FILTER_VALIDATE_INT) === false || $item->places < 1) {
                http_response_code(400);
                echo json_encode([
                    'error' => "Neispravan broj mesta u stavci #" . ($index + 1)
                ], JSON_UNESCAPED_UNICODE);
                return;
            }

            // Validate DATE
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $item->date)) {
                http_response_code(400);
                echo json_encode([
                    'error' => "Neispravan format datuma u stavci #" . ($index + 1)
                ], JSON_UNESCAPED_UNICODE);
                return;
            }
        }

        // ALL OK -> CREATE
        $this->order->items = $this->data->orders;
        $this->order->create();
    }

    //------------------------------------------------ PUT Methods ---------------------------------//
    private function put(): void
    {
        $this->requireAuth();

        // UPDATE order (places, address, reschedule)
        if (isset($this->data->orders->update)) {
            
            if (Validator::isDemo()) {
                echo json_encode([
                    'success' => true,
                    'msg' => 'Demo update izvršen lokalno.',
                    'fake' => true
                ], JSON_UNESCAPED_UNICODE);
                return;
            }

            // Validacija ID-a
            if (!isset($this->data->orders->update->id) 
                || empty($this->data->orders->update->id)) {
                http_response_code(400);
                echo json_encode([
                    'error' => 'ID rezervacije je obavezan'
                ], JSON_UNESCAPED_UNICODE);
                return;
            }

            $orderId = filter_var($this->data->orders->update->id, FILTER_VALIDATE_INT);
            
            if ($orderId === false) {
                http_response_code(400);
                echo json_encode(['error' => 'Neispravan ID rezervacije'], JSON_UNESCAPED_UNICODE);
                return;
            }

            $this->order->id = $orderId;
            $this->order->getFromDB($this->order->id);

            // Order EXISTS ?
            if (!$this->order->order_id) {
                http_response_code(404);
                echo json_encode([
                    'error' => 'Rezervacija nije pronađena'
                ], JSON_UNESCAPED_UNICODE);
                return;
            }

            // Can User Update this ORDER
            if (!($this->order->findUserId() || Validator::isAdmin() || Validator::isSuper())) {
                http_response_code(403);
                echo json_encode([
                    'error' => 'Niste autorizovani da izmenite ovu rezervaciju'
                ], JSON_UNESCAPED_UNICODE);
                return;
            }

            // Deadline check
            if (!$this->order->checkDeadline()) {
                http_response_code(422);
                echo json_encode([
                    'error' => 'Nije moguće izmeniti rezervaciju jer je do polaska ostalo manje od 48 sati'
                ], JSON_UNESCAPED_UNICODE);
                return;
            }

            $hasAddressUpdate = isset($this->data->orders->address) 
                && (!empty($this->data->orders->address->add_from) 
                    || !empty($this->data->orders->address->add_to));
            
            $hasPlacesUpdate = isset($this->data->orders->new_places) 
                && !empty($this->data->orders->new_places);
            
            $hasReschedule = isset($this->data->orders->reschedule) 
                && (!empty($this->data->orders->reschedule->outDate) 
                    || !empty($this->data->orders->reschedule->inDate));

            if (!$hasAddressUpdate && !$hasPlacesUpdate && !$hasReschedule) {
                http_response_code(400);
                echo json_encode([
                    'error' => 'Nema podataka za ažuriranje'
                ], JSON_UNESCAPED_UNICODE);
                return;
            }

            // Assign DATA
            if ($hasAddressUpdate) {
                $this->order->new_add_from = $this->data->orders->address->add_from ?? null;
                $this->order->new_add_to = $this->data->orders->address->add_to ?? null;
            }

            if ($hasPlacesUpdate) {
                $newPlaces = filter_var($this->data->orders->new_places, FILTER_VALIDATE_INT);
                
                if ($newPlaces === false || $newPlaces < 1) {
                    http_response_code(400);
                    echo json_encode([
                        'error' => 'Neispravan broj mesta'
                    ], JSON_UNESCAPED_UNICODE);
                    return;
                }
                
                $this->order->newPlaces = $newPlaces;
            }

            if ($hasReschedule) {
                $outDate = $this->data->orders->reschedule->outDate ?? null;
                $inDate = $this->data->orders->reschedule->inDate ?? null;

                // Check DATE
                if ($outDate && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $outDate)) {
                    http_response_code(400);
                    echo json_encode([
                        'error' => 'Neispravan format datuma polaska'
                    ], JSON_UNESCAPED_UNICODE);
                    return;
                }

                if ($inDate && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $inDate)) {
                    http_response_code(400);
                    echo json_encode([
                        'error' => 'Neispravan format datuma povratka'
                    ], JSON_UNESCAPED_UNICODE);
                    return;
                }

                $this->order->newDate = $outDate;
                $this->order->newDateIn = $inDate;
            }

            // Transakcija
            $this->db->beginTransaction();
            
            try {
                $results = [];
                $errors = [];

                // UPDATE ADDRESS
                if ($hasAddressUpdate) {
                    $addressResult = $this->order->updateAddress();
                    
                    if (isset($addressResult['error'])) {
                        $errors[] = $addressResult['error'];
                    } else if (isset($addressResult['success'])) {
                        $results[] = $addressResult['msg'];
                    }
                }

                // UPDATE PLACES
                if ($hasPlacesUpdate) {
                    if ($this->order->newPlaces == $this->order->places) {
                        $errors[] = "Naveli ste broj mesta koji već imate: {$this->order->places}";
                    } else {
                        $placesResult = $this->order->updatePlaces();
                        
                        if (isset($placesResult['error'])) {
                            $errors[] = $placesResult['error'];
                        } else if (isset($placesResult['success'])) {
                            $results[] = $placesResult['msg'];
                        }
                    }
                }

                // RESCHEDULE
                if ($hasReschedule) {
                    $rescheduleResult = $this->order->reschedule();
                    
                    if (isset($rescheduleResult['error'])) {
                        $errors[] = $rescheduleResult['error'];
                    } else if (isset($rescheduleResult['success'])) {
                        $results[] = $rescheduleResult['msg'];
                    }
                }

                // Check RESULTS
                if (!empty($errors)) {
                    $this->db->rollBack();
                    
                    http_response_code(422);
                    echo json_encode([
                        'error' => count($errors) === 1 ? $errors[0] : $errors
                    ], JSON_UNESCAPED_UNICODE);
                    
                } else {
                    $this->db->commit();
                    
                    http_response_code(200);
                    echo json_encode([
                        'success' => true,
                        'msg' => !empty($results) 
                            ? implode(' ', array_filter($results)) 
                            : 'Rezervacija je uspešno ažurirana'
                    ], JSON_UNESCAPED_UNICODE);
                }

            } catch (PDOException $e) {
                $this->db->rollBack();
                
                $this->logger->error("Order update failed", [
                    'order_id' => $this->order->id,
                    'user_id' => $_SESSION['user']['id'],
                    'error' => $e->getMessage(),
                    'file' => __FILE__,
                    'line' => __LINE__
                ]);
                
                http_response_code(500);
                echo json_encode([
                    'error' => 'Došlo je do greške pri ažuriranju rezervacije'
                ], JSON_UNESCAPED_UNICODE);
            }

            return;
        }

        // ASSIGN DRIVER
        if (isset($this->data->orders->selected) 
            && !empty($this->data->orders->selected)
            && isset($this->data->orders->driver) 
            && !empty($this->data->orders->driver)) {
            
            DemoGuard::denyIfDemo('Demo admin ne može dodeljivati vozače');
            
            if (!Validator::isAdmin() && !Validator::isSuper()) {
                http_response_code(403);
                echo json_encode([
                    'error' => 'Samo admin može dodeljivati vozače'
                ], JSON_UNESCAPED_UNICODE);
                return;
            }

            // Validacija driver ID-a
            if (!isset($this->data->orders->driver->id) 
                || filter_var($this->data->orders->driver->id, FILTER_VALIDATE_INT) === false) {
                http_response_code(400);
                echo json_encode([
                    'error' => 'Neispravan ID vozača'
                ], JSON_UNESCAPED_UNICODE);
                return;
            }

            $this->order->selected = $this->data->orders->selected;
            $this->order->driver = $this->data->orders->driver;
            $this->order->tour_id = $this->data->orders->tour_id ?? null;
            
            $this->order->assignDriverTo();
            return;
        }

        // REGENERATE VOUCHER
        if (isset($this->data->orders->voucher) && !empty($this->data->orders->voucher)) {
            
            DemoGuard::denyIfDemo('Demo admin ne može regenerisati vaučere');
            
            if (!Validator::isAdmin() && !Validator::isSuper()) {
                http_response_code(403);
                echo json_encode([
                    'error' => 'Samo admin može regenerisati vaučere'
                ], JSON_UNESCAPED_UNICODE);
                return;
            }

            $itemId = filter_var($this->data->orders->voucher->item_id, FILTER_VALIDATE_INT);
            
            if ($itemId === false) {
                http_response_code(400);
                echo json_encode([
                    'error' => 'Neispravan ID stavke'
                ], JSON_UNESCAPED_UNICODE);
                return;
            }

            $this->order->id = $itemId;
            $this->order->getFromDB($this->order->id);
            
            if (!$this->order->order_id) {
                http_response_code(404);
                echo json_encode([
                    'error' => 'Rezervacija nije pronađena'
                ], JSON_UNESCAPED_UNICODE);
                return;
            }

            try {
                $mydata = $this->order->reGenerateVoucher();
                $this->order->sendVoucher(
                    $mydata['email'], 
                    $mydata['name'], 
                    $mydata['path'], 
                    $this->order->code, 
                    ''
                );
                
                http_response_code(200);
                echo json_encode([
                    'success' => true,
                    'msg' => 'Vaučer je uspešno poslat na email adresu korisnika'
                ], JSON_UNESCAPED_UNICODE);
                
            } catch (PDOException $e) {
                $this->logger->error('Failed to regenerate voucher', [
                    'order_item_id' => $this->order->id,
                    'error' => $e->getMessage(),
                    'file' => __FILE__,
                    'line' => __LINE__
                ]);
                
                http_response_code(500);
                echo json_encode([
                    'error' => 'Došlo je do greške pri slanju vaučera'
                ], JSON_UNESCAPED_UNICODE);
            }
            return;
        }

        // Ako ništa nije matchovano
        http_response_code(400);
        echo json_encode([
            'error' => 'Nevalidni parametri za ažuriranje'
        ], JSON_UNESCAPED_UNICODE);
    }

    //-------------------------------------------------- DELETE Methods ------------------------------------//
    private function delete(): void
    {
        $this->requireAuth();

        // DELETE order
        if (isset($this->data->orders->delete) && !empty($this->data->orders->delete)) {
            
            if (Validator::isDemo()) {
                echo json_encode([
                    'success' => true,
                    'msg' => 'Demo brisanje izvršeno lokalno.',
                    'fake' => true
                ], JSON_UNESCAPED_UNICODE);
                return;
            }

            if (!isset($this->data->orders->delete->item_id)) {
                http_response_code(400);
                echo json_encode([
                    'error' => 'ID stavke je obavezan'
                ], JSON_UNESCAPED_UNICODE);
                return;
            }

            $itemId = filter_var($this->data->orders->delete->item_id, FILTER_VALIDATE_INT);
            
            if ($itemId === false) {
                http_response_code(400);
                echo json_encode([
                    'error' => 'Neispravan ID stavke'
                ], JSON_UNESCAPED_UNICODE);
                return;
            }

            $this->order->id = $itemId;

            // Auth
            if (!($this->order->findUserId() || Validator::isAdmin() || Validator::isSuper())) {
                http_response_code(403);
                echo json_encode([
                    'error' => 'Niste autorizovani da obrišete ovu rezervaciju'
                ], JSON_UNESCAPED_UNICODE);
                return;
            }

            // Deadline check
            if (!($this->order->checkDeadline() || Validator::isAdmin() || Validator::isSuper())) {
                http_response_code(422);
                echo json_encode([
                    'error' => 'Nije moguće obrisati rezervaciju jer je do polaska ostalo manje od 48 sati'
                ], JSON_UNESCAPED_UNICODE);
                return;
            }

            $this->order->delete();
            return;
        }

        // RESTORE order - samo admin
        if (isset($this->data->orders->restore) && !empty($this->data->orders->restore)) {
            
            if (!Validator::isAdmin() && !Validator::isSuper()) {
                http_response_code(403);
                echo json_encode([
                    'error' => 'Samo admin može aktivirati rezervacije'
                ], JSON_UNESCAPED_UNICODE);
                return;
            }

            if (!isset($this->data->orders->restore->item_id)) {
                http_response_code(400);
                echo json_encode([
                    'error' => 'ID stavke je obavezan'
                ], JSON_UNESCAPED_UNICODE);
                return;
            }

            $itemId = filter_var($this->data->orders->restore->item_id, FILTER_VALIDATE_INT);
            
            if ($itemId === false) {
                http_response_code(400);
                echo json_encode([
                    'error' => 'Neispravan ID stavke'
                ], JSON_UNESCAPED_UNICODE);
                return;
            }

            $this->order->id = $itemId;
            $this->order->restore();
            return;
        }

        http_response_code(400);
        echo json_encode([
            'error' => 'Nevalidni parametri za brisanje'
        ], JSON_UNESCAPED_UNICODE);
    }

    //----------------------------- HEAD FINAL Function -----------------------------//
    public function handleRequest(): void
    {
        $this->requireAuth();

        $request = $_SERVER['REQUEST_METHOD'];

        if (in_array($request, ['POST', 'PUT', 'DELETE'])) {
            DemoMiddleware::handle();
        }

        try {
            switch ($request) {
                case 'GET':
                    $this->get();
                    break;
                
                case 'POST':
                    $this->post();
                    break;
                
                case 'PUT':
                    $this->put();
                    break;
                
                case 'DELETE':
                    $this->delete();
                    break;
                
                default:
                    http_response_code(405);
                    echo json_encode([
                        'error' => 'Metoda nije dozvoljena'
                    ], JSON_UNESCAPED_UNICODE);
            }
        } catch (PDOException $e) {
            $this->logger->error('OrderController exception', [
                'method' => $request,
                'user_id' => $_SESSION['user']['id'] ?? 'unknown',
                'error' => $e->getMessage(),
                'file' => __FILE__,
                'line' => __LINE__
            ]);

            http_response_code(500);
            echo json_encode([
                'error' => 'Došlo je do neočekivane greške'
            ], JSON_UNESCAPED_UNICODE);
        }
    }
}
