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

    // ----------- ESCAPE HTML for PDF:
    
    private function escapeForPDF(string $value): string 
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    // Validate and sanitize path
    private function sanitizeFilePath(string $code): string 
    {
        // Ukloni sve osim brojeva i KP oznake
        $cleanCode = preg_replace('/[^0-9A-Z]/', '', strtoupper($code));
        
        if (empty($cleanCode) || !preg_match('/^\d{7}KP$/', $cleanCode)) {
            throw new Exception('Neispravan kod rezervacije za PDF');
        }
        
        $basePath = realpath('src/assets/pdfs');
        
        if ($basePath === false) {
            throw new Exception('PDF direktorijum ne postoji');
        }
        
        $filePath = $basePath . DIRECTORY_SEPARATOR . $cleanCode . '.pdf';
        
        // Check the path
        if (strpos(realpath(dirname($filePath)), $basePath) !== 0) {
            throw new Exception('Path traversal pokušaj detektovan');
        }
        
        return $filePath;
    }

    public function generateVoucher(?int $total): array 
    {
        if (empty($this->id) || empty($this->user_id) || empty($this->items->create)) {
            throw new Exception('Nedostaju podaci za generisanje vaučera');
        }

        $this->user->id = $this->user_id;
        $this->tour->id = (int)$this->items->create[0]->tour_id;

        // Učitaj user i tour podatke
        $owner = $this->user->getByID();
        $tourObj = $this->tour->getByID();
        
        if (empty($owner) || empty($tourObj)) {
            throw new Exception('Korisnik ili tura ne postoje');
        }

        // Učitaj sve order_items
        $sql = "SELECT orders.id, orders.user_id, orders.code, orders.file_path, orders.total, 
                order_items.* 
                FROM orders
                INNER JOIN order_items ON orders.id = order_items.order_id
                WHERE orders.id = :id
                AND orders.deleted = 0
                ORDER BY order_items.date ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $this->id, PDO::PARAM_INT);

        try {
            $stmt->execute();
            $items = $stmt->fetchAll(PDO::FETCH_OBJ);
            
            if (empty($items)) {
                throw new Exception('Nema stavki za generisanje vaučera');
            }
            
        } catch (PDOException $e) {
            $this->logger->error('Failed to fetch items for voucher', [
                'order_id' => $this->id,
                'error' => $e->getMessage()
            ]);
            throw new Exception('Greška pri učitavanju stavki');
        }

        // Setup Dompdf
        $options = new Options();
        $options->setChroot(realpath("src/assets/img"));
        $pdf = new Dompdf($options);
        $pdf->setPaper("A4", "Portrait");

        // Load template
        $html = file_get_contents("src/template.html");
        
        if ($html === false) {
            throw new Exception('Template fajl nije pronađen');
        }

        // Escape svih vrednosti
        $code = $this->escapeForPDF($items[0]->code);
        $name = $this->escapeForPDF($owner[0]['name']);
        $fromCity = $this->escapeForPDF($tourObj[0]['from_city']);
        $toCity = $this->escapeForPDF($tourObj[0]['to_city']);

        // Header
        $html = str_replace('{{ order }}', $code, $html);
        $html = str_replace('{{ name }}', $name, $html);
        $html = str_replace('{{ city }}', $fromCity, $html);
        $html = str_replace('{{ city_to }}', $toCity, $html);

        // Prvi item (outbound)
        $item1 = $items[0];
        $html = str_replace('{{ view_first }}', 'visible', $html);
        $html = str_replace('{{ places }}', (string)$item1->places, $html);
        $html = str_replace('{{ address }}', $this->escapeForPDF($item1->add_from), $html);
        $html = str_replace('{{ address_to }}', $this->escapeForPDF($item1->add_to), $html);
        $html = str_replace('{{ date }}', Validator::formatDateForFront($item1->date), $html);
        $html = str_replace('{{ time }}', $this->escapeForPDF($tourObj[0]['time']), $html);
        $html = str_replace('{{ price }}', (string)$item1->price, $html);

        // Drugi item (inbound) - ako postoji
        if (count($items) > 1) {
            $item2 = $items[1];
            
            $html = str_replace('{{ view }}', 'visible', $html);
            $html = str_replace('{{ places2 }}', (string)$item2->places, $html);
            $html = str_replace('{{ address2 }}', $this->escapeForPDF($item2->add_from), $html);
            $html = str_replace('{{ city2 }}', $toCity, $html); // Obrnut redosled
            $html = str_replace('{{ address_to2 }}', $this->escapeForPDF($item2->add_to), $html);
            $html = str_replace('{{ city_to2 }}', $fromCity, $html); // Obrnut redosled
            $html = str_replace('{{ date2 }}', Validator::formatDateForFront($item2->date), $html);
            $html = str_replace('{{ time2 }}', $this->escapeForPDF($tourObj[0]['time']), $html);
            $html = str_replace('{{ price2 }}', (string)$item2->price, $html);
            $html = str_replace('{{ total }}', $total ? (string)$total : 'N/A', $html);
        } else {
            // Nema povratka
            $html = str_replace('{{ view }}', 'invisible', $html);
            $html = str_replace('{{ places2 }}', 'N/A', $html);
            $html = str_replace('{{ address2 }}', 'N/A', $html);
            $html = str_replace('{{ city2 }}', 'N/A', $html);
            $html = str_replace('{{ address_to2 }}', 'N/A', $html);
            $html = str_replace('{{ city_to2 }}', 'N/A', $html);
            $html = str_replace('{{ date2 }}', 'N/A', $html);
            $html = str_replace('{{ time2 }}', 'N/A', $html);
            $html = str_replace('{{ price2 }}', 'N/A', $html);
            $html = str_replace('{{ total }}', 'N/A', $html);
        }

        // Footer
        $html = str_replace('{{ year }}', date("Y"), $html);

        // Generiši PDF
        $pdf->loadHtml($html);
        $pdf->render();
        $pdf->addInfo("Title", "Kombitransfer - rezervacija: " . $code);

        // Sačuvaj PDF
        try {
            $filePath = $this->sanitizeFilePath($items[0]->code);
        } catch (Exception $e) {
            $this->logger->error('Invalid file path in generateVoucher', [
                'code' => $items[0]->code ?? 'unknown',
                'error' => $e->getMessage()
            ]);
            throw $e;
        }

        $output = $pdf->output();
        
        if (file_put_contents($filePath, $output) === false) {
            throw new Exception('Nije moguće sačuvati PDF fajl');
        }

        return [
            'email' => $owner[0]['email'],
            'name' => $owner[0]['name'],
            'path' => $filePath,
            'code' => $items[0]->code
        ];
    }

    /**
     * Regeneriši voucher (za update)
     * FIXED: Kompletan refaktor - bolja logika sa deleted items
     */
    public function reGenerateVoucher(): array 
    {
        if (empty($this->order_id) || empty($this->user_id) || empty($this->tour_id)) {
            throw new Exception('Nedostaju podaci za regenerisanje vaučera');
        }

        $this->user->id = $this->user_id;
        $this->tour->id = $this->tour_id;
        
        $owner = $this->user->getByID();          
        $tourObj = $this->tour->getByID();
        
        if (empty($owner) || empty($tourObj)) {
            throw new Exception('Korisnik ili tura ne postoje');
        }

        // Učitaj order sa svim stavkama i driverima
        $myOrder = $this->getDriverOfTour();
        
        if ($myOrder === null) {
            throw new Exception('Order ne postoji');
        }

        // Setup Dompdf
        $options = new Options();
        $options->setChroot(realpath("src/assets/img"));
        $pdf = new Dompdf($options);
        $pdf->setPaper("A4", "Portrait");

        // Odaberi template
        $html = file_get_contents("src/updated.html");
        
        if ($html === false) {
            throw new Exception('Updated template nije pronađen');
        }

        // Escape vrednosti
        $code = $this->escapeForPDF($myOrder['items'][0]['order']['code']);
        $name = $this->escapeForPDF($owner[0]['name']);
        $fromCity = $this->escapeForPDF($tourObj[0]['from_city']);
        $toCity = $this->escapeForPDF($tourObj[0]['to_city']);

        // Header
        $html = str_replace('{{ order }}', $code, $html);
        $html = str_replace('{{ name }}', $name, $html);
        $html = str_replace('{{ city }}', $fromCity, $html);
        $html = str_replace('{{ city_to }}', $toCity, $html);

        // ==================== PRVI ITEM (OUTBOUND) ====================
        
        $hasFirstItem = isset($myOrder['items'][0]) 
            && (int)$myOrder['items'][0]['order']['deleted'] === 0;
        
        if ($hasFirstItem) {
            $item1 = $myOrder['items'][0]['order'];
            $driver1 = $myOrder['items'][0]['driver'];
            
            $html = str_replace('{{ view_first }}', 'visible', $html);
            $html = str_replace('{{ places }}', (string)$item1['places'], $html);
            $html = str_replace('{{ address }}', $this->escapeForPDF($item1['pickup']), $html);
            $html = str_replace('{{ address_to }}', $this->escapeForPDF($item1['dropoff']), $html);
            $html = str_replace('{{ date }}', Validator::formatDateForFront($item1['date']), $html);
            $html = str_replace('{{ time }}', $this->escapeForPDF($tourObj[0]['time']), $html);
            $html = str_replace('{{ price }}', (string)$item1['price'], $html);
            
            // Driver info
            if ($driver1 && !empty($driver1['dr_name'])) {
                $driverFirstName = explode(" ", $driver1['dr_name'])[0];
                
                $html = str_replace('{{ driver_view }}', 'visible', $html);
                $html = str_replace('{{ driver }}', $this->escapeForPDF($driverFirstName), $html);
                $html = str_replace('{{ drphone }}', $this->escapeForPDF($driver1['dr_phone']), $html);
                $html = str_replace('{{ drmail }}', $this->escapeForPDF($driver1['dr_email']), $html);
            } else {
                $html = str_replace('{{ driver_view }}', 'invisible', $html);
                $html = str_replace('{{ driver }}', 'N/A', $html);
                $html = str_replace('{{ drphone }}', 'N/A', $html);
                $html = str_replace('{{ drmail }}', 'N/A', $html);
            }
        } else {
            // Prvi item je obrisan - sakrij
            $html = str_replace('{{ view_first }}', 'invisible', $html);
            $html = str_replace('{{ places }}', 'N/A', $html);
            $html = str_replace('{{ address }}', 'N/A', $html);
            $html = str_replace('{{ address_to }}', 'N/A', $html);
            $html = str_replace('{{ date }}', 'N/A', $html);
            $html = str_replace('{{ time }}', 'N/A', $html);
            $html = str_replace('{{ price }}', 'N/A', $html);
            $html = str_replace('{{ driver_view }}', 'invisible', $html);
            $html = str_replace('{{ driver }}', 'N/A', $html);
            $html = str_replace('{{ drphone }}', 'N/A', $html);
            $html = str_replace('{{ drmail }}', 'N/A', $html);
        }

        // ==================== DRUGI ITEM (INBOUND) ====================
        
        $hasSecondItem = isset($myOrder['items'][1]) 
            && (int)$myOrder['items'][1]['order']['deleted'] === 0;
        
        if ($hasSecondItem) {
            $item2 = $myOrder['items'][1]['order'];
            $driver2 = $myOrder['items'][1]['driver'];
            
            $html = str_replace('{{ view }}', 'visible', $html);
            $html = str_replace('{{ places2 }}', (string)$item2['places'], $html);
            $html = str_replace('{{ address2 }}', $this->escapeForPDF($item2['pickup']), $html);
            $html = str_replace('{{ city2 }}', $toCity, $html);
            $html = str_replace('{{ address_to2 }}', $this->escapeForPDF($item2['dropoff']), $html);
            $html = str_replace('{{ city_to2 }}', $fromCity, $html);
            $html = str_replace('{{ date2 }}', Validator::formatDateForFront($item2['date']), $html);
            $html = str_replace('{{ time2 }}', $this->escapeForPDF($tourObj[0]['time']), $html);
            $html = str_replace('{{ price2 }}', (string)$item2['price'], $html);
            
            // Total price (samo ako ima oba item-a)
            $totalPrice = $hasFirstItem 
                ? (int)$myOrder['items'][0]['order']['total'] 
                : (int)$item2['price'];
            
            $html = str_replace('{{ price3 }}', (string)$totalPrice, $html);
            
            // Driver info
            if ($driver2 && !empty($driver2['dr_name'])) {
                $driverFirstName = explode(" ", $driver2['dr_name'])[0];
                
                $html = str_replace('{{ driver_view2 }}', 'visible', $html);
                $html = str_replace('{{ driver2 }}', $this->escapeForPDF($driverFirstName), $html);
                $html = str_replace('{{ drphone2 }}', $this->escapeForPDF($driver2['dr_phone']), $html);
                $html = str_replace('{{ drmail2 }}', $this->escapeForPDF($driver2['dr_email']), $html);
            } else {
                $html = str_replace('{{ driver_view2 }}', 'invisible', $html);
                $html = str_replace('{{ driver2 }}', 'N/A', $html);
                $html = str_replace('{{ drphone2 }}', 'N/A', $html);
                $html = str_replace('{{ drmail2 }}', 'N/A', $html);
            }
        } else {
            // Drugi item je obrisan ili ne postoji
            $html = str_replace('{{ view }}', 'invisible', $html);
            $html = str_replace('{{ places2 }}', 'N/A', $html);
            $html = str_replace('{{ address2 }}', 'N/A', $html);
            $html = str_replace('{{ city2 }}', 'N/A', $html);
            $html = str_replace('{{ address_to2 }}', 'N/A', $html);
            $html = str_replace('{{ city_to2 }}', 'N/A', $html);
            $html = str_replace('{{ date2 }}', 'N/A', $html);
            $html = str_replace('{{ time2 }}', 'N/A', $html);
            $html = str_replace('{{ price2 }}', 'N/A', $html);
            $html = str_replace('{{ price3 }}', 'N/A', $html);
            $html = str_replace('{{ driver_view2 }}', 'invisible', $html);
            $html = str_replace('{{ driver2 }}', 'N/A', $html);
            $html = str_replace('{{ drphone2 }}', 'N/A', $html);
            $html = str_replace('{{ drmail2 }}', 'N/A', $html);
        }

        // Footer
        $html = str_replace('{{ year }}', date("Y"), $html);

        // Generiši PDF
        $pdf->loadHtml($html);
        $pdf->render();
        $pdf->addInfo("Title", "Kombitransfer - rezervacija: " . $code);

        // Sačuvaj PDF
        $filePath = $myOrder['items'][0]['order']['voucher'];
        
        // Validacija path-a
        $basePath = realpath('src/assets/pdfs');
        
        if ($basePath === false || strpos(realpath(dirname($filePath)), $basePath) !== 0) {
            $this->logger->error('Invalid voucher path in reGenerateVoucher', [
                'path' => $filePath,
                'order_id' => $this->order_id
            ]);
            
            // Fallback - generiši novi path
            try {
                $filePath = $this->sanitizeFilePath($code);
            } catch (Exception $e) {
                throw new Exception('Neispravan path za voucher');
            }
        }

        $output = $pdf->output();
        
        if (file_put_contents($filePath, $output) === false) {
            throw new Exception('Nije moguće sačuvati PDF fajl');
        }

        // Pripremi povratne podatke
        $result = [
            'email' => $owner[0]['email'],
            'name' => $owner[0]['name'],
            'path' => $filePath,
            'code' => $code
        ];

        // Dodaj driver info ako postoji
        if ($hasFirstItem && !empty($myOrder['items'][0]['driver']['dr_name'])) {
            $result['driver'] = explode(" ", $myOrder['items'][0]['driver']['dr_name'])[0];
            $result['driver_phone'] = $myOrder['items'][0]['driver']['dr_phone'];
            $result['driver_email'] = $myOrder['items'][0]['driver']['dr_email'];
        }

        if ($hasSecondItem && !empty($myOrder['items'][1]['driver']['dr_name'])) {
            $result['driver2'] = explode(" ", $myOrder['items'][1]['driver']['dr_name'])[0];
            $result['driver_phone2'] = $myOrder['items'][1]['driver']['dr_phone'];
            $result['driver_email2'] = $myOrder['items'][1]['driver']['dr_email'];
        }

        return $result;
    }

    public function sendVoucher(string $email, string $name, string $path, string $code, string $goal): void
    {
        // Validacija email-a
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Neispravan email format');
        }

        // Validacija fajla
        if (!file_exists($path)) {
            throw new Exception('PDF fajl ne postoji: ' . basename($path));
        }

        // Escape name za sigurnost
        $safeName = $this->escapeForPDF($name);
        $safeCode = $this->escapeForPDF($code);

        // Template na osnovu goal-a
        $template = match($goal) {
            'create' => "<p>Poštovani/a {$safeName},</p>
                <br>
                <p>Uspešno ste rezervisali vašu vožnju!</p>
                <br>
                <p>Broj vaše rezervacije je: <b>{$safeCode}</b></p>
                <br>
                <p>U prilogu Vam šaljemo potvrdu rezervacije.</p>
                <br><br>
                <p>Srdačan pozdrav od Kombitransfer tima!</p>",
            
            'update' => "<p>Poštovani/a {$safeName},</p>
                <br>
                <p>Uspešno ste izmenili vašu vožnju!</p>
                <br>
                <p>Broj vaše rezervacije je: <b>{$safeCode}</b></p>
                <br>
                <p>U prilogu Vam šaljemo ažuriranu potvrdu rezervacije.</p>
                <br><br>
                <p>Srdačan pozdrav od Kombitransfer tima!</p>",
            
            default => "<p>Poštovani/a {$safeName},</p>
                <br>
                <p>Vaša rezervacija broj <b>{$safeCode}</b> je ažurirana.</p>
                <br>
                <p>U prilogu Vam šaljemo ažuriranu potvrdu rezervacije.</p>
                <br><br>
                <p>Srdačan pozdrav od Kombitransfer tima!</p>"
        };

        $mail = new PHPMailer(true);
        
        try {
            // SMTP Config
            $mail->isSMTP();
            $mail->SMTPAuth = true;
            $mail->Host = "smtp.gmail.com";
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port = 465;
            $mail->Username = $_ENV["SMTP_USER"];
            $mail->Password = $_ENV["SMTP_PASS"];

            // From/To (escape ime!)
            $mail->setFrom("noreply-kombiprevoz@gmail.com", "Kombi Transfer");
            
            // ✅ FIXED: Escape name da spreči email injection
            $mail->addAddress($email, mb_substr($safeName, 0, 50)); // Limit 50 karaktera

            // Email settings
            $mail->CharSet = 'UTF-8';
            $mail->Encoding = 'base64';
            $mail->isHTML(true);
            $mail->Subject = "Potvrda Rezervacije - " . $safeCode;
            $mail->setLanguage('sr');
            $mail->Body = $template;
            
            // Attachment
            $mail->addAttachment($path, "Kombitransfer-" . $safeCode . ".pdf");

            $mail->send();
            
        } catch (Exception $e) {
            $this->logger->error('Failed to send email', [
                'email' => $email,
                'code' => $code,
                'error' => $e->getMessage(),
                'mailer_error' => $mail->ErrorInfo ?? 'N/A'
            ]);
            
            throw new Exception('Greška pri slanju emaila: ' . $mail->ErrorInfo);
        }
    }

    // ------ PDF of all passangers and reservations to DRIVER
    private function generateDeparture(array $orders, string $code, string $dateTime): array
    {
        if (empty($orders)) {
            throw new Exception('Nema rezervacija za generisanje PDF-a');
        }

        $options = new Options();
        $options->setChroot(realpath("src/assets/img"));
        $pdf = new Dompdf($options);
        $pdf->setPaper("A4", "Portrait");

        // Formatuj datum
        $formattedDateTime = date('d.m.Y H:i', strtotime($dateTime));

        // Generiši HTML za svaku rezervaciju
        $reservationsHtml = '';
        
        foreach ($orders as $pax) {
            $reservationsHtml .= Validator::mailerDriverTemplate(
                $this->escapeForPDF($pax->code),
                $this->escapeForPDF($pax->user->name),
                (int)$pax->places,
                $this->escapeForPDF($pax->pickup),
                $this->escapeForPDF($pax->from_city),
                $this->escapeForPDF($pax->dropoff),
                $this->escapeForPDF($pax->to_city),
                $pax->date,
                $this->escapeForPDF($pax->pickuptime),
                (int)$pax->price,
                $this->escapeForPDF($pax->user->phone)
            );
        }

        // Učitaj driver template
        $html = file_get_contents("src/driver.html");
        
        if ($html === false) {
            throw new Exception('Driver template nije pronađen');
        }

        $safeCode = $this->escapeForPDF($code);

        $html = str_replace('{{ code }}', $safeCode, $html);
        $html = str_replace('{{ dateTime }}', htmlspecialchars($formattedDateTime, ENT_QUOTES, 'UTF-8'), $html);
        $html = str_replace('{{ main }}', $reservationsHtml, $html);
        $html = str_replace('{{ year }}', date("Y"), $html);

        $pdf->loadHtml($html);
        $pdf->render();
        $pdf->addInfo("Title", "Kombitransfer - vožnja: " . $safeCode);

        // Sačuvaj PDF
        try {
            $filePath = $this->sanitizeFilePath($code);
        } catch (Exception $e) {
            $this->logger->error('Invalid departure PDF path', [
                'code' => $code,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }

        $output = $pdf->output();
        
        if (file_put_contents($filePath, $output) === false) {
            throw new Exception('Nije moguće sačuvati departure PDF');
        }

        return ['path' => $filePath];
    }

    // ------ Email about reservations to DRIVER
    private function sendOrdersToDriver(
        string $name, 
        string $code, 
        string $path, 
        string $email
    ): void {
        // Validacija
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Neispravan email vozača');
        }

        if (!file_exists($path)) {
            throw new Exception('Departure PDF ne postoji');
        }

        $safeName = $this->escapeForPDF($name);
        $safeCode = $this->escapeForPDF($code);

        $template = "<p>Poštovani/a {$safeName},</p>
            <br>
            <p>Sistem Vam je dodelio vožnju broj: <b>{$safeCode}</b></p>
            <br>
            <p>U prilogu Vam šaljemo spisak svih rezervacija, sa imenima i podacima putnika.</p>
            <br>
            <p><b>Važno:</b></p>
            <ul>
                <li>Molimo napravite svoju rutu i poredak preuzimanja putnika</li>
                <li>Dan pre polaska, javite putnicima okvirno vreme dolaska</li>
                <li>Budite dostupni na telefonu za bilo kakva pitanja</li>
            </ul>
            <br>
            <p>Hvala i srećan put!</p>
            <br><br>
            <p>Srdačan pozdrav,<br>Kombitransfer Tim</p>";

        $mail = new PHPMailer(true);
        
        try {
            $mail->isSMTP();
            $mail->SMTPAuth = true;
            $mail->Host = "smtp.gmail.com";
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port = 465;
            $mail->Username = $_ENV["SMTP_USER"];
            $mail->Password = $_ENV["SMTP_PASS"];

            $mail->setFrom("noreply-kombiprevoz@gmail.com", "Kombi Transfer");
            $mail->addAddress($email, mb_substr($safeName, 0, 50));

            $mail->CharSet = 'UTF-8';
            $mail->Encoding = 'base64';
            $mail->isHTML(true);
            $mail->Subject = "Dodela Vožnje - " . $safeCode;
            $mail->setLanguage('sr');
            $mail->Body = $template;
            
            $mail->addAttachment($path, "Kombitransfer-voznja-" . $safeCode . ".pdf");

            $mail->send();
            
        } catch (Exception $e) {
            $this->logger->error('Failed to send email to driver', [
                'email' => $email,
                'code' => $code,
                'error' => $e->getMessage()
            ]);
            
            throw new Exception('Greška pri slanju emaila vozaču');
        }
    }

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

    public function getAllByFilter(?string $email): void 
    {
        // Ako ima code, pretraži samo po code-u
        if (!empty($this->code)) {
            $this->getByCode();
            return;
        }

        // Build WHERE clause dinamički
        $whereClauses = ['1=1'];
        $params = [];

        if (!empty($this->date)) {
            $whereClauses[] = 'order_items.date = :date';
            $params[':date'] = $this->date;
        }

        if (!empty($this->from_city)) {
            $whereClauses[] = 'tours.from_city = :from_city';
            $params[':from_city'] = $this->from_city;
        }

        if (!empty($this->to_city)) {
            $whereClauses[] = 'tours.to_city = :to_city';
            $params[':to_city'] = $this->to_city;
        }

        if (!empty($this->tour_id)) {
            $whereClauses[] = 'order_items.tour_id = :tour_id';
            $params[':tour_id'] = $this->tour_id;
        }

        if (!empty($email)) {
            $whereClauses[] = 'users.email = :email';
            $params[':email'] = $email;
        }

        $whereClause = implode(' AND ', $whereClauses);

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
                WHERE {$whereClause}
                ORDER BY order_items.date ASC";

        $stmt = $this->db->prepare($sql);

        // Bind parameters
        foreach ($params as $key => $value) {
            if ($key === ':tour_id') {
                $stmt->bindValue($key, $value, PDO::PARAM_INT);
            } else {
                $stmt->bindValue($key, $value, PDO::PARAM_STR);
            }
        }

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
            $this->logger->error('Failed in getAllByFilter', [
                'params' => $params,
                'error' => $e->getMessage(),
                'file' => __FILE__,
                'line' => __LINE__
            ]);

            http_response_code(500);
            echo json_encode([
                'error' => 'Greška pri filtriranju rezervacija'
            ], JSON_UNESCAPED_UNICODE);
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

    public function getByTour(): void 
    {
        if (empty($this->tour_id)) {
            http_response_code(400);
            echo json_encode([
                'error' => 'Tour ID je obavezan'
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        $sql = "SELECT orders.id, orders.tour_id, orders.user_id, order_items.places, 
                tours.from_city, order_items.add_from as pickup, 
                tours.to_city, order_items.add_to as dropoff,
                order_items.date, tours.time as pickuptime, tours.duration,
                orders.total as price, orders.code, orders.file_path as voucher, 
                users.name as user, users.email, users.phone
                FROM order_items
                INNER JOIN orders ON order_items.order_id = orders.id
                INNER JOIN tours ON order_items.tour_id = tours.id
                INNER JOIN users ON orders.user_id = users.id
                WHERE order_items.tour_id = :tour_id 
                AND orders.deleted = 0
                AND order_items.deleted = 0
                ORDER BY order_items.date ASC, pickuptime ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':tour_id', $this->tour_id, PDO::PARAM_INT);

        try {
            $stmt->execute();
            $orders = $stmt->fetchAll(PDO::FETCH_OBJ);

            echo json_encode([
                'orders' => $orders,
                'has_orders' => !empty($orders)
            ], JSON_UNESCAPED_UNICODE);

        } catch (PDOException $e) {
            $this->logger->error('Failed in getByTour', [
                'tour_id' => $this->tour_id,
                'error' => $e->getMessage(),
                'file' => __FILE__,
                'line' => __LINE__
            ]);
        }
    }

    public function getByTourAndDate(): void 
    {
        if (empty($this->tour_id) || empty($this->date)) {
            http_response_code(400);
            echo json_encode([
                'error' => 'Tour ID i datum su obavezni'
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        $sql = "SELECT orders.id, orders.tour_id, orders.user_id, order_items.places, 
                tours.from_city, order_items.add_from as pickup, 
                tours.to_city, order_items.add_to as dropoff,
                order_items.date, tours.time as pickuptime, tours.duration,
                orders.total as price, orders.code, orders.file_path as voucher, 
                users.name as user, users.email, users.phone
                FROM order_items
                INNER JOIN orders ON order_items.order_id = orders.id
                INNER JOIN tours ON order_items.tour_id = tours.id
                INNER JOIN users ON orders.user_id = users.id
                WHERE order_items.tour_id = :tour_id 
                AND order_items.date = :date 
                AND orders.deleted = 0
                AND order_items.deleted = 0
                ORDER BY pickuptime ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':tour_id', $this->tour_id, PDO::PARAM_INT);
        $stmt->bindParam(':date', $this->date, PDO::PARAM_STR);

        try {
            $stmt->execute();
            $orders = $stmt->fetchAll(PDO::FETCH_OBJ);
            
            // Dohvati dostupne vozače
            $drivers = $this->user->getAvailableDrivers($this->date);

            echo json_encode([
                'orders' => $orders,
                'drivers' => $drivers,
                'has_orders' => !empty($orders)
            ], JSON_UNESCAPED_UNICODE);

        } catch (PDOException $e) {
            $this->logger->error('Failed in getByTourAndDate', [
                'tour_id' => $this->tour_id,
                'date' => $this->date,
                'error' => $e->getMessage()
            ]);

            http_response_code(500);
            echo json_encode([
                'error' => 'Greška pri pretrazi rezervacija'
            ], JSON_UNESCAPED_UNICODE);
        }
    }

    public function getDriverOfTour(): ?array
    {
        if (empty($this->order_id)) {
            return null;
        }

        $sql = "SELECT orders.id, order_items.id as item_id, order_items.tour_id, 
                order_items.driver_id, order_items.places, order_items.deleted, 
                tours.from_city, order_items.add_from as pickup, 
                tours.to_city, order_items.add_to as dropoff,
                order_items.date, tours.time as pickuptime, tours.duration,
                order_items.price, orders.total, orders.code, orders.file_path as voucher
                FROM order_items 
                INNER JOIN orders ON order_items.order_id = orders.id
                INNER JOIN tours ON order_items.tour_id = tours.id
                WHERE orders.id = :id
                ORDER BY order_items.date ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $this->order_id, PDO::PARAM_INT);
        
        try {
            $stmt->execute();
            $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($orders)) {
                return null;
            }
        
            $items = [];
        
            foreach ($orders as $order) {
                $driver = null;

                if ($order['driver_id']) {
                    $driverSql = "SELECT users.name as dr_name, users.email as dr_email, 
                                users.phone as dr_phone 
                                FROM users
                                WHERE id = :driver_id AND deleted = 0";
                
                    $stmtDriver = $this->db->prepare($driverSql);
                    $stmtDriver->bindParam(':driver_id', $order['driver_id'], PDO::PARAM_INT);
                    $stmtDriver->execute();
                    $driver = $stmtDriver->fetch(PDO::FETCH_ASSOC);
                }
                
                $items[] = [
                    'order' => $order,
                    'driver' => $driver
                ];
            }
            
            return ['items' => $items];
            
        } catch (PDOException $e) {
            $this->logger->error('Failed in getDriverOfTour', [
                'order_id' => $this->order_id,
                'error' => $e->getMessage(),
                'file' => __FILE__,
                'line' => __LINE__
            ]);
            return null;
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
                throw new PDOException('Neispravan format datuma');
            }

            // LOCK ROW - provera dostupnosti sa FOR UPDATE
            $lockSql = "SELECT COALESCE(SUM(oi.places), 0) as occupied, t.seats 
                        FROM tours t
                        LEFT JOIN order_items oi ON oi.tour_id = t.id 
                            AND oi.date = :date 
                            AND oi.deleted = 0
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
                throw new PDOException("Tura ne postoji ili je obrisana");
            }

            $availableSeats = (int)$availability->seats - (int)$availability->occupied;

            // Provera dostupnosti
            if ($this->places > $availableSeats) {
                throw new PDOException("Nema dovoljno mesta. Dostupno: {$availableSeats}");
            }

            // Provera departure day
            if (!$this->isDeparture($this->date)) {
                throw new PDOException("Nema polazaka za datum: {$this->date}");
            }

            // Provera unlock (25h minimum)
            if (!$this->isUnlocked($this->date)) {
                throw new PDOException("Rezervacija mora biti najmanje 25h unapred");
            }

            // total price
            $this->price = self::totalPrice($this->db, $this->tour_id, $this->places);
            
            if ($this->price === null) {
                throw new PDOException("Greška pri računanju cene");
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
            // Nastavljamo - order je kreiran, samo voucher failed
        }

        // SUCCESS response
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'msg' => "Uspešno ste rezervisali vožnju. Vaš broj rezervacije je: {$new_code}",
            'code' => $new_code
        ], JSON_UNESCAPED_UNICODE);

    } catch (PDOException $e) {
        // ROLLBACK 
        if ($this->db->inTransaction()) {
            $this->db->rollBack();
        }

        // SECURITY: Ne outputuj detalje SQL greške
        $this->logger->error('Order creation failed', [
            'user_id' => $this->user_id,
            'error' => $e->getMessage(),
            'code' => $e->getCode(),
            'file' => __FILE__,
            'line' => __LINE__
        ]);

        // Generička poruka za korisnika
        http_response_code(422);
        echo json_encode([
            'success' => false,
            'error' => 'Rezervacija nije uspela. Molimo pokušajte ponovo ili kontaktirajte podršku.'
        ], JSON_UNESCAPED_UNICODE);

    } catch (Exception $e) {
        // ROLLBACK za druge greške
        if ($this->db->inTransaction()) {
            $this->db->rollBack();
        }

        $this->logger->error('Order creation failed (non-PDO)', [
            'user_id' => $this->user_id,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
            'file' => __FILE__,
            'line' => __LINE__
        ]);

        http_response_code(422);
        echo json_encode([
            'success' => false,
            'error' => 'Rezervacija nije uspela. Molimo pokušajte ponovo.'
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

    public function assignDriverTo(): void
    {
        if (empty($this->selected) || empty($this->driver) || empty($this->driver->id)) {
            http_response_code(400);
            echo json_encode([
                'error' => 'Nedostaju podaci za dodelu vozača'
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        // Validacija driver ID-a
        $driverId = filter_var($this->driver->id, FILTER_VALIDATE_INT);
        
        if ($driverId === false) {
            http_response_code(400);
            echo json_encode([
                'error' => 'Neispravan ID vozača'
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        // Provera da li driver postoji
        $driverCheckSql = "SELECT id, name, email FROM users 
                        WHERE id = :id AND status = 'Driver' AND deleted = 0";
        $driverStmt = $this->db->prepare($driverCheckSql);
        $driverStmt->bindParam(':id', $driverId, PDO::PARAM_INT);
        
        try {
            $driverStmt->execute();
            $driverData = $driverStmt->fetch(PDO::FETCH_OBJ);
            
            if (!$driverData) {
                http_response_code(404);
                echo json_encode([
                    'error' => 'Vozač nije pronađen'
                ], JSON_UNESCAPED_UNICODE);
                return;
            }
        } catch (PDOException $e) {
            $this->logger->error('Failed to validate driver', [
                'driver_id' => $driverId,
                'error' => $e->getMessage()
            ]);
            
            http_response_code(500);
            echo json_encode([
                'error' => 'Greška pri validaciji vozača'
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        // Generiši departure code
        $now = time() + $driverId;
        $generated = (string)$now . "KP";
        $dep_code = substr($generated, -9);
        $dep_date = $this->selected[0]->date;

        try {
            $this->db->beginTransaction();

            // 1. Generiši PDF za vozača
            $pathD = $this->generateDeparture($this->selected, $dep_code, $dep_date);

            // 2. Kreiraj departure
            $dep_id = $this->departureCreate(
                $driverId, 
                $this->tour_id, 
                $dep_code, 
                $pathD['path'], 
                $dep_date
            );

            // 3. Assign driver svim order_items
            $assignSql = "UPDATE order_items 
                        SET driver_id = :driver_id, dep_id = :dep_id 
                        WHERE id = :id";
            $assignStmt = $this->db->prepare($assignSql);

            foreach ($this->selected as $ord) {
                $this->id = (int)$ord->order_item_id;
                $this->order_id = (int)$ord->order_id;

                $assignStmt->bindParam(':driver_id', $driverId, PDO::PARAM_INT);
                $assignStmt->bindParam(':dep_id', $dep_id, PDO::PARAM_INT);
                $assignStmt->bindParam(':id', $this->id, PDO::PARAM_INT);
                $assignStmt->execute();

                // Regeneriši voucher sa driver info
                try {
                    $updated = $this->reGenerateVoucher();
                    $this->sendVoucher(
                        $ord->user->email, 
                        $ord->user->name, 
                        $updated['path'], 
                        $updated['code'], 
                        'update'
                    );
                } catch (Exception $e) {
                    $this->logger->error('Failed to send voucher in assignDriverTo', [
                        'order_item_id' => $this->id,
                        'error' => $e->getMessage()
                    ]);
                }

                // Log
                $this->logger->logOrderChange(
                    $this->id, 
                    $_SESSION['user']['id'], 
                    'Ažuriranje', 
                    'Dodela vozača',
                    'null', 
                    (string)$driverId
                );
            }

            $this->db->commit();

            // 4. Pošalji PDF vozaču
            try {
                $this->sendOrdersToDriver(
                    $driverData->name, 
                    $dep_code, 
                    $pathD['path'], 
                    $driverData->email
                );
            } catch (Exception $e) {
                $this->logger->error('Failed to send PDF to driver', [
                    'driver_id' => $driverId,
                    'dep_code' => $dep_code,
                    'error' => $e->getMessage()
                ]);
            }

            http_response_code(200);
            echo json_encode([
                'success' => true,
                'msg' => "Uspešno ste dodelili vožnje vozaču {$driverData->name}"
            ], JSON_UNESCAPED_UNICODE);

        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            $this->logger->error('Failed to assign driver', [
                'driver_id' => $driverId,
                'selected_count' => count($this->selected),
                'error' => $e->getMessage(),
                'file' => __FILE__,
                'line' => __LINE__
            ]);

            http_response_code(500);
            echo json_encode([
                'error' => 'Greška pri dodeli vozača'
            ], JSON_UNESCAPED_UNICODE);
        }
    }


    // END
    private function departureCreate(
        int $driver_id, 
        int $tour_id, 
        string $code, 
        string $path, 
        string $date
    ): int 
    {
        $sql = "INSERT INTO departures 
                (driver_id, tour_id, code, file_path, date) 
                VALUES (:driver_id, :tour_id, :code, :path, :date)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':driver_id', $driver_id, PDO::PARAM_INT);
        $stmt->bindParam(':tour_id', $tour_id, PDO::PARAM_INT);
        $stmt->bindParam(':code', $code, PDO::PARAM_STR);
        $stmt->bindParam(':path', $path, PDO::PARAM_STR);
        $stmt->bindParam(':date', $date, PDO::PARAM_STR);

        try {
            $stmt->execute();
            return (int)$this->db->lastInsertId();
            
        } catch (PDOException $e) {
            $this->logger->error('Failed to create departure', [
                'driver_id' => $driver_id,
                'tour_id' => $tour_id,
                'code' => $code,
                'date' => $date,
                'error' => $e->getMessage()
            ]);
            
            throw new Exception('Greška pri kreiranju polaska');
        }
    }

    //------------------------------- FUNCTIONS OF DELETE METHOD --------------------------------//

    // DELETE order
    public function delete(): void
    {
        if (empty($this->id)) {
            http_response_code(400);
            echo json_encode([
                'error' => 'ID stavke je obavezan'
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        // Učitaj podatke
        $this->getFromDB($this->id);
        
        if (!$this->order_id) {
            http_response_code(404);
            echo json_encode([
                'error' => 'Stavka nije pronađena'
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        try {
            $this->db->beginTransaction();

            // 1. Obriši order_item
            $sql = "UPDATE order_items SET deleted = 1 WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':id', $this->id, PDO::PARAM_INT);
            $stmt->execute();

            // 2. Proveri da li departure ima još aktivnih items
            if ($this->dep_id) {
                $depCheckSql = "SELECT COUNT(*) as active_count 
                                FROM order_items 
                                WHERE dep_id = :dep_id 
                                AND deleted = 0";
                
                $depStmt = $this->db->prepare($depCheckSql);
                $depStmt->bindParam(':dep_id', $this->dep_id, PDO::PARAM_INT);
                $depStmt->execute();
                $depResult = $depStmt->fetch(PDO::FETCH_OBJ);

                // Ako nema više aktivnih items, obriši departure
                if ($depResult && (int)$depResult->active_count === 0) {
                    $depDeleteSql = "UPDATE departures SET deleted = 1 WHERE id = :dep_id";
                    $depDeleteStmt = $this->db->prepare($depDeleteSql);
                    $depDeleteStmt->bindParam(':dep_id', $this->dep_id, PDO::PARAM_INT);
                    $depDeleteStmt->execute();
                }
            }

            // 3. Proveri da li order ima još aktivnih items
            $ordCheckSql = "SELECT COUNT(*) as active_count 
                            FROM order_items 
                            WHERE order_id = :order_id 
                            AND deleted = 0";
            
            $ordStmt = $this->db->prepare($ordCheckSql);
            $ordStmt->bindParam(':order_id', $this->order_id, PDO::PARAM_INT);
            $ordStmt->execute();
            $ordResult = $ordStmt->fetch(PDO::FETCH_OBJ);

            // Ako nema više aktivnih items, obriši order
            if ($ordResult && (int)$ordResult->active_count === 0) {
                $ordDeleteSql = "UPDATE orders SET deleted = 1 WHERE id = :order_id";
                $ordDeleteStmt = $this->db->prepare($ordDeleteSql);
                $ordDeleteStmt->bindParam(':order_id', $this->order_id, PDO::PARAM_INT);
                $ordDeleteStmt->execute();
            } else {
                // Ažuriraj total price
                $this->updateTotalPrice();
            }

            $this->db->commit();

            // Log
            $this->logger->logOrderChange(
                $this->id, 
                $_SESSION['user']['id'], 
                'Otkazivanje', 
                'deleted', 
                '0', 
                '1'
            );

            http_response_code(200);
            echo json_encode([
                'success' => true,
                'msg' => 'Uspešno ste obrisali vožnju'
            ], JSON_UNESCAPED_UNICODE);

        } catch (PDOException $e) {
            $this->db->rollBack();
            
            $this->logger->error('Failed to delete order_item', [
                'order_item_id' => $this->id,
                'user_id' => $_SESSION['user']['id'],
                'error' => $e->getMessage(),
                'file' => __FILE__,
                'line' => __LINE__
            ]);

            http_response_code(500);
            echo json_encode([
                'error' => 'Greška pri brisanju vožnje'
            ], JSON_UNESCAPED_UNICODE);
        }
    }

    // RESTORE deleted order
    public function restore(): void 
    {   
        if (empty($this->id)) {
            http_response_code(400);
            echo json_encode([
                'error' => 'ID stavke je obavezan'
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        $order = $this->getFromDB($this->id);

        if (!$order) {
            http_response_code(404);
            echo json_encode([
                'error' => 'Stavka nije pronađena ili je trajno obrisana'
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        // Provera dostupnosti
        $available = $this->availability($this->date);
        
        if ($this->places > $available) {
            http_response_code(422);
            echo json_encode([
                'error' => "Nema dovoljno mesta za reaktiviranje. Dostupno: {$available}"
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        // Provera datuma (mora biti u budućnosti ili admin override)
        if (!$this->isUnlocked($this->date) 
            && !Validator::isSuper() 
            && !Validator::isAdmin()) {
            http_response_code(422);
            echo json_encode([
                'error' => 'Datum vožnje je prošao, nije moguće reaktivirati'
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        try {
            $this->db->beginTransaction();

            // 1. Reaktiviraj order_item
            $sql = "UPDATE order_items SET deleted = 0 WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':id', $this->id, PDO::PARAM_INT);
            $stmt->execute();

            // 2. Reaktiviraj order ako je bio obrisan
            $ordSql = "UPDATE orders SET deleted = 0 WHERE id = :order_id";
            $ordStmt = $this->db->prepare($ordSql);
            $ordStmt->bindParam(':order_id', $this->order_id, PDO::PARAM_INT);
            $ordStmt->execute();

            // 3. Ažuriraj total price
            $this->updateTotalPrice();

            $this->db->commit();

            // Regeneriši voucher i pošalji email
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
                $this->logger->error('Failed to send voucher after restore', [
                    'order_item_id' => $this->id,
                    'error' => $e->getMessage()
                ]);
            }

            // Log
            $this->logger->logOrderChange(
                $this->id, 
                $_SESSION['user']['id'], 
                'Ponovno aktiviranje', 
                'deleted', 
                '1', 
                '0'
            );

            http_response_code(200);
            echo json_encode([
                'success' => true,
                'msg' => 'Uspešno ste aktivirali rezervaciju'
            ], JSON_UNESCAPED_UNICODE);

        } catch (PDOException $e) {
            $this->db->rollBack();
            
            $this->logger->error('Failed to restore order_item', [
                'order_item_id' => $this->id,
                'user_id' => $_SESSION['user']['id'],
                'error' => $e->getMessage(),
                'file' => __FILE__,
                'line' => __LINE__
            ]);

            http_response_code(500);
            echo json_encode([
                'error' => 'Greška pri reaktiviranju rezervacije'
            ], JSON_UNESCAPED_UNICODE);
        }
    }
}

?>