<?php
declare(strict_types=1);

namespace Controllers;

use Guards\DemoGuard;
use Helpers\Logger;
use Models\Order;
use PDO;
use PDOException;
use Rules\Validator;

if (!defined('APP_ACCESS')) {
    http_response_code(403);
    die('Direct access forbidden');
}

/**
 * OrderController
 * Svaka metoda = jedna akcija
 */
class OrderController {
    private PDO $db;
    private object $data;
    private Order $order;
    private string $sid;

    public function __construct(PDO $db, object $data, string $sid)
    {
        $this->db = $db;
        $this->data = $data;
        $this->order = new Order($this->db);
        $this->sid = $sid;
    }

    // ======================== HELPER METHODS ========================

    private function canAccessOrder(int $userId): bool
    {
        if (Validator::isAdmin() || Validator::isSuper()) {
            return true;
        }

        if (isset($_SESSION['user']['id'])) {
            return $_SESSION['user']['id'] === $userId;
        }

        return false;
    }

    // ======================== GET METHODS ========================

    /**
     * GET orders by user ID
     * Akcija: { "orders": { "user_id": N } }
     */
    public function getOrdersByUser(): void
    {
        if (empty($this->data->orders->user_id)) {
            http_response_code(400);
            echo json_encode([
                'error' => 'ID korisnika je obavezan'
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        $userId = filter_var($this->data->orders->user_id, FILTER_VALIDATE_INT);
        
        if ($userId === false) {
            http_response_code(400);
            echo json_encode(['error' => 'Neispravan ID korisnika'], JSON_UNESCAPED_UNICODE);
            return;
        }

        if (!$this->canAccessOrder($userId)) {
            http_response_code(403);
            echo json_encode([
                'error' => 'Nemate dozvolu da vidite ove rezervacije'
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        $this->order->user_id = $userId;
        $this->order->getByUser();
    }

    /**
     * GET all orders (24h ili 48h filter) - admin/driver only
     * Akcija: { "orders": { "adminOrders": { "all": true, "in24": bool, "in48": bool } } }
     */
    public function getAllOrders(): void
    {
        $in24 = $this->data->orders->adminOrders->in24 ?? false;
        $in48 = $this->data->orders->adminOrders->in48 ?? false;

        $this->order->getAll($in24, $in48);
    }

    /**
     * GET orders by filters - admin only
     * Akcija: { "orders": { "filters": { "departure": "...", "code": "...", ... } } }
     */
    public function getOrdersByFilters(): void
    {
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
    }

    /**
     * GET orders by date range - admin/driver only
     * Akcija: { "orders": { "from_date": "...", "to_date": "..." } }
     */
    public function getOrdersByDateRange(): void
    {
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
    }

    /**
     * GET orders by tour and date - admin/driver only
     * Akcija: { "orders": { "tour_id": N, "date": "..." } }
     */
    public function getOrdersByTourAndDate(): void
    {
        $tourId = filter_var($this->data->orders->tour_id, FILTER_VALIDATE_INT);
        
        if ($tourId === false) {
            http_response_code(400);
            echo json_encode(['error' => 'Neispravan ID ture'], JSON_UNESCAPED_UNICODE);
            return;
        }

        $this->order->tour_id = $tourId;
        $this->order->date = $this->data->orders->date;
        $this->order->getByTourAndDate();
    }

    /**
     * GET orders by tour only - admin/driver only
     * Akcija: { "orders": { "tour_id": N } }
     */
    public function getOrdersByTour(): void
    {
        $tourId = filter_var($this->data->orders->tour_id, FILTER_VALIDATE_INT);
        
        if ($tourId === false) {
            http_response_code(400);
            echo json_encode(['error' => 'Neispravan ID ture'], JSON_UNESCAPED_UNICODE);
            return;
        }

        $this->order->tour_id = $tourId;
        $this->order->getByTour();
    }

    // ======================== POST METHOD ========================

    /**
     * CREATE new order
     * Akcija: { "orders": { "create": [...] } }
     */
    public function createOrder(): void
    {
        // Demo guard
        if (Validator::isDemo()) {
            echo json_encode([
                'success' => true,
                'msg' => 'Demo rezervacija je kreirana lokalno i privremeno.',
                'fake' => true
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        // Validacija
        if (!is_array($this->data->orders->create) || count($this->data->orders->create) === 0) {
            http_response_code(400);
            echo json_encode([
                'error' => 'Morate uneti bar jednu stavku rezervacije'
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

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

        if (!$this->canAccessOrder($userId)) {
            http_response_code(403);
            echo json_encode([
                'error' => 'Ne možete kreirati rezervaciju za drugog korisnika'
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        // Validacija svake stavke
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

            if (filter_var($item->tour_id, FILTER_VALIDATE_INT) === false) {
                http_response_code(400);
                echo json_encode([
                    'error' => "Neispravan ID ture u stavci #" . ($index + 1)
                ], JSON_UNESCAPED_UNICODE);
                return;
            }

            if (filter_var($item->places, FILTER_VALIDATE_INT) === false || $item->places < 1) {
                http_response_code(400);
                echo json_encode([
                    'error' => "Neispravan broj mesta u stavci #" . ($index + 1)
                ], JSON_UNESCAPED_UNICODE);
                return;
            }

            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $item->date)) {
                http_response_code(400);
                echo json_encode([
                    'error' => "Neispravan format datuma u stavci #" . ($index + 1)
                ], JSON_UNESCAPED_UNICODE);
                return;
            }
        }

        $this->order->items = $this->data->orders;
        $this->order->create();
    }

    // ======================== PUT METHODS ========================

    /**
     * UPDATE order (address, places, reschedule)
     * Akcija: { "orders": { "update": { "id": N, ... } } }
     */
    public function updateOrder(): void
    {
        if (Validator::isDemo()) {
            echo json_encode([
                'success' => true,
                'msg' => 'Demo update izvršen lokalno.',
                'fake' => true
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        if (!isset($this->data->orders->update->id) || empty($this->data->orders->update->id)) {
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

        if (!$this->order->order_id) {
            http_response_code(404);
            echo json_encode([
                'error' => 'Rezervacija nije pronađena'
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        if (!($this->order->findUserId() || Validator::isAdmin() || Validator::isSuper())) {
            http_response_code(403);
            echo json_encode([
                'error' => 'Niste autorizovani da izmenite ovu rezervaciju'
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

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
            /*
            Logger::error('outDate type: ' . gettype($this->data->orders->reschedule->outDate));
            Logger::error('outDate value: ' . var_export($this->data->orders->reschedule->outDate, true));
            Logger::error('inDate type: ' . gettype($this->data->orders->reschedule->inDate));
            Logger::error('inDate value: ' . var_export($this->data->orders->reschedule->inDate, true));
            */
            $outDate = $this->data->orders->reschedule->outDate ?? null;
            $inDate = $this->data->orders->reschedule->inDate ?? null;
            /*
            Logger::error('$outDate after assignment: ' . var_export($outDate, true));
            Logger::error('$inDate after assignment: ' . var_export($inDate, true));
            */
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

        $this->db->beginTransaction();
        
        try {
            $results = [];
            $errors = [];

            if ($hasAddressUpdate) {
                $addressResult = $this->order->updateAddress();
                
                if (isset($addressResult['error'])) {
                    $errors[] = $addressResult['error'];
                } else if (isset($addressResult['success'])) {
                    $results[] = $addressResult['msg'];
                }
            }

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

            if ($hasReschedule) {
                $rescheduleResult = $this->order->reschedule();
                
                if (isset($rescheduleResult['error'])) {
                    $errors[] = $rescheduleResult['error'];
                } else if (isset($rescheduleResult['success'])) {
                    $results[] = $rescheduleResult['msg'];
                }
            }

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
            
            Logger::error("Order update failed", [
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
    }

    /**
     * ASSIGN driver to orders - admin only
     * Akcija: { "orders": { "selected": [...], "driver": {...}, "tour_id": N } }
     */
    public function assignDriver(): void
    {
        DemoGuard::denyIfDemo('Demo admin ne može dodeljivati vozače');

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
    }

    /**
     * REGENERATE voucher - admin only
     * Akcija: { "orders": { "voucher": { "item_id": N } } }
     */
    public function regenerateVoucher(): void
    {
        DemoGuard::denyIfDemo('Demo admin ne može regenerisati vaučere');

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
            Logger::error('Failed to regenerate voucher', [
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
    }

    // ======================== DELETE METHODS ========================

    /**
     * DELETE order (soft delete)
     * Akcija: { "orders": { "delete": { "item_id": N } } }
     */
    public function deleteOrder(): void
    {
        if (Validator::isDemo()) {
            echo json_encode([
                'success' => true,
                'msg' => 'Demo brisanje izvršeno lokalno.',
                'fake' => true
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

        if (!($this->order->findUserId() || Validator::isAdmin() || Validator::isSuper())) {
            http_response_code(403);
            echo json_encode([
                'error' => 'Niste autorizovani da obrišete ovu rezervaciju'
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        if (!($this->order->checkDeadline() || Validator::isAdmin() || Validator::isSuper())) {
            http_response_code(422);
            echo json_encode([
                'error' => 'Nije moguće obrisati rezervaciju jer je do polaska ostalo manje od 48 sati'
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        $this->order->delete();
    }

    /**
     * RESTORE order - admin only
     * Akcija: { "orders": { "restore": { "item_id": N } } }
     */
    public function restoreOrder(): void
    {
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
    }
}

?>