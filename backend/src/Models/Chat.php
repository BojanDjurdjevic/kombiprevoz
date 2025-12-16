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

            if(!filter_var($this->customer_email, FILTER_VALIDATE_EMAIL)) {
                return $this->error('Nevažeća email adresa!', 400);
            }

            $ticketNumber = $this->generateTicketNumber();

            $sql = "INSERT INTO chat_tickets SET ticket_number = :ticket_number, customer_name = :customer_name,
                    customer_email = :customer_email, customer_phone = :customer_phone,
                    reservation_number = :reservation_number, status = :status"
            ;
            $stmt = $this->db->prepare($sql);

            $reservationNum = (isset($this->reservation_number) && mb_strtoupper($this->reservation_number) !== 'NO'
                                && !empty($this->reservation_number)) ? $this->reservation_number : null;
            $my_status = "open"; 

            $stmt->bindParam(":ticket_number", $ticketNumber);
            $stmt->bindParam(":reservation_number", $reservationNum, PDO::PARAM_INT);
            $stmt->bindParam(":customer_name", $this->customer_name);
            $stmt->bindParam(":customer_email", $this->customer_email);
            $stmt->bindParam(":customer_phone", $this->customer_phone);
            $stmt->bindParam(":status", $my_status, PDO::PARAM_STR);

            $stmt->execute();

            $ticketID = $this->db->lastInsertId();

            if(!empty($this->initial_message)) {
                $this->addMessage($ticketID, 'customer', null, $this->initial_message);
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

    // POLL MSGS

    public function pollMessages() 
    {
        $ticketID = $this->ticket_id ?? 0;
        $lastMessageID = $this->last_message_id ?? 0;
        $timeout = 5;
        $start = time();

        try {
            while (time() - $start < $timeout) {
                $sql = "SELECT m.*, u.name as admin_name 
                    FROM chat_messages m
                    LEFT JOIN users u ON m.sender_id = u.id
                    WHERE m.ticket_id = :ticket_id AND m.id > :m_id
                    ORDER BY m.created_at ASC";
                
                $stmt = $this->db->prepare($sql);
                $stmt->bindParam(":ticket_id", $ticketID, PDO::PARAM_INT);
                $stmt->bindParam(":m_id", $lastMessageID, PDO::PARAM_INT);
                $stmt->execute();

                $messages = $stmt->fetchAll(PDO::FETCH_OBJ);

                if (!empty($messages)) {
                    return $this->success([
                        'messages' => $messages,
                        'has_more' => false
                    ]);
                }

                $ticket = $this->getTicket($ticketID);
                if ($ticket && $ticket->status === 'closed') {
                    return $this->success([
                        'messages' => [],
                        'ticket_closed' => true
                    ]);
                }

                $typing = $this->getTypingStatus($ticketID);

                usleep(300000); 
            }

            return $this->success([
                'messages' => [],
                'timeout' => true
            ]);
        } catch (PDOException $e) {
            Logger::error("Poll messages failed: " . $e->getMessage());
            return $this->error('Greška pri preuzimanju poruka', 500);
        }
    }

    // POLL TICKETS

    public function pollNewTickets() 
    {
        $lastChecked = $this->last_checked ?? date('Y-m-d H:i:s', strtotime('-1 hour'));
        $timeout = 5;
        $start = time();

        try {
            while (time() - $start < $timeout) {
                $sql = "SELECT t.*, 
                        u.name as assigned_admin_name,
                        (SELECT COUNT(*) FROM chat_messages 
                         WHERE ticket_id = t.id AND sender_type = 'customer' AND is_read = FALSE) as unread_count,
                        (SELECT message FROM chat_messages 
                         WHERE ticket_id = t.id ORDER BY created_at DESC LIMIT 1) as last_message,
                        (SELECT created_at FROM chat_messages 
                         WHERE ticket_id = t.id ORDER BY created_at DESC LIMIT 1) as last_message_time
                        FROM chat_tickets t
                        LEFT JOIN users u ON t.assigned_to = u.id
                        WHERE t.created_at > :created_at OR t.updated_at > :updated_at
                        ORDER BY t.updated_at DESC";
                
                $stmt = $this->db->prepare($sql);
                $stmt->bindParam(":created_at", $lastChecked);
                $stmt->bindParam(":updated_at", $lastChecked);
                $stmt->execute();
                $tickets = $stmt->fetchAll(PDO::FETCH_OBJ);

                if (!empty($tickets)) {
                    return $this->success([
                        'tickets' => $tickets,
                        'timestamp' => date('Y-m-d H:i:s')
                    ]);
                }

                usleep(300000);
            }

            return $this->success([
                'tickets' => [],
                'timeout' => true,
                'timestamp' => date('Y-m-d H:i:s')
            ]);

        } catch (PDOException $e) {
            Logger::error("Poll tickets failed: " . $e->getMessage());
            return $this->error('Greška pri preuzimanju tiketa', 500);
        }
    }

    // ---------------- GET ALL TICKETS ------------------//

    public function getAllTickets()
    {
        try {
            $where = ['1=1'];
            $params = [];

            if (!empty($this->ticket_status)) {
                $where[] = "t.status = ?";
                $params[] = $this->ticket_status;
            }

            if (!empty($this->assigned_to)) {
                $where[] = "t.assigned_to = ?";
                $params[] = $this->assigned_to;
            }

            $sql = "SELECT t.*, 
                    u.name as assigned_admin_name,
                    (SELECT COUNT(*) FROM chat_messages 
                     WHERE ticket_id = t.id AND sender_type = 'customer' AND is_read = FALSE) as unread_count,
                    (SELECT message FROM chat_messages 
                     WHERE ticket_id = t.id ORDER BY created_at DESC LIMIT 1) as last_message,
                    (SELECT created_at FROM chat_messages 
                     WHERE ticket_id = t.id ORDER BY created_at DESC LIMIT 1) as last_message_time
                FROM chat_tickets t
                LEFT JOIN users u ON t.assigned_to = u.id
                WHERE " . implode(' AND ', $where) . "
                ORDER BY t.updated_at DESC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);

            return $this->success([
                'tickets' => $stmt->fetchAll(PDO::FETCH_OBJ)
            ]);

        } catch (PDOException $e) {
            Logger::error("Get all tickets failed: " . $e->getMessage());
            return $this->error('Greška pri preuzimanju tiketa', 500);
        }
    }

    // ---------------- GET TICKETS MSGS ------------------//

    public function getTicketMessages(): array 
    {
        try {
            $ticketId = $this->ticket_id ?? 0;
            $limit = $this->limit ?? 100;
            $offset = $this->offset ?? 0;

            $stmt = $this->db->prepare(
                "SELECT m.*, u.name as admin_name, u.status as admin_role
                FROM chat_messages m
                LEFT JOIN users u ON m.sender_id = u.id
                WHERE m.ticket_id = :ticket_id
                ORDER BY m.created_at ASC
                LIMIT :limit OFFSET :offset"
            );

            $stmt->bindParam(":ticket_id", $ticketId, PDO::PARAM_INT);
            $stmt->bindParam(":limit", $limit, PDO::PARAM_INT);
            $stmt->bindParam(":offset", $offset, PDO::PARAM_INT);

            $stmt->execute();

            return $this->success([
                'messages' => $stmt->fetchAll(PDO::FETCH_OBJ)
            ]);

        } catch (PDOException $e) {
            Logger::error("Get ticket messages failed: " . $e->getMessage());
            return $this->error('Greška pri preuzimanju poruka', 500);
        }
    }

    // ---------------- SET MSGS as IS_READ ------------------//
    public function markAsRead(): array 
    {
        try {
            $ticketId = $this->ticket_id ?? 0;
            $readerType = $this->reader_type ?? null;

            if (empty($ticketId) || empty($readerType)) {
                return $this->error('Nedostaju parametri', 400);
            }

            // Mark as read from the other side
            $senderType = ($readerType === 'admin') ? 'customer' : 'admin';

            $stmt = $this->db->prepare(
                "UPDATE chat_messages 
                SET is_read = TRUE 
                WHERE ticket_id = ? AND sender_type = ? AND is_read = FALSE"
            );
            $stmt->execute([$ticketId, $senderType]);

            return $this->success(['marked' => $stmt->rowCount()]);

        } catch (PDOException $e) {
            Logger::error("Mark as read failed: " . $e->getMessage());
            return $this->error('Greška pri označavanju poruka', 500);
        }
    }

    // ---------------- ADMIN ASSIGN TICKETS ------------------//
    public function assignTicket(): array 
    {
        try {
            $ticketId = $this->ticket_id ?? 0;
            $adminId = $this->admin_id ?? null;

            if (empty($ticketId) || empty($adminId)) {
                return $this->error('Nedostaju parametri', 400);
            }

            $ticket = $this->getTicket($ticketId);
            if (!$ticket) {
                return $this->error('Tiket ne postoji', 404);
            }

            $stmt = $this->db->prepare(
                "UPDATE chat_tickets 
                SET assigned_to = ?, status = 'in_progress', updated_at = NOW() 
                WHERE id = ?"
            );
            $stmt->execute([$adminId, $ticketId]);

            Logger::audit("Chat ticket #{$ticket->ticket_number} assigned to admin ID: $adminId", $adminId);

            return $this->success(['assigned' => true]);

        } catch (PDOException $e) {
            Logger::error("Assign ticket failed: " . $e->getMessage());
            return $this->error('Greška pri preuzimanju tiketa', 500);
        }
    }

    // ---------------- CLOSE THE TICKET ------------------//
    public function closeTicket(): array 
    {
        try {
            $ticketId = $this->ticket_id ?? 0;
            $closedBy = $this->closed_by ?? null;

            if (empty($ticketId)) {
                return $this->error('Nedostaje ID tiketa', 400);
            }

            $ticket = $this->getTicket($ticketId);
            if (!$ticket) {
                return $this->error('Tiket ne postoji', 404);
            }

            $stmt = $this->db->prepare(
                "UPDATE chat_tickets 
                SET status = 'closed', updated_at = NOW() 
                WHERE id = ?"
            );
            $stmt->execute([$ticketId]);

            Logger::audit("Chat ticket #{$ticket->ticket_number} closed", $closedBy);

            return $this->success(['closed' => true]);

        } catch (PDOException $e) {
            Logger::error("Close ticket failed: " . $e->getMessage());
            return $this->error('Greška pri zatvaranju tiketa', 500);
        }
    }

    // ---------------- SET TYPING INDICATE ------------------//
    public function updateTyping(): array 
    {
        try {
            $ticketId = $this->ticket_id ?? 0;
            $userType = $this->user_type ?? null;
            $userId = $this->user_id ?? null;

            if (empty($ticketId) || empty($userType)) {
                return $this->error('Nedostaju parametri', 400);
            }

            $stmt = $this->db->prepare(
                "INSERT INTO chat_typing (ticket_id, user_type, user_id, updated_at) 
                VALUES (?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE user_type = ?, user_id = ?, updated_at = NOW()"
            );
            $stmt->execute([$ticketId, $userType, $userId, $userType, $userId]);

            return $this->success(['typing_updated' => true]);

        } catch (PDOException $e) {
            Logger::error("Update typing failed: " . $e->getMessage());
            return $this->error('Greška pri ažuriranju typing statusa', 500);
        }
    }

    // ---------------- REAAD TYPING IND ------------------//
    public function getTyping(): array 
    {
        try {
            $ticketId = $this->ticket_id ?? 0;

            $stmt = $this->db->prepare(
                "SELECT * FROM chat_typing 
                WHERE ticket_id = ? AND updated_at > DATE_SUB(NOW(), INTERVAL 3 SECOND)"
            );
            $stmt->execute([$ticketId]);
            $typing = $stmt->fetch(PDO::FETCH_OBJ);

            return $this->success(['typing' => $typing]);

        } catch (PDOException $e) {
            Logger::error("Get typing failed: " . $e->getMessage());
            return $this->error('Greška pri preuzimanju typing statusa', 500);
        }
    }
}

?>