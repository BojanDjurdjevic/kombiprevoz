<?php

namespace Models;

use Helpers\Logger;
use PDO;
use PDOException;

if (!defined('APP_ACCESS')) {
    http_response_code(403);
    die('Direct access forbidden');
}

class Chat {
    public $ticket_id;
    public $ticket_number;
    public $ticket_status;
    public $customer_name;
    public $customer_email;
    public $reservation_number;
    public $customer_phone;
    public $initial_message;
    public $last_message_id;
    public $last_checked;


    public $sender_id;
    public $sender_type;
    public $message;


    public $assigned_to;
    public $limit;
    public $offset;
    public $reader_type;
    public $admin_id;
    public $user_id;
    public $user_type;
    public $closed_by;



    private $db;
    public $logger;

    

    public function __construct($db) {
        $this->db = $db;
        $this->logger = new Logger($this->db);
    }

    // CHAT HELPERS:

    private function generateTicketNumber(): string 
    {
        return 'TKT-' . strtoupper(substr(uniqid(), -8));
    }

    private function addMessage(int $ticketId, string $senderType, ?int $senderId, string $message): int 
    {
        $stmt = $this->db->prepare(
            "INSERT INTO chat_messages (ticket_id, sender_type, sender_id, message, created_at) 
            VALUES (?, ?, ?, ?, NOW())"
        );
        $stmt->execute([$ticketId, $senderType, $senderId, $message]);
        return (int)$this->db->lastInsertId();
    }

    private function getTicket(int $ticketId): ?object 
    {
        $stmt = $this->db->prepare("SELECT * FROM chat_tickets WHERE id = ?");
        $stmt->execute([$ticketId]);
        $result = $stmt->fetch(PDO::FETCH_OBJ);
        return $result ?: null;
    }

    private function updateTicketStatus(int $ticketId, string $status, ?int $userId): void 
    {
        $stmt = $this->db->prepare(
            "UPDATE chat_tickets SET status = ?, updated_at = NOW() WHERE id = ?"
        );
        $stmt->execute([$status, $ticketId]);

        $this->logger->audit("Chat ticket ID $ticketId status changed to: $status", $userId);
    }

    private function getTypingStatus(int $ticketId): ?object 
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM chat_typing 
            WHERE ticket_id = ? AND updated_at > DATE_SUB(NOW(), INTERVAL 3 SECOND)"
        );
        $stmt->execute([$ticketId]);
        $result = $stmt->fetch(PDO::FETCH_OBJ);
        return $result ?: null;
    }

    private function success(array $data): array 
    {
        return ['success' => true, 'data' => $data, 'code' => 200];
    }

    private function error(string $message, int $code): array 
    {
        return ['success' => false, 'error' => $message, 'code' => $code];
    }

    // CREATE NEW TICKET

    public function createTicket() 
    {
        try {
            if(empty($this->customer_name) || empty($this->customer_email)) {
                return $this->error('Ime i email korisnika su obavezni za početak konverzacije!', 400);
            }

            if(!filter_var($this->customer_name, FILTER_VALIDATE_EMAIL)) {
                return $this->error('Nevažeća email adresa!', 400);
            }

            $ticketNumber = $this->generateTicketNumber();

            $sql = "INSERT INTO chat_tickets SET ticket_number = :ticket_number, customer_name = :customer_name,
                    customer_email = :customer_email, customer_phone = :customer_phone
                    reservation_number = :reservation_number, status = :status"
            ;
            $stmt = $this->db->prepare($sql);

            $reservationNum = (isset($this->reservation_nummber) && mb_strtoupper($this->reservation_number) !== 'NO'
                                && !empty($this->reservation_number)) ? $this->reservation_number : null;

            $stmt->bindParam(":ticket_number", $ticketNumber, PDO::PARAM_INT);
            $stmt->bindParam(":reservation_number", $reservationNum, PDO::PARAM_INT);
            $stmt->bindParam(":customer_name", $this->customer_name);
            $stmt->bindParam(":customer_email", $this->customer_email);
            $stmt->bindParam(":customer_phone", $this->customer_phone);
            $stmt->bindParam(":status", 'open');

            $stmt->execute();

            $ticketID = $this->db->lastInsertId();

            if(!empty($this->initial_message)) {
                $this->addMessage($this->ticket_id, 'customer', null, $this->initial_message);
            }

            $this->logger->info("Chat ticket created: $ticketNumber (ID: $ticketID) by {$this->customer_email}");
            $this->logger->audit("Chat ticket $ticketNumber created", null);

            return $this->success([
                "ticket_id"=> (int)$ticketID,
                "ticket_number"=> $ticketNumber,
            ]);

        } catch (PDOException $e) {
            $this->logger->error( "Chat ticket create failed: " . $e->getMessage(), [
                'emali' => $this->customer_email ?? 'unknown'
            ]);
            return $this->error('Greška pri kreiranju tiketa!', 500);
        }
    }

    // SEND MESSAGE

    public function sendMessage() 
    {
        try {
            if(empty($this->ticket_id) || empty($this->sender_type) || empty($this->message)) {
                return $this->error('Nedostaju obavezni parametri',400);
            }

            $ticket = $this->getTicket($this->ticket_id);
            if(!$ticket) {
                return $this->error('Tiket ne postoji ili nije otvoren',404);
            }
            if($ticket->status == 'closed') {
                $this->error('Tiket je zatvoren', 403);
            }

            $messageID = $this->addMessage($this->ticket_id, $this->sender_type, $this->sender_id, $this->message);

            $sql = "UPDATE chat_tickets
                    SET last_message_at = NOW(), updated_at = NOW()
                    WHERE id = :id"
            ;
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(":id", $this->ticket_id);
            $stmt->execute();

            if($this->sender_type == "admin" && $ticket->status == "open") {
                $this->updateTicketStatus($this->ticket_id, 'in_progress', $this->sender_id);
            }

            Logger::info("Message sent in ticket #{$ticket->ticket_number} by $this->sender_type");

            return $this->success([
                "message_id"=> (int)$messageID,
                "created_at" => date("Y-m-d H:i:s")
            ]);
        } catch (PDOException $e) {
            $this->logger->error("Sending message failed: " . $e->getMessage());
            return $this->error("Greška pri slanju poruke!",500);
        }
    }

    public function pollMessages() 
    {
        
    }
}

?>