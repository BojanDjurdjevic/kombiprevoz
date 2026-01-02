<?php
declare(strict_types=1);

namespace Models;

use Helpers\Logger;
use PDO;
use PDOException;
use Rules\Validator;
use Dompdf\Dompdf;
use Dompdf\Options;
use Error;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;
use stdClass;
use tidy;

if (!defined('APP_ACCESS')) {
    http_response_code(403);
    die('Direct access forbidden');
}

class Order {
    public ?int $id = null;
    public ?int $order_id = null;
    public ?int $tour_id = null;
    public ?int $user_id = null;
    public ?int $driver_id = null;
    public ?int $places = null;
    public ?string $add_from = null;
    public ?string $add_to = null;
    public ?string $date = null;
    public ?int $price = null;
    public ?int $total = null;
    public ?string $code = null;
    public ?string $voucher = null;
    public ?int $deleted = null;
    public ?string $new_add_from = null;
    public ?string $new_add_to = null;
    public ?string $newDate = null;
    public ?string $newDateIn = null;
    public ?int $newPlaces = null;
    public ?object $driver = null;
    public ?int $dep_id = null;
    public ?array $selected = null;
    public ?object $items = null;
    public ?string $from_city = null;
    public ?string $to_city = null;

    private User $user;
    private Tour $tour;
    private PDO $db;
    private Logger $logger;

    public function __construct($db)
    {
        $this->db = $db;
        $this->user = new User($this->db);
        $this->tour = new Tour($this->db);
        $this->logger = new Logger($this->db);
    }

    //------------------------------- FUNCTIONS BEFORE THE ACTION ------------------------------//

    // Checking if the USER is OWNER of the order
    public function findUserId(): bool 
    {
        if (!isset($_SESSION['user']['id']) || empty($this->id)) {
            return false;
        }

        $sql = "SELECT user_id FROM orders 
                INNER JOIN order_items ON orders.id = order_items.order_id 
                WHERE order_items.id = :id AND orders.deleted = 0";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $this->id, PDO::PARAM_INT);

        try {
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_OBJ);

            return $row && (int)$row->user_id === (int)$_SESSION['user']['id'];
            
        } catch (PDOException $e) {
            $this->logger->error('Failed in findUserId()', [
                'order_item_id' => $this->id,
                'error' => $e->getMessage(),
                'file' => __FILE__,
                'line' => __LINE__
            ]);
            return false;
        }
    }

    // How many places we have available for the requested date:
    public function availability(string $date): int 
    {
        // Validate date
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $this->logger->error('Invalid date format in availability()', [
                'date' => $date,
                'tour_id' => $this->tour_id,
                'file' => __FILE__,
                'line' => __LINE__
            ]);
            return 0;
        }

        if (empty($this->tour_id)) {
            $this->logger->error('Tour ID missing in availability()', [
                'date' => $date,
                'file' => __FILE__,
                'line' => __LINE__
            ]);
            return 0;
        }

        // Query sa SUM za sve zauzete places
        $sql = "SELECT COALESCE(SUM(order_items.places), 0) as occupied, tours.seats 
                FROM order_items
                INNER JOIN tours ON tours.id = order_items.tour_id
                INNER JOIN orders ON order_items.order_id = orders.id
                WHERE order_items.date = :date
                AND order_items.tour_id = :tour_id 
                AND orders.deleted = 0
                AND order_items.deleted = 0
                GROUP BY tours.seats";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':date', $date, PDO::PARAM_STR);
        $stmt->bindParam(':tour_id', $this->tour_id, PDO::PARAM_INT);

        try {
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_OBJ);
            
            if ($row) {
                $available = (int)$row->seats - (int)$row->occupied;
                return max(0, $available);
            }
            
            // No reservation - all seats
            $tourSql = "SELECT seats FROM tours WHERE id = :id AND deleted = 0";
            $tourStmt = $this->db->prepare($tourSql);
            $tourStmt->bindParam(':id', $this->tour_id, PDO::PARAM_INT);
            $tourStmt->execute();
            
            $tour = $tourStmt->fetch(PDO::FETCH_OBJ);
            return $tour ? (int)$tour->seats : 0;
            
        } catch (PDOException $e) {
            $this->logger->error('Failed to check availability', [
                'date' => $date,
                'tour_id' => $this->tour_id,
                'error' => $e->getMessage(),
                'file' => __FILE__,
                'line' => __LINE__
            ]);
            return 0;
        }
    }

    // CHECK if the DEADLINE (48H) for changes is NOT passed:
    public function checkDeadline(): bool 
    {
        if(Validator::isAdmin() || Validator::isSuper()) return true;
        if (empty($this->id)) return false;

        $sql = "SELECT order_items.places, order_items.date, order_items.price, 
                       order_items.tour_id, tours.time 
                FROM order_items 
                INNER JOIN orders ON order_items.order_id = orders.id
                INNER JOIN tours ON order_items.tour_id = tours.id
                WHERE order_items.id = :id 
                AND orders.deleted = 0
                AND order_items.deleted = 0";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $this->id, PDO::PARAM_INT);

        try {
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_OBJ);

            if (!$row) {
                return false;
            }

            // Count deadline
            $departureTimestamp = strtotime($row->date . ' ' . $row->time);
            $deadlineTimestamp = $departureTimestamp - (48 * 3600); // 48h pre
            $currentTimestamp = time();

            // Set property
            $this->date = $row->date;
            $this->places = (int)$row->places;
            $this->price = (int)$row->price;
            $this->tour_id = (int)$row->tour_id;

            return $deadlineTimestamp > $currentTimestamp;
            
        } catch (PDOException $e) {
            $this->logger->error('Failed in checkDeadline()', [
                'order_item_id' => $this->id,
                'error' => $e->getMessage(),
                'file' => __FILE__,
                'line' => __LINE__
            ]);
            return false;
        }
    }

    // CHECK if the new date isn't within 24H

    public function isUnlocked(?string $d): bool 
    {
        if ($d === null) {
            return false;
        }

        // Validate format
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $d)) {
            return false;
        }

        // Date
        $parts = explode('-', $d);
        if (!checkdate((int)$parts[1], (int)$parts[2], (int)$parts[0])) {
            return false;
        }

        $requestedTimestamp = strtotime($d);
        $unlockTimestamp = time() + (25 * 3600); // 25h od sada

        return $requestedTimestamp > $unlockTimestamp;
    }

    // CHECK if the requested DATE is departure day:
    public function isDeparture(string $d): bool
    {
        // Validate date
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $d)) return false;

        // If no tour_id, take from order_items
        if (empty($this->tour_id) && !empty($this->id)) {
            $sqlID = "SELECT tour_id FROM order_items WHERE id = :id";
            $stmt = $this->db->prepare($sqlID);
            $stmt->bindParam(':id', $this->id, PDO::PARAM_INT);
            
            try {
                $stmt->execute();
                $row = $stmt->fetch(PDO::FETCH_OBJ);
                
                if ($row) {
                    $this->tour_id = (int)$row->tour_id;
                } else {
                    return false;
                }
            } catch (PDOException $e) {
                $this->logger->error('Failed to get tour_id in isDeparture()', [
                    'order_item_id' => $this->id,
                    'error' => $e->getMessage(),
                    'file' => __FILE__,
                    'line' => __LINE__
                ]);
                return false;
            }
        }

        if (empty($this->tour_id)) {
            return false;
        }

        // Check departure days
        $sql = "SELECT departures FROM tours WHERE id = :id AND deleted = 0";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $this->tour_id, PDO::PARAM_INT);

        try {
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_OBJ);
            
            if (!$row) {
                return false;
            }

            $days = explode(',', $row->departures);
            $depDays = array_map('intval', $days);
            
            // Dan u nedelji za odabrani datum (0=Nedelja, 6=Subota isto kaoo i u JS) 
            $orderDayOfWeek = (int)date('w', strtotime($d));

            return in_array($orderDayOfWeek, $depDays, true);
            
        } catch (PDOException $e) {
            $this->logger->error('Failed in isDeparture()', [
                'tour_id' => $this->tour_id,
                'date' => $d,
                'error' => $e->getMessage(),
                'file' => __FILE__,
                'line' => __LINE__
            ]);
            return false;
        }
    }

    // Check the real price of the order:
    public static function totalPrice(PDO $db, int $tourID, int $places): ?int
    {
        if ($tourID <= 0 || $places <= 0) {
            return null;
        }

        $sql = "SELECT price FROM tours WHERE id = :id AND deleted = 0";
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':id', $tourID, PDO::PARAM_INT);

        try {
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_OBJ);
            
            if ($row) {
                return (int)$row->price * $places;
            }
            
            return null;
            
        } catch (PDOException $e) {
            $logger = new Logger($db);
            $logger->error('Failed in totalPrice()', [
                'tour_id' => $tourID,
                'places' => $places,
                'error' => $e->getMessage(),
                'file' => __FILE__,
                'line' => __LINE__
            ]);
            return null;
        }
    }

    //------------------------------- FUNCTIONS AFTER THE ACTION ------------------------------//

    public function updateTotalPrice(): int 
    {
        if (empty($this->order_id)) {
            throw new Exception('Order ID nije setovan');
        }

        // Count total from items
        $findSql = "SELECT COALESCE(SUM(price), 0) as total 
                    FROM order_items 
                    WHERE order_id = :order_id 
                    AND deleted = 0";
        
        $stmtF = $this->db->prepare($findSql);
        $stmtF->bindParam(':order_id', $this->order_id, PDO::PARAM_INT);
        
        try {
            $stmtF->execute();
            $result = $stmtF->fetch(PDO::FETCH_OBJ);
            $total = $result ? (int)$result->total : 0;
            
        } catch (PDOException $e) {
            $this->logger->error('Failed to calculate total in updateTotalPrice', [
                'order_id' => $this->order_id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }

        // Update orders
        $updateSql = "UPDATE orders SET total = :total WHERE id = :id";
        $updateStmt = $this->db->prepare($updateSql);
        $updateStmt->bindParam(':total', $total, PDO::PARAM_INT);
        $updateStmt->bindParam(':id', $this->order_id, PDO::PARAM_INT);

        try {
            $updateStmt->execute();
            return $total;
            
        } catch (PDOException $e) {
            $this->logger->error('Failed to update total price', [
                'order_id' => $this->order_id,
                'total' => $total,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function generateVoucher( ? int $total): array 
    {
        $this->user->id = $this->user_id;
        $this->tour->id = $this->items->create[0]->tour_id;

        $owner = $this->user->getByID();
                    
        $tourObj = $this->tour->getByID();

        $sql = "SELECT orders.id, orders.user_id, orders.code, orders.file_path, orders.total, 
                order_items.* FROM orders
                INNER JOIN order_items on orders.id = order_items.order_id
                WHERE orders.id = :id     
        ";

        $drive = [];

        $stmt = $this->db->prepare($sql);
        
        $stmt->bindParam(':id', $this->id, PDO::PARAM_INT);

        try {
            if($stmt->execute()) {
                while($row = $stmt->fetch(PDO::FETCH_OBJ)) {
                    array_push($drive, $row);
                }
            }
        } catch (PDOException $e) {
            throw new Exception($e->getMessage());
        }
                    
        $options = new Options();
        $options->setChroot("src/assets/img");
        $pdf = new Dompdf($options);
        $pdf->setPaper("A4", "Portrait");

        $d = Validator::formatDateForFront($drive[0]->date);

        $html = file_get_contents("src/template.html");
        $html = str_replace("{{ order }}", $drive[0]->code, $html);

        $html = str_replace("{{ name }}", $owner[0]['name'], $html);

        $html = str_replace("{{ places }}", $drive[0]->places, $html);
        $html = str_replace("{{ address }}", $drive[0]->add_from, $html);
        $html = str_replace("{{ city }}", $tourObj[0]['from_city'], $html);
        $html = str_replace("{{ address_to }}", $drive[0]->add_to, $html);
        $html = str_replace("{{ city_to }}", $tourObj[0]['to_city'], $html);
        $html = str_replace("{{ date }}", $d, $html);
        $html = str_replace("{{ time }}", $tourObj[0]['time'], $html);
        $html = str_replace("{{ price }}", $drive[0]->price, $html);

        if(count($drive) > 1) {
            $dIn = Validator::formatDateForFront($drive[1]->date);

            $html = str_replace("{{ view }}", "visible", $html);
            $html = str_replace("{{ places2 }}", $drive[1]->places, $html);
            $html = str_replace("{{ address2 }}", $drive[1]->add_from, $html);
            $html = str_replace("{{ city2 }}", $tourObj[0]['to_city'], $html);
            $html = str_replace("{{ address_to2 }}", $drive[1]->add_to, $html);
            $html = str_replace("{{ city_to2 }}", $tourObj[0]['from_city'], $html);
            $html = str_replace("{{ date2 }}", $dIn, $html);
            $html = str_replace("{{ time2 }}", $tourObj[0]['time'], $html);
            $html = str_replace("{{ price2 }}", $drive[1]->price, $html);
            if($total) $html = str_replace("{{ total }}", (string) $total, $html);
            else $html = str_replace("{{ total }}", 'N/A', $html);
        } else {
            $html = str_replace("{{ view }}", "invisible", $html);
            $html = str_replace("{{ places2 }}", "N/A", $html);
            $html = str_replace("{{ address2 }}", "N/A", $html);
            $html = str_replace("{{ city2 }}", "N/A", $html);
            $html = str_replace("{{ address_to2 }}", "N/A", $html);
            $html = str_replace("{{ city_to2 }}", "N/A", $html);
            $html = str_replace("{{ date2 }}", "N/A", $html);
            $html = str_replace("{{ time2 }}", "N/A", $html);
            $html = str_replace("{{ price2 }}", "N/A", $html);
            $html = str_replace("{{ total }}", 'N/A', $html);
        }

        $html = str_replace("{{ year }}", date("Y"), $html);

        $pdf->loadHtml($html);

        $pdf->render(); // Obavezno!!!
        $pdf->addInfo("Title", "Kombitransfer - rezervacija: ". $drive[0]->code);
        //$pdf->stream("Rezervacija.pdf");
        $file_path = "src/assets/pdfs/". $drive[0]->code . ".pdf";
                    
        $output = $pdf->output();
        file_put_contents($file_path, $output);
        return [
            'email' => $owner[0]['email'],
            'name' => $owner[0]['name'],
            'path' => $file_path,
            'code' => $drive[0]->code
        ];
    }

    /*

    public function reGenerateVoucher() 
    {
        $this->user->id = $this->user_id;
        $this->tour->id = $this->tour_id;
        
        $owner = $this->user->getByID();          
        $tourObj = $this->tour->getByID();
        $myOrder = $this->getDriverOfTour();
                    
        $options = new Options();
        $options->setChroot("src/assets/img");
        $pdf = new Dompdf($options);
        $pdf->setPaper("A4", "Portrait");
        // Drivers for each item
        if($myOrder != NULL && isset($myOrder['items'][0]['driver'])) { //&& $myOrder['items'][0]['driver'] != NULL
            $arr = explode(" ", $myOrder['items'][0]['driver']['dr_name']);
            $myDriver = $arr[0];
        } else $myDriver = null;
        if($myOrder != NULL && isset($myOrder['items'][1]['driver'])) { // && $myOrder['items'][1]['driver'] != NULL
            $arr = explode(" ", $myOrder['items'][1]['driver']['dr_name']);
            $myDriver2 = $arr[0];
        } else $myDriver2 = null;
        // Dates for each item
        if($myOrder != NULL) {
           $formated = date_create($myOrder['items'][0]['order']['date']); 
        } else 
        $formated = date_create($this->date);  // BUG
        $d = date("d.m.Y", date_timestamp_get($formated));
        if($myOrder != NULL && isset($myOrder['items'][1]['order']['date'])) {
           $formated2 = date_create($myOrder['items'][1]['order']['date']); 
        } else 
        $formated2 = $formated; // SAME BUG - corrected
        $d2 = date("d.m.Y", date_timestamp_get($formated2));
        // In the title
        if($myOrder != NULL) {
            $html = file_get_contents("src/updated.html");
        } else $html = file_get_contents("src/template.html");
        if($myOrder != NULL) {
            $html = str_replace("{{ order }}", $myOrder['items'][0]['order']['code'], $html);
        } else
        $html = str_replace("{{ order }}", $this->code, $html);

        $html = str_replace("{{ name }}", $owner[0]['name'], $html);
        
        // First Item

        if($myOrder != NULL && isset($myOrder['items'][0])) {
        if($myOrder['items'][0]['deleted'] === 0) {
        if($myOrder != NULL) {
            $html = str_replace("{{ places }}", $myOrder['items'][0]['order']['places'], $html);
        } else
        $html = str_replace("{{ places }}", $this->places, $html);
        if($myOrder != NULL) {
            $html = str_replace("{{ address }}", $myOrder['items'][0]['order']['pickup'], $html);
        } else
        $html = str_replace("{{ address }}", $this->add_from, $html);
        $html = str_replace("{{ city }}", $tourObj[0]['from_city'], $html);
        if($myOrder != NULL) {
            $html = str_replace("{{ address_to }}", $myOrder['items'][0]['order']['dropoff'], $html);
        } else
        $html = str_replace("{{ address_to }}", $this->add_to, $html);
        $html = str_replace("{{ city_to }}", $tourObj[0]['to_city'], $html);
        $html = str_replace("{{ date }}", $d, $html);
        $html = str_replace("{{ time }}", $tourObj[0]['time'], $html);
        if($myOrder != NULL) {
            $html = str_replace("{{ price }}", $myOrder['items'][0]['order']['price'], $html);
        } else
        $html = str_replace("{{ price }}", $this->price, $html);

        if($myOrder != NULL && $myDriver) {
            $html = str_replace("{{ driver }}", $myDriver, $html);
            $html = str_replace("{{ drphone }}", $myOrder['items'][0]['driver']['dr_phone'], $html);
            $html = str_replace("{{ drmail }}", $myOrder['items'][0]['driver']['dr_email'], $html);
            $html = str_replace("{{ driver_view }}", "visible", $html);
        } else {
            $html = str_replace("{{ driver_view }}", "invisible", $html);
        }
        } else $html = str_replace("{{ view_first }}", "invisible", $html);
        } else $html = str_replace("{{ view_first }}", "invisible", $html);

        // 2nd Item - Inbound
        if($myOrder != NULL && isset($myOrder['items'][1])) {
            //if(count($myOrder['items']) > 1) {  
                //$items = $myOrder['items'];

                ////$deleted = !array_filter($items, fn($i) => $i['deleted'] == 0);

                //$hasDeleted = in_array(1, array_column($items, 'deleted'));

                if($myOrder['items'][1]['deleted'] === 0) {
                    $html = str_replace("{{ view }}", "visible", $html);
                    $html = str_replace("{{ places2 }}", $myOrder['items'][1]['order']['places'], $html);
                
                    $html = str_replace("{{ address2 }}", $myOrder['items'][1]['order']['pickup'], $html);

                    //$html = str_replace("{{ address2 }}", $this->add_from, $html);
                    $html = str_replace("{{ city2 }}", $tourObj[0]['to_city'], $html);

                    $html = str_replace("{{ address_to2 }}", $myOrder['items'][1]['order']['dropoff'], $html);

                    //$html = str_replace("{{ address_to2 }}", $this->add_to, $html);

                    $html = str_replace("{{ city_to2 }}", $tourObj[0]['from_city'], $html);
                    $html = str_replace("{{ date2 }}", $d2, $html);
                    $html = str_replace("{{ time2 }}", $tourObj[0]['time'], $html);
                    
                    $html = str_replace("{{ price2 }}", $myOrder['items'][1]['order']['price'], $html);
                    $html = str_replace("{{ price3 }}", $myOrder['items'][0]['order']['total'], $html);
                    
                    if($myOrder != NULL && $myDriver2) {
                        $html = str_replace("{{ driver2 }}", $myDriver2, $html);
                        $html = str_replace("{{ drphone2 }}", $myOrder['items'][1]['driver']['dr_phone'], $html);
                        $html = str_replace("{{ drmail2 }}", $myOrder['items'][1]['driver']['dr_email'], $html);
                        $html = str_replace("{{ driver_view2 }}", "visible", $html);
                    } else {
                        $html = str_replace("{{ driver_view2 }}", "invisible", $html);
                    }
                } else $html = str_replace("{{ view }}", "invisible", $html);
            //} else $html = str_replace("{{ view }}", "invisible", $html);
        } else $html = str_replace("{{ view }}", "invisible", $html);
        //In footer
        $html = str_replace("{{ year }}", date("Y"), $html);
        // Render voucher and return data
        $pdf->loadHtml($html);

        $pdf->render();
        if($myOrder != NULL) {
            $pdf->addInfo("Title", "Kombitransfer - rezervacija: ". $myOrder['items'][0]['order']['code']);
        } else
        $pdf->addInfo("Title", "Kombitransfer - rezervacija: ". $this->code);
        if($myOrder != NULL) {
            $file_path = $myOrder['items'][0]['order']['voucher'];
        } else
        $file_path = $this->voucher;
                    
        $output = $pdf->output();
        file_put_contents($file_path, $output);
        if($myOrder != NULL && $myDriver && $myDriver2) {
            return [
                'email' => $owner[0]['email'],
                'name' => $owner[0]['name'],
                'path' => $file_path,
                'code' => $myOrder['items'][0]['order']['code'],
                'driver' => $myDriver,
                'driver_phone' => $myOrder['items'][0]['driver']['dr_phone'],
                'driver_email' => $myOrder['items'][0]['driver']['dr_phone'],
                'driver2' => $myDriver2,
                'driver_phone2' => $myOrder['items'][1]['driver']['dr_phone'],
                'driver_email2' => $myOrder['items'][1]['driver']['dr_phone']
            ];
        } elseif($myOrder != NULL && $myDriver) {
            return [
                'email' => $owner[0]['email'],
                'name' => $owner[0]['name'],
                'path' => $file_path,
                'code' => $myOrder['items'][0]['order']['code'],
                'driver' => $myDriver,
                'driver_phone' => $myOrder['items'][0]['driver']['dr_phone'],
                'driver_email' => $myOrder['items'][0]['driver']['dr_phone'],
            ];
        } else
        return [
            'email' => $owner[0]['email'],
            'name' => $owner[0]['name'],
            'path' => $file_path,
            'code' => $this->code
        ];
    } */

    public function reGenerateVoucher() 
    {
        $this->user->id = $this->user_id;
        $this->tour->id = $this->tour_id;
        
        $owner = $this->user->getByID();          
        $tourObj = $this->tour->getByID();
        $myOrder = $this->getDriverOfTour();
                    
        $options = new Options();
        $options->setChroot("src/assets/img");
        $pdf = new Dompdf($options);
        $pdf->setPaper("A4", "Portrait");
        
        //======================================== PRIPREMA PODATAKA
        
        // Drivers
        $myDriver = null;
        $myDriver2 = null;
        
        if ($myOrder != NULL && isset($myOrder['items'][0]['driver']['dr_name'])) {
            $arr = explode(" ", $myOrder['items'][0]['driver']['dr_name']);
            $myDriver = $arr[0];
        }
        
        if ($myOrder != NULL && isset($myOrder['items'][1]['driver']['dr_name'])) {
            $arr = explode(" ", $myOrder['items'][1]['driver']['dr_name']);
            $myDriver2 = $arr[0];
        }
        
        // Datum polaska (prvi item)
        if ($myOrder != NULL && isset($myOrder['items'][0]['order']['date'])) {
            $d = date("d.m.Y", strtotime($myOrder['items'][0]['order']['date']));
        } else {
            $d = date("d.m.Y", strtotime($this->date));
        }
        
        // Datum povratka (drugi item)
        if ($myOrder != NULL && isset($myOrder['items'][1]['order']['date'])) {
            $d2 = date("d.m.Y", strtotime($myOrder['items'][1]['order']['date']));
        } else {
            $d2 = $d; // Ako nema povratak, koristi isti datum
        }
        
        // Order code
        $orderCode = ($myOrder != NULL) ? $myOrder['items'][0]['order']['code'] : $this->code;
        
        // File path
        $file_path = ($myOrder != NULL) ? $myOrder['items'][0]['order']['voucher'] : $this->voucher;
        
        // =====================  TEMPLATE  ===================  

        $html = ($myOrder != NULL) 
            ? file_get_contents("src/updated.html") 
            : file_get_contents("src/template.html");
        
        // =================== HEADER - Order code i putnik =====================

        $html = str_replace("{{ order }}", $orderCode, $html);
        $html = str_replace("{{ name }}", $owner[0]['name'], $html);
        
        // ==================  PRVI ITEM - POLAZAK  ======================
        
        $hasFirstItem = $myOrder != NULL && isset($myOrder['items'][0]) && $myOrder['items'][0]['order']['deleted'] == 0;
        
        if ($hasFirstItem) {
            $item1 = $myOrder['items'][0]['order'];
            
            $html = str_replace("{{ view_first }}", "visible", $html);
            $html = str_replace("{{ places }}", $item1['places'], $html);
            $html = str_replace("{{ address }}", $item1['pickup'], $html);
            $html = str_replace("{{ city }}", $tourObj[0]['from_city'], $html);
            $html = str_replace("{{ address_to }}", $item1['dropoff'], $html);
            $html = str_replace("{{ city_to }}", $tourObj[0]['to_city'], $html);
            $html = str_replace("{{ date }}", $d, $html);
            $html = str_replace("{{ time }}", $tourObj[0]['time'], $html);
            $html = str_replace("{{ price }}", $item1['price'], $html);
            
            // Driver za polazak
            if ($myDriver) {
                $html = str_replace("{{ driver }}", $myDriver, $html);
                $html = str_replace("{{ drphone }}", $myOrder['items'][0]['driver']['dr_phone'], $html);
                $html = str_replace("{{ drmail }}", $myOrder['items'][0]['driver']['dr_email'], $html);
                $html = str_replace("{{ driver_view }}", "visible", $html);
            } else {
                $html = str_replace("{{ driver_view }}", "invisible", $html);
            }
            
        } else {
            // Fallback ako nema myOrder
            $html = str_replace("{{ view_first }}", "visible", $html);
            $html = str_replace("{{ places }}", (string) $this->places, $html);
            $html = str_replace("{{ address }}", $this->add_from, $html);
            $html = str_replace("{{ city }}", $tourObj[0]['from_city'], $html);
            $html = str_replace("{{ address_to }}", $this->add_to, $html);
            $html = str_replace("{{ city_to }}", $tourObj[0]['to_city'], $html);
            $html = str_replace("{{ date }}", $d, $html);
            $html = str_replace("{{ time }}", $tourObj[0]['time'], $html);
            $html = str_replace("{{ price }}", (string) $this->price, $html);
            $html = str_replace("{{ driver_view }}", "invisible", $html);
        }
        
        // ====================  DRUGI ITEM - POVRATAK  ====================

        $hasSecondItem = $myOrder != NULL && isset($myOrder['items'][1]) && $myOrder['items'][1]['order']['deleted'] == 0;
        
        if ($hasSecondItem) {
            $item2 = $myOrder['items'][1]['order'];
            
            $html = str_replace("{{ view }}", "visible", $html);
            $html = str_replace("{{ places2 }}", $item2['places'], $html);
            $html = str_replace("{{ address2 }}", $item2['pickup'], $html);
            $html = str_replace("{{ city2 }}", $tourObj[0]['from_city'], $html); 
            $html = str_replace("{{ address_to2 }}", $item2['dropoff'], $html);
            $html = str_replace("{{ city_to2 }}", $tourObj[0]['to_city'], $html); 
            $html = str_replace("{{ date2 }}", $d2, $html);
            $html = str_replace("{{ time2 }}", $tourObj[0]['time'], $html);
            $html = str_replace("{{ price2 }}", $item2['price'], $html);
            
            // Total price (iz prvog item-a jer je to total za celu order)
            $html = str_replace("{{ price3 }}", $myOrder['items'][0]['order']['total'], $html);
            
            // Driver za povratak
            if ($myDriver2) {
                $html = str_replace("{{ driver2 }}", $myDriver2, $html);
                $html = str_replace("{{ drphone2 }}", $myOrder['items'][1]['driver']['dr_phone'], $html);
                $html = str_replace("{{ drmail2 }}", $myOrder['items'][1]['driver']['dr_email'], $html);
                $html = str_replace("{{ driver_view2 }}", "visible", $html);
            } else {
                $html = str_replace("{{ driver_view2 }}", "invisible", $html);
            }
            
        } else {
            // If no return
            $html = str_replace("{{ view }}", "invisible", $html);
        }
        
        // ==================  FOOTER  ======================
        
        $html = str_replace("{{ year }}", date("Y"), $html);
        
        // ====================  GENERATE PDF   ====================
        
        $pdf->loadHtml($html);
        $pdf->render();
        $pdf->addInfo("Title", "Kombitransfer - rezervacija: " . $orderCode);
        
        $output = $pdf->output();
        file_put_contents($file_path, $output);
        
        // ==================  RETURN DATA  ======================
        
        $result = [
            'email' => $owner[0]['email'],
            'name' => $owner[0]['name'],
            'path' => $file_path,
            'code' => $orderCode
        ];
        
        // Dodaj driver info ako postoji
        if ($myDriver) {
            $result['driver'] = $myDriver;
            $result['driver_phone'] = $myOrder['items'][0]['driver']['dr_phone'];
            $result['driver_email'] = $myOrder['items'][0]['driver']['dr_email'];
        }
        
        if ($myDriver2) {
            $result['driver2'] = $myDriver2;
            $result['driver_phone2'] = $myOrder['items'][1]['driver']['dr_phone'];
            $result['driver_email2'] = $myOrder['items'][1]['driver']['dr_email'];
        }
        
        return $result;
    }

    public function sendVoucher($email, $name, $path, $new_code, $goal)
    {
        $template = '';
        if($goal === 'create') {
            $template = "<p> Poštovani/a {$name}, </p>
            <br>
            <p> Uspešno ste rezervisali vašu vožnju! </p>
            <br>
            <p> Broj vaše rezervacije je: <b> $new_code </b> </p>
            <br>
            <p> U prilogu Vam šaljemo potvrdu rezervacije. </p>
            <br><br>
            <p> Srdačan pozdrav od Kombitransfer tima! </p>";
        } elseif($goal === 'update') {
            $template = "<p> Poštovani/a {$name}, </p>
            <br>
            <p> Uspešno ste izmenili vašu vožnju! </p>
            <br>
            <p> Broj vaše rezervacije je: <b> $new_code </b> </p>
            <br>
            <p> U prilogu Vam šaljemo ažuriranu potvrdu rezervacije. </p>
            <br><br>
            <p> Srdačan pozdrav od Kombitransfer tima! </p>";
        } else {
            $template = "<p> Poštovani/a {$name}, </p>
            <br>
            <p> Vaša rezervacija broj <b> $new_code </b> je ažurirana. </p>
            <br>
            <p> U prilogu Vam šaljemo ažuriranu potvrdu rezervacije. </p>
            <br><br>
            <p> Srdačan pozdrav od Kombitransfer tima! </p>";
        }

        $mail = new PHPMailer(true);
        //$mail->SMTPDebug = SMTP::DEBUG_SERVER;
        $mail->isSMTP();
        $mail->SMTPAuth = true;

        $mail->Host = "smtp.gmail.com";
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = 465;
        $mail->Username = $_ENV["SMTP_USER"];
        $mail->Password = $_ENV["SMTP_PASS"];

        $mail->setFrom("noreply-info@kombitransfer.com", "Bojan");
        $mail->addAddress($email, $name);

        $mail->CharSet = 'UTF-8';
        $mail->Encoding = 'base64';

        $mail->isHTML(true);
        $mail->addAttachment($path, "Kombitransfer - rezervacija: ". $new_code);
        $mail->Subject = "Potvrda Rezervacije";
        $mail->setLanguage('sr');
        $mail->Body = <<<END

            $template
                        
        END;

        try {
            $mail->send();
            //echo json_encode(['email' => 'Potvrda je upravo poslata na Vašu email adresu. Molimo proverite Vaš email!']);
        } catch (Exception $e) {
            echo json_encode([
                'email' => 'Došlo je do greške!',
                'msg' => $mail->ErrorInfo
            ]);
        }
    }

    // ------ PDF of all passangers and reservations to DRIVER
    public function generateDeparture($users, $new_code, $dateTime)
    {        
        $options = new Options();
        $options->setChroot("src/assets/img");
        $pdf = new Dompdf($options);
        $pdf->setPaper("A4", "Portrait");

        $formated = date_create($dateTime);
        $d = date_format($formated, "d.m.Y H:i"); 

        $template = '';
        foreach ($users as $pax) {
            $template .= Validator::mailerDriverTemplate($pax->code, $pax->user->name, $pax->places, $pax->pickup, $pax->from_city, $pax->dropoff, $pax->to_city, $pax->date, $pax->pickuptime, $pax->price, $pax->user->phone);
        }

        $html = file_get_contents("src/driver.html");
        $html = str_replace("{{ code }}", $new_code, $html);
        $html = str_replace("{{ dateTime }}", $d, $html);
        $html = str_replace("{{ main }}", $template, $html);
        $html = str_replace("{{ year }}", date("Y"), $html);

        $pdf->loadHtml($html);

        $pdf->render();
        $pdf->addInfo("Title", "Kombitransfer - vožnja: ". $new_code);
        $file_path = "src/assets/pdfs/". $new_code . ".pdf";
                    
        $output = $pdf->output();
        file_put_contents($file_path, $output);
        return [
            'path' => $file_path
        ];
    }

    // ------ Email about reservations to DRIVER
    public function sendOrdersToDriver($name, $new_code, $path, $email)
    {        
        $template = "<p> Poštovani/a {$name}, </p>
        <br>
        <p> Sistem Vam je dodelio vožnju broj: <b> $new_code </b> </p>
        <br>
        <p> U prilogu Vam šaljemo spisak svih porudžbina, sa imenima i podacima putnika. </p>
        <p> Savetujemo Vam da napravite svoju rutu i poredak kupljenja i ostavljanja putnika sa/na adrese. </p>
        <p> Takođe Vas molimo da dan pre polaska, a nakon pravljenja redosleda preuzimanja putnika, 
        svim putnicima blagovremeno javite okvirno vreme kada ćete po njih doći. </p>
        <p>Hvala!</p>
        <br><br>
        <p> Srdačan pozdrav od Kombitransfer tima! </p>";

        $mail = new PHPMailer(true);
        //$mail->SMTPDebug = SMTP::DEBUG_SERVER;
        $mail->isSMTP();
        $mail->SMTPAuth = true;

        $mail->Host = "smtp.gmail.com";
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = 465;
        $mail->Username = $_ENV["SMTP_USER"];
        $mail->Password = $_ENV["SMTP_PASS"];

        $mail->setFrom("noreply-info@kombitransfer.com", "Bojan");
        $mail->addAddress($email, $name);

        $mail->CharSet = 'UTF-8';
        $mail->Encoding = 'base64';

        $mail->isHTML(true);
        $mail->addAttachment($path, "Kombitransfer - rezervacija: ". $new_code);
        $mail->Subject = "Potvrda Rezervacije";
        $mail->setLanguage('sr');
        $mail->Body = <<<END

            $template
                        
        END;

        try {
            $mail->send();
        } catch (Exception $e) {
            echo json_encode([
                'email' => 'Došlo je do greške!',
                'msg' => $mail->ErrorInfo
            ]);
        }
    }

    //------------------------------- AFTER ACTION HELPERS RELATED WITH ANOTHER CLASSES --------------------------------//
    /*
    public function availableDrivers($date, $tour_id) {
        $sql = "SELECT users.id, users.name, users.status, users.email, users.phone, users.city FROM users
                WHERE users.status = 'driver' AND
                NOT EXISTS (SELECT 1 FROM departures WHERE driver_id = users.id AND departures.date = :date)
                AND users.city IN 
                (
                    (SELECT from_city FROM tours WHERE id = :tour_id), 
                    (SELECT to_city FROM tours WHERE id = :tour_id)
                ) 
                "
        ;
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':date', $date);
        $stmt->bindParam(':tour_id', $tour_id);
        
        try {
            if($stmt->execute()) {
                $drivers = $stmt->fetchAll(PDO::FETCH_OBJ);
                return $drivers;
            }
        } catch(PDOException $e) {
            return json_encode(['message' => $e->getMessage()]);
        }
    }
    */

    public function availableDrivers($date, $tours): array {
        if (empty($tours)) return [];

        // Svi gradovi za zadate ture
        $sqlCities = "
            SELECT id, from_city, to_city
            FROM tours
            WHERE id IN (" . implode(',', array_map('intval', $tours)) . ")
        ";
        $stmtCities = $this->db->prepare($sqlCities);
        $stmtCities->execute();
        $tourCities = $stmtCities->fetchAll(PDO::FETCH_OBJ);

        // Liista gradova
        $cities = [];
        foreach ($tourCities as $t) {
            $cities[] = $t->from_city;
            $cities[] = $t->to_city;
        }
        $cities = array_unique($cities);

        // Upitnici za IN klauzulu
        $placeholders = str_repeat('?,', count($cities) - 1) . '?';

        // Slobodni vozači u tim gradovima
        $sqlDrivers = "
            SELECT id, name, status, email, phone, city
            FROM users
            WHERE status = 'Driver'
            AND NOT EXISTS (
                SELECT 1 FROM departures 
                WHERE driver_id = users.id AND date = ?
            )
            AND city IN ($placeholders)
        ";
        $stmtDrivers = $this->db->prepare($sqlDrivers);
        $params = array_merge([$date], $cities);
        $stmtDrivers->execute($params);
        $drivers = $stmtDrivers->fetchAll(PDO::FETCH_OBJ);

        // Grupisanje vozača po gradu
        $grouped = [];
        foreach ($drivers as $d) {
            $grouped[$d->city][] = $d;
        }

        return [
            'drivers_by_city' => $grouped,
            'tour_cities' => $tourCities
        ];
    }


    //------------------------------- FUNCTIONS OF GET METHOD --------------------------------//
    /*
    public function getAll($in24, $in48) 
    {
        if($in24 && empty($in48)) {
            $tomorrow = date("Y-m-d", strtotime("+1 day"));
        } elseif($in48 && empty($in24)) {
            $tomorrow = date("Y-m-d", strtotime("+2 days"));
        } else {
            echo json_encode(['error' => 'Odaberite 24h ili 48h']);
            exit();
        } 
        $sql = "SELECT order_items.id as order_item_id, order_items.driver_id, orders.id as order_id, 
                tours.id as tour_id, orders.user_id as user_id, order_items.places,  
                order_items.add_from as pickup, tours.to_city, order_items.add_to as dropoff, tours.from_city,
                order_items.date, tours.time as pickuptime, tours.duration,
                orders.total as price, orders.code, orders.file_path as voucher, users.name as user_name, users.email as email, users.phone as phone
                from orders 
                INNER JOIN order_items on order_items.order_id = orders.id
                INNER JOIN tours on order_items.tour_id = tours.id
                INNER JOIN users on orders.user_id = users.id
                WHERE orders.deleted = 0 and order_items.deleted = 0
                and order_items.date = :tomorrow
                ORDER BY tours.id, order_items.date, pickuptime"
        ;
        $stmt = $this->db->prepare($sql);

        $stmt->bindParam(':tomorrow', $tomorrow);

        try {
            if($stmt->execute()) {
                $rows = $stmt->fetchAll(PDO::FETCH_OBJ);
                $orders = [];
                foreach($rows as $row) {
                    $tId = $row->tour_id;
                    if(!isset($orders[$tId])) {
                        $orders[$tId] = [
                            'tour_id' => $row->tour_id,
                            'from_city' => $row->from_city,
                            'to_city' => $row->to_city,
                            'pickuptime' => $row->pickuptime,
                            'duration' => $row->duration,
                            'date' => $tomorrow,
                            'rides' => [],
                            'unasssigned_rides' => 0
                        ];
                    }

                    $orders[$tId]['rides'][] = [
                        'order_item_id' => $row->order_item_id,
                        'order_id'      => $row->order_id,
                        'places'        => $row->places,
                        'pickup'        => $row->pickup,
                        'dropoff'       => $row->dropoff,
                        'date'          => $row->date,
                        'price'         => $row->price,
                        'voucher'       => $row->voucher,
                        'code'          => $row->code,   
                        'from_city'     => $row->from_city,
                        'to_city'       => $row->to_city, 
                        'pickuptime'    => $row->pickuptime,
                        'driver_id'     => $row->driver_id,
                        'user'          => [
                            'id'    => $row->user_id,
                            'name'  => $row->user_name,
                            'email' => $row->email,
                            'phone' => $row->phone,
                        ]
                    ];

                    if(empty($row->driver_id)) $orders[$tId]['unasssigned_rides']++;
                }

                foreach($orders as $tId => &$tour) {
                    if($tour['unasssigned_rides'] > 0) {
                        $drivers = $this->availableDrivers($tomorrow, $tId);
                        if(!empty($drivers)) $tour['drivers'] = $drivers;
                        else $tour['drivers'] = null;
                    } else {
                        $tour['drivers'] = true;
                    }
                }
                unset($tour);
                
                echo json_encode(['orders' => $orders, 'has_orders' => !empty($orders)]);
            }
        } catch(PDOException $e) {
            http_response_code(500);
            echo json_encode([
                'error' => 'Došlo je do greške pri učitavanju iz baze',
                'msg' => $e->getMessage() 
            ]);
        }
        
    }
    */

    public function getAll($in24, $in48) 
    {
        if ($in24 && empty($in48)) {
            $tomorrow = date("Y-m-d", strtotime("+1 day"));
        } elseif ($in48 && empty($in24)) {
            $tomorrow = date("Y-m-d", strtotime("+2 days"));
        } else {
            echo json_encode(['error' => 'Odaberite 24h ili 48h']);
            exit();
        } 

        $sql = "SELECT 
                    order_items.id AS order_item_id,
                    order_items.driver_id,
                    orders.id AS order_id,
                    tours.id AS tour_id,
                    orders.user_id AS user_id,
                    order_items.places,
                    order_items.add_from AS pickup,
                    tours.to_city,
                    order_items.add_to AS dropoff,
                    tours.from_city,
                    order_items.date,
                    tours.time AS pickuptime,
                    tours.duration,
                    tours.seats,
                    orders.total AS price,
                    orders.code,
                    orders.file_path AS voucher,
                    users.name AS user_name,
                    users.email AS email,
                    users.phone AS phone
                FROM orders 
                INNER JOIN order_items ON order_items.order_id = orders.id
                INNER JOIN tours ON order_items.tour_id = tours.id
                INNER JOIN users ON orders.user_id = users.id
                WHERE orders.deleted = 0 
                AND order_items.deleted = 0
                AND order_items.date = :tomorrow
                ORDER BY tours.id, order_items.date, pickuptime";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':tomorrow', $tomorrow);

        try {
            if ($stmt->execute()) {
                $rows = $stmt->fetchAll(PDO::FETCH_OBJ);
                $orders = [];

                foreach ($rows as $row) {
                    $tId = $row->tour_id;

                    if (!isset($orders[$tId])) {
                        $orders[$tId] = [
                            'tour_id' => $row->tour_id,
                            'from_city' => $row->from_city,
                            'to_city' => $row->to_city,
                            'pickuptime' => $row->pickuptime,
                            'duration' => $row->duration,
                            'date' => $tomorrow,
                            'seats' => $row->seats,
                            'rides' => [],
                            'total_places' => 0,
                            'unassigned_rides' => 0
                        ];
                    }

                    $orders[$tId]['rides'][] = [
                        'order_item_id' => $row->order_item_id,
                        'order_id'      => $row->order_id,
                        'places'        => $row->places,
                        'pickup'        => $row->pickup,
                        'dropoff'       => $row->dropoff,
                        'date'          => $row->date,
                        'price'         => $row->price,
                        'voucher'       => $row->voucher,
                        'code'          => $row->code,   
                        'from_city'     => $row->from_city,
                        'to_city'       => $row->to_city, 
                        'pickuptime'    => $row->pickuptime,
                        'driver_id'     => $row->driver_id,
                        'user'          => [
                            'id'    => $row->user_id,
                            'name'  => $row->user_name,
                            'email' => $row->email,
                            'phone' => $row->phone,
                        ]
                    ];

                    $orders[$tId]['total_places'] += $row->places;

                    if (empty($row->driver_id)) {
                        $orders[$tId]['unassigned_rides']++;
                    }
                }

                $tourIDs = array_keys($orders);

                // Svi vozači iz helpera
                $driversData = $this->availableDrivers($tomorrow, $tourIDs);
                if($driversData) {
                    $driversByCity = $driversData['drivers_by_city'];
                    $tourCities = $driversData['tour_cities'];
                } else {
                    $driversByCity = [];
                    $tourCities = [];
                }
                

                // Povezati vozače i ture
                foreach ($orders as $tId => &$tour) {
                    $tourCity = null;
                    foreach ($tourCities as $tc) {
                        if ($tc->id == $tId) {
                            $tourCity = $tc;
                            break;
                        }
                    }

                    $hasUnassigned = $tour['unassigned_rides'] > 0;

                    if ($hasUnassigned) {
                        $available = [];

                        if (isset($driversByCity[$tourCity->from_city]))
                            $available = array_merge($available, $driversByCity[$tourCity->from_city]);
                        if (isset($driversByCity[$tourCity->to_city]))
                            $available = array_merge($available, $driversByCity[$tourCity->to_city]);

                        $tour['drivers'] = !empty($available) ? $available : null;
                    } else {
                        $tour['drivers'] = true;
                    }
                }
                unset($tour);

                echo json_encode([
                    'orders' => $orders,
                    'has_orders' => !empty($orders)
                ]);
            }
        } catch (PDOException $e) {
            $this->logger->error("Failed to fetch upcoming bookings in getAll(24, 48).", [
                'DB error' => $e->getMessage(),
                'file' => __FILE__,
                'line' => __LINE__  
            ]);
            http_response_code(500);
            echo json_encode(['error' => 'Došlo je do greške pri pretrazi']);
        }
    }


    public function getAllByFilter($email) 
    {
        if($this->code) return $this->getByCode();

        $params = [
            'date' => $this->date,
            'from_city' => $this->from_city,
            'to_city' => $this->to_city,
            'tour_id' => $this->tour_id,
            'email' => $email
        ];

        $params = array_filter($params, fn($p) => !empty($p));

        $cleaned = Validator::cleanParams($params);

        $sql = "SELECT orders.id as order_id, order_items.id as item_id, 
                order_items.tour_id, orders.user_id, order_items.places, tours.from_city, 
                order_items.add_from as pickup, tours.to_city, order_items.add_to as dropoff,
                order_items.date, order_items.price, order_items.deleted,
                tours.time as pickuptime, tours.duration,
                orders.total, orders.code, orders.file_path as voucher, 
                users.name as user, users.email, users.phone, users.city as user_city
                from order_items
                JOIN orders on orders.id = order_items.order_id
                JOIN tours on order_items.tour_id = tours.id
                JOIN users on orders.user_id = users.id
                
                WHERE 1=1"
        ;

        if(isset($cleaned['date'])) $sql .= " AND order_items.date = :date ";
        if(isset($cleaned['from_city'])) $sql .= " AND tours.from_city = :from_city";
        if(isset($cleaned['to_city'])) $sql .= " AND tours.to_city = :to_city";
        if(isset($cleaned['tour_id'])) $sql .= " AND order_items.tour_id = :tour_id";
        if(isset($cleaned['email'])) $sql .= " AND users.email = :email";

        $stmt = $this->db->prepare($sql);
        foreach($cleaned as $k => $v) {
            $stmt->bindValue(':' . $k, $v);
        }

        try {
            $stmt->execute();
            $orders = $stmt->fetchAll(PDO::FETCH_OBJ);

            foreach ($orders as $order) {
                $logs = $this->logger->getOrderLogs($order->item_id);
                $order->logs = $logs;
            }

            header('Content-Type: application/json');
            echo json_encode(['orders' => $orders, 'has_orders' => !empty($orders)], JSON_PRETTY_PRINT);
        } catch (PDOException $e) {
            $this->logger->error("Failed to get order_items by filters in getAllByFilter()", [
                'user_id' => $_SESSION['user']['id'],
                'error' => $e->getMessage(),
                'file' => __FILE__,
                'line' => __LINE__
            ]);
            http_response_code(500);
            echo json_encode([
                'error' => 'Došlo je do greške pri filtriranju rezervacija!'
            ]);
        }
    }

    public function getAllByDateRange(? string $from, ? string $to)
    {
        $now = date("Y-m-d");
        $sql = "";
        if(isset($from) && isset($to)) {
            $sql = "SELECT orders.id, orders.tour_id, orders.user_id, orders.places, tours.from_city, 
                orders.add_from as pickup, tours.to_city, orders.add_to as dropoff,
                orders.date, tours.time as pickuptime, tours.duration,
                orders.total as price, orders.code, orders.file_path as voucher, users.name as user, users.email, users.phone
                from orders 
                INNER JOIN tours on orders.tour_id = tours.id
                INNER JOIN users on orders.user_id = users.id
                WHERE orders.date >= '$from' AND orders.date <= '$to' AND orders.deleted = 0
                ORDER BY orders.date"
            ;
        } elseif(isset($from) && !isset($to)) {
            $sql = "SELECT orders.id, orders.tour_id, orders.user_id, orders.places, tours.from_city, 
                orders.add_from as pickup, tours.to_city, orders.add_to as dropoff,
                orders.date, tours.time as pickuptime, tours.duration,
                orders.total as price, orders.code, orders.file_path as voucher, users.name as user, users.email, users.phone
                from orders 
                INNER JOIN tours on orders.tour_id = tours.id
                INNER JOIN users on orders.user_id = users.id
                WHERE orders.date >= '$from' AND orders.deleted = 0
                ORDER BY orders.date"
            ;
        } elseif(!isset($from) && isset($to)) {
            $sql = "SELECT orders.id, orders.tour_id, orders.user_id, orders.places, tours.from_city, 
                orders.add_from as pickup, tours.to_city, orders.add_to as dropoff,
                orders.date, tours.time as pickuptime, tours.duration,
                orders.total as price, orders.code, orders.file_path as voucher, users.name as user, users.email, users.phone
                from orders 
                INNER JOIN tours on orders.tour_id = tours.id
                INNER JOIN users on orders.user_id = users.id
                WHERE orders.date >= '$now' AND orders.date <= '$to' AND orders.deleted = 0
                ORDER BY orders.date"
            ;
        }
        
        $res = $this->db->query($sql);
        $num = $res->rowCount();

        if($num > 0) {
            $orders = [];
            while($row = $res->fetch(PDO::FETCH_OBJ)) {
                array_push($orders, $row);
            }
            echo json_encode([
                'orders'=> $orders
            ], JSON_PRETTY_PRINT);
        } else
        echo json_encode(['msg' => 'Nema rezervisanih vožnji za odabrane datume.'], JSON_PRETTY_PRINT);
    }

    public function getByUser(): void 
    {
        if (empty($this->user_id)) {
            http_response_code(400);
            echo json_encode([
                'error' => 'User ID je obavezan'
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        $sql = "SELECT orders.id, order_items.id as item_id, 
                order_items.tour_id, orders.user_id, order_items.places, 
                tours.from_city, order_items.add_from as pickup, 
                tours.to_city, order_items.add_to as dropoff,
                order_items.date, order_items.price, order_items.deleted,
                tours.time as pickuptime, tours.duration,
                orders.total, orders.code, orders.file_path as voucher, 
                users.name as user, users.email, users.phone
                FROM orders 
                INNER JOIN order_items ON orders.id = order_items.order_id
                INNER JOIN tours ON order_items.tour_id = tours.id
                INNER JOIN users ON orders.user_id = users.id
                WHERE orders.user_id = :user_id 
                AND orders.deleted = 0
                ORDER BY order_items.date ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':user_id', $this->user_id, PDO::PARAM_INT);

        try {
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_OBJ);
            
            if (empty($rows)) {
                echo json_encode([
                    'success' => true,
                    'orders' => []
                ], JSON_UNESCAPED_UNICODE);
                return;
            }

            // Group by orderID
            $orders = [];
            
            foreach ($rows as $row) {
                $orderId = $row->id;

                if (!isset($orders[$orderId])) {
                    $order = new stdClass();
                    $order->id = $row->id;
                    $order->code = $row->code;
                    $order->duration = $row->duration;
                    $order->voucher = $row->voucher;
                    $order->total = $row->total;
                    $order->items = [];
                    $orders[$orderId] = $order;
                }

                $item = new stdClass();
                $item->id = $row->item_id;
                $item->tour_id = $row->tour_id;
                $item->date = $row->date;
                $item->time = $row->pickuptime;
                $item->pickup = $row->pickup;
                $item->dropoff = $row->dropoff;
                $item->places = $row->places;
                $item->price = $row->price;
                $item->from = $row->from_city;
                $item->to = $row->to_city;
                $item->deleted = $row->deleted;

                // logs
                $logs = $this->logger->getOrderLogs($row->item_id);
                $item->logs = $logs;

                $orders[$orderId]->items[] = $item;
            }

            echo json_encode([
                'success' => true,
                'orders' => array_values($orders)
            ], JSON_UNESCAPED_UNICODE);
            
        } catch (PDOException $e) {
            $this->logger->error('Failed in getByUser()', [
                'user_id' => $this->user_id,
                'error' => $e->getMessage(),
                'file' => __FILE__,
                'line' => __LINE__
            ]);
            
            http_response_code(500);
            echo json_encode([
                'error' => 'Greška pri učitavanju rezervacija'
            ], JSON_UNESCAPED_UNICODE);
        }
    }

    public function getByCode(): void 
    {
        if (empty($this->code) || !Validator::validateCode($this->code)) {
            http_response_code(400);
            echo json_encode([
                'error' => 'Pogrešan format koda rezervacije (format: xxxxxxxKP)'
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        $sql = "SELECT orders.id as order_id, order_items.id as item_id, 
                order_items.tour_id, orders.user_id, order_items.places, 
                tours.from_city, order_items.add_from as pickup, 
                tours.to_city, order_items.add_to as dropoff,
                order_items.date, order_items.price, order_items.deleted,
                tours.time as pickuptime, tours.duration,
                orders.total, orders.code, orders.file_path as voucher, 
                users.name as user, users.email, users.phone, 
                users.city as user_city
                FROM order_items
                INNER JOIN orders ON orders.id = order_items.order_id
                INNER JOIN tours ON order_items.tour_id = tours.id
                INNER JOIN users ON orders.user_id = users.id 
                WHERE orders.code = :code
                AND orders.deleted = 0";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':code', $this->code, PDO::PARAM_STR);

        try {
            $stmt->execute();
            $orders = $stmt->fetchAll(PDO::FETCH_OBJ);

            // Dodaj logs za svaki item
            foreach ($orders as $order) {
                $logs = $this->logger->getOrderLogs($order->item_id);
                $order->logs = $logs;
            }

            echo json_encode([
                'orders' => $orders, 
                'has_orders' => !empty($orders)
            ], JSON_UNESCAPED_UNICODE);
            
        } catch (PDOException $e) {
            $this->logger->error('Failed in getByCode()', [
                'code' => $this->code,
                'error' => $e->getMessage(),
                'file' => __FILE__,
                'line' => __LINE__
            ]);
            
            http_response_code(500);
            echo json_encode([
                'error' => 'Greška pri pretrazi rezervacije'
            ], JSON_UNESCAPED_UNICODE);
        }
    }

    public function getItems(int $order_id): void 
    {
        $sql = "SELECT order_items.*, orders.user_id, orders.code, 
                orders.file_path as voucher, orders.total
                FROM order_items 
                INNER JOIN orders ON order_items.order_id = orders.id
                WHERE orders.id = :id
                AND orders.deleted = 0"; 
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $order_id, PDO::PARAM_INT);

        try {
            $stmt->execute();
            
            $order = new stdClass();
            $order->items = [];
            
            while ($row = $stmt->fetch(PDO::FETCH_OBJ)) {
                $order->items[] = $row;
            }
            
            $this->items = $order;
            
        } catch (PDOException $e) {
            $this->logger->error('Failed to get order items', [
                'order_id' => $order_id,
                'error' => $e->getMessage(),
                'file' => __FILE__,
                'line' => __LINE__
            ]);
            $this->items = null;
        }
    }

    //GET order from DB by item ID
    public function getFromDB(int $id)
    {
        $sql = "SELECT order_items.*, orders.* FROM order_items 
        INNER JOIN orders on order_items.order_id = orders.id
        WHERE order_items.id = :id";
        $stmt = $this->db->prepare($sql);

        $stmt->bindParam(':id', $id, PDO::PARAM_INT);

        try {
            if($stmt->execute()) {
                $order = $stmt->fetch(PDO::FETCH_OBJ);
                if($order) {
                    $this->tour_id = (int) $order->tour_id;
                    $this->user_id = (int) $order->user_id;
                    $this->places = (int) $order->places;
                    $this->add_from = $order->add_from;
                    $this->add_to = $order->add_to;
                    $this->date = $order->date;
                    $this->total = (int) $order->total;
                    $this->price = (int) $order->price;
                    $this->code = $order->code;
                    $this->voucher = $order->file_path;
                    $this->driver_id = $order->driver_id ? (int)$order->driver_id : null;
                    $this->dep_id = $order->dep_id ? (int)$order->dep_id : null;
                    $this->order_id = $order->order_id;

                    $this->getItems($this->order_id);
                    
                    return $order; 
                } 
                return null;
            }
        } catch (PDOException $e) {
            echo json_encode(['error' => $e->getMessage()]);
            return null;
        }
    }

    public function getByTour(): void {
        $sql = "SELECT orders.id, orders.tour_id, orders.user_id, orders.places, tours.from_city, 
                orders.add_from as pickup, tours.to_city, orders.add_to as dropoff,
                orders.date, tours.time as pickuptime, tours.duration,
                orders.total as price, orders.code, orders.file_path as voucher, users.name as user, users.email, users.phone
                from orders 
                INNER JOIN tours on orders.tour_id = tours.id
                INNER JOIN users on orders.user_id = users.id
                WHERE orders.tour_id = '$this->tour_id' AND orders.deleted = 0"
        ;
        $res = $this->db->query($sql);
        $num = $res->rowCount();

        if($num > 0) {
            $orders = [];
            while($row = $res->fetch(PDO::FETCH_OBJ)) {
                array_push($orders, $row);
            }
            echo json_encode([
                'orders'=> $orders
            ], JSON_PRETTY_PRINT);
        } else
        echo json_encode(['msg' => 'Nema rezervisanih vožnji za odabrane destinacije.'], JSON_PRETTY_PRINT);
    }

    public function getByTourAndDate() {
        $sql = "SELECT orders.id, orders.tour_id, orders.user_id, orders.places, tours.from_city, 
                orders.add_from as pickup, tours.to_city, orders.add_to as dropoff,
                orders.date, tours.time as pickuptime, tours.duration,
                orders.total as price, orders.code, orders.file_path as voucher, users.name as user, users.email, users.phone
                from orders 
                INNER JOIN tours on orders.tour_id = tours.id
                INNER JOIN users on orders.user_id = users.id
                WHERE orders.tour_id = '$this->tour_id' AND orders.date = '$this->date' AND orders.deleted = 0"
        ;
        $res = $this->db->query($sql);
        $num = $res->rowCount();

        if($num > 0) {
            $drivers = $this->user->getAvailableDrivers($this->date);
            $orders = [];
            while($row = $res->fetch(PDO::FETCH_OBJ)) {
                array_push($orders, $row);
            }
            echo json_encode([
                'orders'=> $orders,
                'drivers' => $drivers
            ], JSON_PRETTY_PRINT);
        } else
        echo json_encode(['msg' => 'Nema rezervisanih vožnji za odabrane datume.'], JSON_PRETTY_PRINT);
    }

    public function getDriverOfTour()
    {
        $sql = "SELECT orders.id, order_items.id as item_id, order_items.tour_id, 
                order_items.driver_id, order_items.places, order_items.deleted, tours.from_city, 
                order_items.add_from as pickup, tours.to_city, order_items.add_to as dropoff,
                order_items.date, tours.time as pickuptime, tours.duration,
                order_items.price, orders.total, orders.code, orders.file_path as voucher
                
                from order_items 
                INNER JOIN orders on order_items.order_id = orders.id
                INNER JOIN tours on order_items.tour_id = tours.id
                
                WHERE orders.id = :id
                ORDER BY order_items.date ASC"
        ;

        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $this->order_id, PDO::PARAM_INT);
        try {
            $stmt->execute();
            $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($orders)) {
                return null;
            }
        
            $items = [];
        
            foreach($orders as $order) {
                $driver = null;

                if($order['driver_id']) {
                    $sdr = "SELECT users.name as dr_name, users.email as dr_email, 
                        users.phone as dr_phone 
                        FROM users
                        WHERE id = :driver_id";
                
                    $stmtDriver = $this->db->prepare($sdr);
                    $stmtDriver->bindParam(':driver_id', $order['driver_id'], PDO::PARAM_INT);
                    $stmtDriver->execute();
                    $driver = $stmtDriver->fetch(PDO::FETCH_ASSOC);
                }
                $items[] = [
                    'order' => $order,
                    'driver' => $driver
                ];
            }
            
            return [
                'items' => $items
            ];
        } catch(PDOException $e) {
            $this->logger->error('Failed to fetch driver of tour for generate Voucher in getDriverOfTour()', [
                'error' => $e->getMessage(),
                'file' => __FILE__,
                'line' => __LINE__
            ]);
        }
        
    }

    //------------------------------- FUNCTIONS OF POST METHOD --------------------------------//

    public function create(): void 
    {
        if (empty($this->items->create) || !is_array($this->items->create)) {
            http_response_code(400);
            echo json_encode([
                'error' => 'Nedostaju stavke rezervacije'
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        // Validacija user_id
        $this->user_id = (int)$this->items->create[0]->user_id;
        
        if ($this->user_id <= 0) {
            http_response_code(400);
            echo json_encode([
                'error' => 'Neispravan ID korisnika'
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        // Provera da li user postoji
        $userCheckSql = "SELECT id FROM users WHERE id = :user_id AND deleted = 0";
        $userStmt = $this->db->prepare($userCheckSql);
        $userStmt->bindParam(':user_id', $this->user_id, PDO::PARAM_INT);
        
        try {
            $userStmt->execute();
            if (!$userStmt->fetch()) {
                http_response_code(404);
                echo json_encode([
                    'error' => 'Korisnik nije pronađen'
                ], JSON_UNESCAPED_UNICODE);
                return;
            }
        } catch (PDOException $e) {
            $this->logger->error('Failed to validate user in create()', [
                'user_id' => $this->user_id,
                'error' => $e->getMessage(),
                'file' => __FILE__,
                'line' => __LINE__
            ]);
            
            http_response_code(500);
            echo json_encode([
                'error' => 'Greška pri validaciji korisnika'
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        // Generisanje koda rezervacije
        $now = time() + $this->user_id;
        $generated = (string)$now . "KP";
        $new_code = substr($generated, -9);
        $file_path = "src/assets/pdfs/" . $new_code . ".pdf";

        // Izračunavanje ukupne cene
        $total = 0; 
        foreach ($this->items->create as $item) {
            $itemPrice = self::totalPrice($this->db, (int)$item->tour_id, (int)$item->places);
            
            if ($itemPrice === null) {
                http_response_code(400);
                echo json_encode([
                    'error' => 'Nevalidna tura ili broj mesta'
                ], JSON_UNESCAPED_UNICODE);
                return;
            }
            
            $total += $itemPrice;
        }

        // TRANSACTION START sa LOCKING
        try {
            $this->db->beginTransaction();
            
            // 1. INSERT u orders tabelu
            $orderSql = "INSERT INTO orders (user_id, total, code, file_path) 
                        VALUES (:user_id, :total, :code, :pdf)";
            
            $orderStmt = $this->db->prepare($orderSql);
            $orderStmt->bindParam(':user_id', $this->user_id, PDO::PARAM_INT);
            $orderStmt->bindParam(':total', $total, PDO::PARAM_INT);
            $orderStmt->bindParam(':code', $new_code, PDO::PARAM_STR);
            $orderStmt->bindParam(':pdf', $file_path, PDO::PARAM_STR);
            
            $orderStmt->execute();
            $this->id = (int)$this->db->lastInsertId();
            
            // 2. INSERT svih order_items SA ROW LOCKING
            foreach ($this->items->create as $item) {
                $this->tour_id = (int)$item->tour_id;
                $this->date = $item->date;
                $this->places = (int)$item->places;
                $this->add_from = $item->add_from;
                $this->add_to = $item->add_to;

                // Validacija datuma
                if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $this->date)) {
                    throw new Exception('Neispravan format datuma: ' . $this->date);
                }

                // LOCK ROW - provera dostupnosti sa FOR UPDATE
                $lockSql = "SELECT COALESCE(SUM(oi.places), 0) as occupied, t.seats 
                            FROM tours t
                            LEFT JOIN order_items oi ON oi.tour_id = t.id 
                                AND oi.date = :date
                            LEFT JOIN orders o ON oi.order_id = o.id 
                                AND o.deleted = 0
                            WHERE t.id = :tour_id 
                            AND t.deleted = 0
                            FOR UPDATE"; 
                
                $lockStmt = $this->db->prepare($lockSql);
                $lockStmt->bindParam(':date', $this->date, PDO::PARAM_STR);
                $lockStmt->bindParam(':tour_id', $this->tour_id, PDO::PARAM_INT);
                $lockStmt->execute();
                
                $availability = $lockStmt->fetch(PDO::FETCH_OBJ);
                
                if (!$availability) {
                    throw new Exception("Tura sa ID {$this->tour_id} ne postoji");
                }

                $availableSeats = (int)$availability->seats - (int)$availability->occupied;

                // Provera dostupnosti
                if ($this->places > $availableSeats) {
                    throw new Exception("Nema dovoljno mesta. Dostupno: {$availableSeats}");
                }

                // Provera departure day
                if (!$this->isDeparture($this->date)) {
                    throw new Exception("Nema polazaka za datum: {$this->date}");
                }

                // Provera unlock (25h minimum)
                if (!$this->isUnlocked($this->date)) {
                    throw new Exception("Rezervacija mora biti najmanje 25h unapred");
                }

                // total price
                $this->price = self::totalPrice($this->db, $this->tour_id, $this->places);
                
                if ($this->price === null) {
                    throw new Exception("Greška pri računanju cene");
                }

                // INSERT order_item
                $itemSql = "INSERT INTO order_items 
                            (order_id, tour_id, places, add_from, add_to, date, price) 
                            VALUES (:order_id, :tour_id, :places, :add_from, :add_to, :date, :price)";
                
                $itemStmt = $this->db->prepare($itemSql);
                $itemStmt->bindParam(':order_id', $this->id, PDO::PARAM_INT);
                $itemStmt->bindParam(':tour_id', $this->tour_id, PDO::PARAM_INT);
                $itemStmt->bindParam(':places', $this->places, PDO::PARAM_INT);
                $itemStmt->bindParam(':add_from', $this->add_from, PDO::PARAM_STR);
                $itemStmt->bindParam(':add_to', $this->add_to, PDO::PARAM_STR);
                $itemStmt->bindParam(':date', $this->date, PDO::PARAM_STR);
                $itemStmt->bindParam(':price', $this->price, PDO::PARAM_INT);
                
                $itemStmt->execute();
            }

            // COMMIT transaction
            $this->db->commit();

            // Generate and send voucher
            try {
                $mydata = $this->generateVoucher($total);
                $this->sendVoucher(
                    $mydata['email'], 
                    $mydata['name'], 
                    $mydata['path'], 
                    $mydata['code'], 
                    'create'
                );
            } catch (Exception $e) {
                $this->logger->error('Failed to generate/send voucher', [
                    'order_id' => $this->id,
                    'code' => $new_code,
                    'error' => $e->getMessage(),
                    'file' => __FILE__,
                    'line' => __LINE__
                ]);
            }

            // SUCCESS response
            http_response_code(200);
            echo json_encode([
                'success' => true,
                'msg' => "Uspešno ste rezervisali vožnju. Vaš broj rezervacije je: {$new_code}",
                'code' => $new_code
            ], JSON_UNESCAPED_UNICODE);

        } catch (Exception $e) {
            // ROLLBACK 
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            $this->logger->error('Order creation failed', [
                'user_id' => $this->user_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'file' => __FILE__,
                'line' => __LINE__
            ]);

            http_response_code(422);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
        }
    }

    //------------------------------- FUNCTIONS OF PUT METHOD --------------------------------// 
    public function updateAddress(): array
    {
        if (empty($this->new_add_from) && empty($this->new_add_to)) {
            return ['msg' => '']; 
        }

        if (empty($this->new_add_from) || empty($this->new_add_to)) {
            return ['error' => 'Molimo unesite obe adrese (polazak i dolazak)'];
        }

        $sql = "UPDATE order_items 
                SET add_from = :add_from, add_to = :add_to 
                WHERE id = :id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $this->id, PDO::PARAM_INT);
        $stmt->bindParam(':add_from', $this->new_add_from, PDO::PARAM_STR);
        $stmt->bindParam(':add_to', $this->new_add_to, PDO::PARAM_STR);

        try {
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $this->logger->logOrderChange(
                    $this->id, 
                    $_SESSION['user']['id'], 
                    'Ažuriranje', 
                    'Adresa od/do', 
                    "Polazak: {$this->add_from} / Dolazak: {$this->add_to}", 
                    "Polazak: {$this->new_add_from} / Dolazak: {$this->new_add_to}"
                );
                
                return [
                    'success' => true,
                    'msg' => 'Uspešno ste izmenili adrese rezervacije.'
                ];
            }
            
            return ['error' => 'Nije moguće ažurirati adrese'];
            
        } catch (PDOException $e) {
            $this->logger->error('Failed to update addresses', [
                'user_id' => $_SESSION['user']['id'],
                'order_item_id' => $this->id,
                'error' => $e->getMessage(),
                'file' => __FILE__,
                'line' => __LINE__
            ]);
            
            return ['error' => 'Greška pri ažuriranju adresa'];
        }
    }

    public function updatePlaces(): array 
    {
        $this->getFromDB($this->id);
        
        if ($this->newPlaces <= 0) {
            return ['error' => 'Broj mesta mora biti veći od 0'];
        }
        
        // Provera dostupnosti
        $diff = $this->newPlaces - $this->places;
        $available = $this->availability($this->date);
        
        if ($diff > $available) {
            return [
                'error' => "Nema dovoljno mesta. Dostupno: " . ($available + $this->places)
            ];
        }

        $sql = "UPDATE order_items 
                SET places = :places, price = :total 
                WHERE id = :id";
        
        $stmt = $this->db->prepare($sql);

        $new_total = self::totalPrice($this->db, $this->tour_id, $this->newPlaces);
        
        if ($new_total === null) {
            return ['error' => 'Greška pri računanju nove cene'];
        }

        $stmt->bindParam(':places', $this->newPlaces, PDO::PARAM_INT);
        $stmt->bindParam(':total', $new_total, PDO::PARAM_INT);
        $stmt->bindParam(':id', $this->id, PDO::PARAM_INT);
        
        try {
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                // Update total price u orders
                $this->updateTotalPrice();
                
                // Regeneriši voucher
                try {
                    $mydata = $this->reGenerateVoucher();
                    $this->sendVoucher(
                        $mydata['email'], 
                        $mydata['name'], 
                        $mydata['path'], 
                        $this->code, 
                        'update'
                    );
                } catch (Exception $e) {
                    $this->logger->error('Failed to regenerate voucher in updatePlaces', [
                        'order_item_id' => $this->id,
                        'error' => $e->getMessage()
                    ]);
                }
                
                $this->logger->logOrderChange(
                    $this->id, 
                    $_SESSION['user']['id'], 
                    'Ažuriranje',
                    'Broj mesta', 
                    (string)$this->places, 
                    (string)$this->newPlaces
                );
                
                return [
                    'success' => true,
                    'msg' => "Uspešno ste izmenili broj mesta na {$this->newPlaces}."
                ];
            }
            
            return ['error' => 'Nije moguće ažurirati broj mesta'];
            
        } catch (PDOException $e) {
            $this->logger->error('Failed to update places', [
                'user_id' => $_SESSION['user']['id'],
                'order_item_id' => $this->id,
                'error' => $e->getMessage(),
                'file' => __FILE__,
                'line' => __LINE__
            ]);
            
            return ['error' => 'Greška pri ažuriranju broja mesta'];
        } 
    }
    // RESCHEDULE outbound

    private function outbound(bool $sendVoucher): array 
    {
        if (!$this->isDeparture($this->newDate)) {
            return ['error' => 'Nema polazaka za odabrani datum'];
        }

        if (!$this->isUnlocked($this->newDate) 
            && !Validator::isAdmin() 
            && !Validator::isSuper()) {
            return ['error' => 'Novi datum mora biti najmanje 25h od sada'];
        }

        $availableSeats = $this->availability($this->newDate);
        
        if ($this->items->items[0]->places > $availableSeats) {
            return ['error' => "Nema dovoljno mesta za novi datum. Dostupno: {$availableSeats}"];
        }

        $sql = "UPDATE order_items SET date = :date WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $this->items->items[0]->id, PDO::PARAM_INT);
        $stmt->bindParam(':date', $this->newDate, PDO::PARAM_STR);
        
        try {
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $this->updateTotalPrice();
                
                if ($sendVoucher) {
                    try {
                        $mydata = $this->reGenerateVoucher();
                        $this->sendVoucher(
                            $mydata['email'], 
                            $mydata['name'], 
                            $mydata['path'], 
                            $this->code, 
                            'update'
                        );
                    } catch (Exception $e) {
                        $this->logger->error('Failed to send voucher in outbound', [
                            'error' => $e->getMessage()
                        ]);
                    }
                }
                
                $this->logger->logOrderChange(
                    $this->id, 
                    $_SESSION['user']['id'], 
                    'Ažuriranje', 
                    'Datum polaska',
                    $this->items->items[0]->date, 
                    $this->newDate
                );
                
                return ['success' => true, 'msg' => 'Datum polaska uspešno promenjen'];
            }
            
            return ['error' => 'Nije moguće promeniti datum'];
            
        } catch (PDOException $e) {
            $this->logger->error('Failed to reschedule outbound', [
                'order_item_id' => $this->items->items[0]->id,
                'new_date' => $this->newDate,
                'error' => $e->getMessage()
            ]);
            
            return ['error' => 'Greška pri promeni datuma polaska'];
        }
    }

    // RESCHEDULE inbound

    private function inbound(bool $sendVoucher): array
    {
        if (!isset($this->items->items[1])) {
            return ['error' => 'Nema povratne vožnje'];
        }

        if (!$this->isDeparture($this->newDateIn)) {
            return ['error' => 'Nema polazaka za odabrani datum povratka'];
        }

        if (!$this->isUnlocked($this->newDateIn) 
            && !Validator::isAdmin() 
            && !Validator::isSuper()) {
            return ['error' => 'Novi datum mora biti najmanje 25h od sada'];
        }

        $availableSeats = $this->availability($this->newDateIn);
        
        if ($this->items->items[1]->places > $availableSeats) {
            return ['error' => "Nema dovoljno mesta za novi datum. Dostupno: {$availableSeats}"];
        }

        $sql = "UPDATE order_items SET date = :date WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $this->items->items[1]->id, PDO::PARAM_INT);
        $stmt->bindParam(':date', $this->newDateIn, PDO::PARAM_STR);
        
        try {
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $this->updateTotalPrice();
                
                if ($sendVoucher) {
                    try {
                        $mydata = $this->reGenerateVoucher();
                        $this->sendVoucher(
                            $mydata['email'], 
                            $mydata['name'], 
                            $mydata['path'], 
                            $this->code, 
                            'update'
                        );
                    } catch (Exception $e) {
                        $this->logger->error('Failed to send voucher in inbound', [
                            'error' => $e->getMessage()
                        ]);
                    }
                }
                
                $this->logger->logOrderChange(
                    $this->id, 
                    $_SESSION['user']['id'], 
                    'Ažuriranje', 
                    'Datum povratka',
                    $this->items->items[1]->date, 
                    $this->newDateIn
                );
                
                return ['success' => true, 'msg' => 'Datum povratka uspešno promenjen'];
            }
            
            return ['error' => 'Nije moguće promeniti datum'];
            
        } catch (PDOException $e) {
            $this->logger->error('Failed to reschedule inbound', [
                'order_item_id' => $this->items->items[1]->id,
                'new_date' => $this->newDateIn,
                'error' => $e->getMessage()
            ]);
            
            return ['error' => 'Greška pri promeni datuma povratka'];
        }
    }

    // RESCHEDULE date all bounds
    public function reschedule(): array 
    {
        try {
            $hasOutbound = !empty($this->newDate);
            $hasInbound = !empty($this->newDateIn);

            if (!$hasOutbound && !$hasInbound) {
                return ['error' => 'Unesite bar jedan datum'];
            }

            // Ako su oba datuma
            if ($hasOutbound && $hasInbound) {
                $outb = $this->outbound(false);
                
                if (isset($outb['error'])) {
                    return $outb; 
                }

                $inb = $this->inbound(false);
                
                if (isset($inb['error'])) {
                    return $inb; 
                }

                // Oba uspešna - regeneriši voucher
                try {
                    $mydata = $this->reGenerateVoucher();
                    $this->sendVoucher(
                        $mydata['email'], 
                        $mydata['name'], 
                        $mydata['path'], 
                        $this->code, 
                        'update'
                    );
                } catch (Exception $e) {
                    $this->logger->error('Failed to send voucher after reschedule', [
                        'order_id' => $this->id,
                        'error' => $e->getMessage()
                    ]);
                }

                return [
                    'success' => true,
                    'msg' => 'Uspešno ste izmenili datume vožnji'
                ];
            }

            // Samo outbound
            if ($hasOutbound) {
                return $this->outbound(true);
            }

            // Samo inbound
            if ($hasInbound) {
                return $this->inbound(true);
            }

            return ['error' => 'Neočekivana greška'];
            
        } catch (Exception $e) {
            $this->logger->error('Reschedule failed', [
                'user_id' => $_SESSION['user']['id'],
                'order_item_id' => $this->id,
                'error' => $e->getMessage(),
                'file' => __FILE__,
                'line' => __LINE__
            ]);
            
            return ['error' => 'Greška pri promeni datuma'];
        }
    }

    // UPDATE PLACES and DATE - Refactor until here
    public function rescheduleAndPlaces() 
    {
        //$this->getFromDB($this->id);
        if(isset($this->newDate) && !empty($this->newDate) && isset($this->newPlaces) && !empty($this->newPlaces)) {
            if($this->isDeparture($this->newDate)) {
                if($this->newPlaces <= $this->availability($this->newDate)) {
                    $sql = "UPDATE orders SET places = :places, total = :total, date = :date WHERE id = :id";
                    $stmt = $this->db->prepare($sql);
                    /* NEPOTREBNO
                    $this->id = htmlspecialchars(strip_tags($this->id));
                    $this->places = htmlspecialchars(strip_tags($this->places));
                    $this->price = htmlspecialchars(strip_tags($this->price));
                    $this->newPlaces = htmlspecialchars(strip_tags($this->newPlaces));
                    */
                    $new_total = $this->totalPrice($this->db, $this->tour_id, $this->newPlaces);
                    //($this->price / $this->places) * $this->newPlaces;
                    $this->newDate = htmlspecialchars(strip_tags($this->newDate));

                    $stmt->bindParam(':id', $this->id);
                    $stmt->bindParam(':places', $this->newPlaces);
                    $stmt->bindParam(':total', $new_total);
                    $stmt->bindParam('date', $this->newDate);

                    $formated = date_create($this->newDate);
                    $d = date("d.m.Y", date_timestamp_get($formated));

                    if($stmt->execute()) {
                        $mydata = $this->reGenerateVoucher();
                        $this->sendVoucher($mydata['email'], $mydata['name'], $mydata['path'], $this->code, 'update');
                        echo json_encode(['reschedule' => "Uspešno ste promenili datum vaše vožnje na: $d, a broj mesta na: $this->newPlaces"]);
                    } else
                        echo json_encode(['reschedule' => "Nije moguće promeniti datum vaše vožnje na: $d. Molimo kontaktirajte našu podršku!"]);
                } else {
                    echo json_encode([
                        'reschedule' => 'Nema dovoljno slobodnih mesta za izabrani datum.',
                        'mesta' => $this->newPlaces,
                        'dostupno' => $this->availability($this->newDate)
                    ], JSON_PRETTY_PRINT);
                }  
            } else {
                echo json_encode([
                    'reschedule' => 'Odabrani datum nije dostupan.'
                ], JSON_PRETTY_PRINT);
            }   
        }
        
    }
    // REFACTOR ASSIGN DRIVER

    public function assignDriverTo()
    {
        $now = time() + $this->driver->id;
        $generated = (string)$now . "KP";
        $new_code = substr($generated, -9);
        $dep_date = $this->selected[0]->date;
        
        $pathD = $this->generateDeparture($this->selected, $new_code, $dep_date);

        $dep_id = $this->departureCreate($this->driver->id, $this->tour_id, $new_code, $pathD['path'], $dep_date);
        $this->sendOrdersToDriver($this->driver->name, $new_code, $pathD['path'], $this->driver->email);

        foreach($this->selected as $ord) {
            $sql = "UPDATE order_items SET driver_id = :driver, dep_id = :dep_id WHERE id = :id";
            $stmt = $this->db->prepare($sql);

            $this->order_id = $ord->order_id;
            $stmt->bindParam(':driver', $this->driver->id, PDO::PARAM_INT);
            $stmt->bindParam(':dep_id', $dep_id, PDO::PARAM_INT);
            $stmt->bindParam(':id', $this->id, PDO::PARAM_INT);
            
            try {
                if($stmt->execute()) {
                    $updated = $this->reGenerateVoucher();
                    $this->sendVoucher($ord->user->email, $ord->user->name, $updated['path'], $updated['code'], 'resend');
                    $this->logger->logOrderChange($this->id, $_SESSION['user']['id'], 'Ažuriranje', 'Dodela vozača',
                                    null, $this->driver->id);

                    echo json_encode([
                        "success" => true,
                        "msg" => "Uspešno ste dodelili vožnje vozaču {$this->driver->name}"
                    ], JSON_PRETTY_PRINT);
                }
            } catch (PDOException $e) {            
                $this->logger->error("Failed to assign a driver to order_item with ID: $this->id", [
                    'user_id' => $_SESSION['user']['id'],
                    'error' => $e->getMessage(),
                    'file' => __FILE__,
                    'line' => __LINE__
                ]);
                http_response_code(500);
                echo json_encode([
                    'error'=> 'Došlo je do greške prilikom dodele vozača!'
                ]);
            } 
        }
        
    }

    // END
    public function departureCreate($driver_id, $tour_id, $code, $path, $date) 
    {
        $sql = "INSERT INTO departures SET driver_id = :driver_id, tour_id = :tour_id, code = :code, file_path = :path, date = :date";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':driver_id', $driver_id);
        $stmt->bindParam(':tour_id', $tour_id);
        //$stmt->bindParam(':orders', $orders);
        $stmt->bindParam(':code', $code);
        $stmt->bindParam(':path', $path);
        $stmt->bindParam(':date', $date);

        try {
            if($stmt->execute()) {
                //echo json_encode(['departure' => 'Uspešno ste kreirali polazak!'], JSON_PRETTY_PRINT);
                return $this->db->lastInsertId();
            } 
        }catch (PDOException $e) {
            $this->logger->error("Failed to make a new departure", [
                'user_id' => $_SESSION['user']['id'],
                'error' => $e->getMessage(),
                'file' => __FILE__,
                'line' => __LINE__
            ]);
        }
    }

    //------------------------------- FUNCTIONS OF DELETE METHOD --------------------------------//

    // DELETE order
    public function delete()
    {
        $this->getFromDB($this->id);
        $sql = "UPDATE order_items SET deleted = 1 WHERE id = :id";
        $stmt = $this->db->prepare($sql);

        $stmt->bindParam(":id", $this->id);
        try {
            if($stmt->execute()) {
                // Check if the departure has at leas one active item:
                $depSql = "UPDATE departures SET deleted = 1 WHERE id = :dep_id
                            AND NOT EXISTS ( SELECT 1 FROM order_items 
                            WHERE dep_id = departures.id AND deleted = 0)
                ";
                $stmt = $this->db->prepare($depSql);
                $stmt->bindParam(':dep_id', $this->dep_id);
                $stmt->execute();

                // Check if the order has at leas one active item:
                
                $ordSql = "UPDATE orders SET deleted = 1 WHERE id = :order_id
                            AND NOT EXISTS ( SELECT 1 FROM order_items 
                            WHERE order_id = orders.id AND deleted = 0)
                ";
                $stmt = $this->db->prepare($ordSql);
                $stmt->bindParam(':order_id', $this->order_id);
                $stmt->execute();

                $this->updateTotalPrice();

                $this->logger->logOrderChange($this->id, $_SESSION['user']['id'], 'Otkazivanje', 
                    'deleted', 0, 1);

                echo json_encode([
                    "success" => true,
                    "msg" => 'Uspešno ste obrisali vožnju!'
                ], JSON_PRETTY_PRINT);
            } else {
                http_response_code(422);
                echo json_encode(["error" => 'Trenutno nije moguće obrisati ovu rezervaciju!']);
            }
        } catch (PDOException $e) {
            $this->logger->error("Failed to delete order_item with ID: $this->id", [
                'user_id' => $_SESSION['user']['id'],
                'error' => $e->getMessage(),
                'file' => __FILE__,
                'line' => __LINE__
            ]);
            http_response_code(500);
            echo json_encode(['error'=> 'Došlo je do greške pri brisanju vožnje.']);
        }
    }

    // RESTORE order - only Admin

    public function restore() 
    {   
        $order = $this->getFromDB($this->id);

        if($order) {
            if($this->places <= $this->availability($this->date) && ($this->isUnlocked($this->date) || Validator::isSuper() || Validator::isAdmin())) {
                $sql = "UPDATE order_items SET deleted = 0 WHERE id = :id";
                $stmt = $this->db->prepare($sql);

                $stmt->bindParam(":id", $this->id);
                try {
                    if($stmt->execute()) {
                        $ordSql = "UPDATE orders SET deleted = 0 WHERE id = :order_id
                            AND EXISTS ( SELECT 1 FROM order_items 
                            WHERE order_id = orders.id AND deleted = 0)
                        ";
                        $stmt = $this->db->prepare($ordSql);
                        $stmt->bindParam(':order_id', $this->order_id);
                        $stmt->execute();

                        $this->updateTotalPrice();

                        $mydata = $this->reGenerateVoucher();
                        $this->sendVoucher($mydata['email'], $mydata['name'], $mydata['path'], $this->code, 'update');

                        $this->logger->logOrderChange($this->id, $_SESSION['user']['id'], 'Ponovno aktiviranje', 
                        'deleted', 1, 0);

                        echo json_encode([
                            'success' => true,
                            "msg" => 'Uspešno ste aktivirali rezervaciju!'
                        ], JSON_PRETTY_PRINT);
                    } else {
                        http_response_code(422);
                        echo json_encode(["error" => 'Trenutno nije moguće aktivirati ovu rezervaciju!']);
                    } 
                } catch(PDOException $e) {
                    $this->logger->error("Failed to delete order_item with ID: $this->id", [
                        'user_id' => $_SESSION['user']['id'],
                        'error' => $e->getMessage(),
                        'file' => __FILE__,
                        'line' => __LINE__
                    ]);
                    http_response_code(500);
                    echo json_encode(['error'=> 'Došlo je do greške pri reaktiviranju rezervacije.']);
                }
            } else {
                http_response_code(422);
                echo json_encode([
                    'success' => false,
                    "error" => 'Nema više slobodnih mesta za datum ove rezervacije, te je ne možemo aktivirati.'
                ]);
            }
        } else
            echo json_encode(["msg" => 'Ova rezervacija je izbrisana iz naše baze, pokušajte da kreirate novu.']);
    }
}


/**
 
    PUT
    
    "user": {
        "id": 10,
        "email": "pininfarina164@gmail.com"
    },
    "orders": {
        "update": {
            "order_id": 83,
            "tour_id": 1,
            "user_id": 10,
            "places": 2,
            "add_from": "Gavrila Principa 9",
            "add_to": "Primorska 18",
            "date": "2025-07-14",
            "price": null
        },
        "address": {
            "add_from": "Jevrejska 9",
            "add_to": "Mornarska 18"
        },
        "new_places": 3,
        "reschedule": null
    }

    DELETE

    "orders": {
        "delete": {
            "order_id": 83
        }
    }

    "orders": {
        "restore": {
            "order_id": 83
        }
    }

 */
?>