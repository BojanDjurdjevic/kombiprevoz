<?php
declare(strict_types=1);

namespace Models;

use Helpers\Logger;
use PDO;
use PDOException;
use PHPMailer\PHPMailer\PHPMailer;
use Rules\Validator;

if (!defined('APP_ACCESS')) {
    http_response_code(403);
    die('Direct access forbidden');
}

class Chat {
    // Ticket properties
    public ?int $ticket_id = null;
    public ?string $ticket_number = null;
    public ?string $ticket_status = null;
    public ?string $customer_name = null;
    public ?string $customer_email = null;
    public ?string $reservation_number = null;
    public ?string $customer_phone = null;
    public ?string $initial_message = null;
    public ?int $last_message_id = null;
    public ?string $last_checked = null;

    // Message properties
    public ?int $sender_id = null;
    public ?string $sender_type = null;
    public ?string $message = null;

    // Admin properties
    public ?int $assigned_to = null;
    public ?int $limit = null;
    public ?int $offset = null;
    public ?string $reader_type = null;
    public ?int $admin_id = null;
    public ?int $user_id = null;
    public ?string $user_type = null;
    public ?int $closed_by = null;

    private PDO $db;

    public function __construct(PDO $db) 
    {
        $this->db = $db;
    }

    // ======================== HELPER METHODS ========================

    /**
     * Generisanje unique broja tiketa
     */
    private function generateTicketNumber(): string 
    {
        return 'TKT-' . strtoupper(substr(uniqid(), -8));
    }

    /**
     * Dodavanje poruke u tiket
     */
    private function addMessage(int $ticketId, string $senderType, ?int $senderId, string $message): int 
    {
        $stmt = $this->db->prepare(
            "INSERT INTO chat_messages (ticket_id, sender_type, sender_id, message, created_at) 
            VALUES (?, ?, ?, ?, NOW())"
        );
        $stmt->execute([$ticketId, $senderType, $senderId, $message]);
        
        return (int) $this->db->lastInsertId();
    }

    /**
     * Dobijanje tiketa po ID-u
     */
    private function getTicket(int $ticketId): ?object 
    {
        $stmt = $this->db->prepare("SELECT * FROM chat_tickets WHERE id = ? LIMIT 1");
        $stmt->execute([$ticketId]);
        $result = $stmt->fetch(PDO::FETCH_OBJ);
        
        return $result ?: null;
    }

    /**
     * Ažuriranje statusa tiketa
     */
    private function updateTicketStatus(int $ticketId, string $status, ?int $userId): void 
    {
        $stmt = $this->db->prepare(
            "UPDATE chat_tickets 
            SET status = ?, updated_at = NOW() 
            WHERE id = ?"
        );
        $stmt->execute([$status, $ticketId]);

        Logger::audit("Chat ticket ID $ticketId status changed to: $status", $userId);
    }

    /**
     * Dobijanje typing statusa
     */
    private function getTypingStatus(int $ticketId): ?object 
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM chat_typing 
            WHERE ticket_id = ? 
            AND updated_at > DATE_SUB(NOW(), INTERVAL 3 SECOND)
            LIMIT 1"
        );
        $stmt->execute([$ticketId]);
        $result = $stmt->fetch(PDO::FETCH_OBJ);
        
        return $result ?: null;
    }

    /**
     * Success response helper
     */
    private function success(array $data): array 
    {
        return [
            'success' => true, 
            'data' => $data, 
            'code' => 200
        ];
    }

    /**
     * Error response helper
     */
    private function error(string $message, int $code): array 
    {
        return [
            'success' => false, 
            'error' => $message, 
            'code' => $code
        ];
    }

    /**
     * Slanje email notifikacije admin-u
     */
    private function sendAdminNotification(string $ticketNumber, string $customerName): void
    {
        $html = "
            <p>Poštovani/a {{ name }}</p>
            <br>
            <p>Imate novi Chat na čekanju, broj tiketa: {{ code }}</p>
            <br>
            <p>Molimo Vas da uđete na admin panel na link:</p>
            <p>" . ($_ENV['APP_URL'] ?? 'http://localhost:5173') . "/admin</p>
            <br>
            <p>Srdačan pozdrav od KombiPrevoz tima!</p>
        ";

        try {
            $this->sendEmail($html, $ticketNumber, $_ENV['ADMIN_EMAIL'] ?? 'admin@example.com', 'Admin');
        } catch (\Exception $e) {
            Logger::error('Failed to send admin notification email', [
                'ticket_number' => $ticketNumber,
                'error' => $e->getMessage(),
                'file' => __FILE__,
                'line' => __LINE__
            ]);
        }
    }

    /**
     * PHPMailer setup i slanje email-a
     */
    private function sendEmail(string $html, string $code, string $email, string $name): void
    {
        $template = Validator::mailerTemplate($html, $code, $name);
        
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->SMTPAuth = true;
        $mail->Host = "smtp.gmail.com";
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = 465;
        $mail->Username = $_ENV["SMTP_USER"];
        $mail->Password = $_ENV["SMTP_PASS"];
        $mail->setFrom("noreply-kombiprevoz@gmail.com", "KombiPrevoz");
        $mail->addAddress($email, $name);
        $mail->CharSet = 'UTF-8';
        $mail->Encoding = 'base64';
        $mail->isHTML(true);
        $mail->Subject = 'Novi chat tiket na čekanju!';
        $mail->setLanguage('sr');
        $mail->Body = $template;

        try {
            $mail->send();
        } catch (\Exception $e) {
            Logger::error('PHPMailer failed to send email', [
                'email' => $email,
                'error' => $e->getMessage(),
                'file' => __FILE__,
                'line' => __LINE__
            ]);
        }
    }

    // ======================== PUBLIC METHODS ========================

    /**
     * Kreiranje novog tiketa
     */
    public function createTicket(): array 
    {
        try {
            if (empty($this->customer_name) || empty($this->customer_email)) {
                return $this->error('Ime i email korisnika su obavezni za početak konverzacije!', 400);
            }

            if (!filter_var($this->customer_email, FILTER_VALIDATE_EMAIL)) {
                return $this->error('Nevažeća email adresa!', 400);
            }

            $ticketNumber = $this->generateTicketNumber();

            $sql = "INSERT INTO chat_tickets SET 
                    ticket_number = :ticket_number, 
                    customer_name = :customer_name,
                    customer_email = :customer_email, 
                    customer_phone = :customer_phone,
                    reservation_number = :reservation_number, 
                    status = :status,
                    created_at = NOW()";
            
            $stmt = $this->db->prepare($sql);

            $reservationNum = (isset($this->reservation_number) 
                && mb_strtoupper($this->reservation_number) !== 'NO'
                && !empty($this->reservation_number)) 
                ? $this->reservation_number 
                : null;
            
            $status = "open";

            $stmt->bindParam(":ticket_number", $ticketNumber, PDO::PARAM_STR);
            $stmt->bindParam(":reservation_number", $reservationNum, PDO::PARAM_STR);
            $stmt->bindParam(":customer_name", $this->customer_name, PDO::PARAM_STR);
            $stmt->bindParam(":customer_email", $this->customer_email, PDO::PARAM_STR);
            $stmt->bindParam(":customer_phone", $this->customer_phone, PDO::PARAM_STR);
            $stmt->bindParam(":status", $status, PDO::PARAM_STR);

            $stmt->execute();

            $ticketID = (int) $this->db->lastInsertId();

            // Welcome poruka
            $welcomeMessage = "Zdravo {$this->customer_name}! 👋\n\n" .
                 "Hvala što ste nas kontaktirali. Vaš tiket broj: {$ticketNumber}.\n\n" .
                 "Molimo Vas da sačekate dok admin ne preuzme vašu konverzaciju. " .
                 "Odgovorićemo Vam u najkraćem mogućem roku.\n\n" .
                 "Hvala na strpljenju! 😊";
        
            $this->addMessage($ticketID, 'admin', null, $welcomeMessage);

            // Initial poruka od korisnika
            if (!empty($this->initial_message)) {
                $this->addMessage($ticketID, 'customer', null, $this->initial_message);
            }

            Logger::info("Chat ticket created: $ticketNumber (ID: $ticketID) by {$this->customer_email}");
            Logger::audit("Chat ticket $ticketNumber created", null);

            // Slanje notifikacije admin-u
            $this->sendAdminNotification($ticketNumber, $this->customer_name);

            return $this->success([
                "ticket_id" => $ticketID,
                "ticket_number" => $ticketNumber,
            ]);

        } catch (PDOException $e) {
            Logger::error("Chat ticket create failed: " . $e->getMessage(), [
                'email' => $this->customer_email ?? 'unknown',
                'file' => __FILE__,
                'line' => __LINE__
            ]);
            
            return $this->error('Greška pri kreiranju tiketa!', 500);
        }
    }

    /**
     * Slanje poruke u tiket
     */
    public function sendMessage(): array 
    {
        try {
            if (empty($this->ticket_id) || empty($this->sender_type) || empty($this->message)) {
                return $this->error('Nedostaju obavezni parametri', 400);
            }

            $ticket = $this->getTicket($this->ticket_id);
            
            if (!$ticket) {
                return $this->error('Tiket ne postoji', 404);
            }
            
            if ($ticket->status === 'closed') {
                return $this->error('Tiket je zatvoren', 403);
            }

            $messageID = $this->addMessage(
                $this->ticket_id, 
                $this->sender_type, 
                $this->sender_id, 
                $this->message
            );

            // Update tiketa
            $stmt = $this->db->prepare(
                "UPDATE chat_tickets
                SET last_message_at = NOW(), updated_at = NOW()
                WHERE id = ?"
            );
            $stmt->execute([$this->ticket_id]);

            // Ako admin šalje prvu poruku, postavi status na in_progress
            if ($this->sender_type === "admin" && $ticket->status === "open") {
                $this->updateTicketStatus($this->ticket_id, 'in_progress', $this->sender_id);
            }

            Logger::info("Message sent in ticket #{$ticket->ticket_number} by {$this->sender_type}");

            return $this->success([
                "message_id" => $messageID,
                "created_at" => date("Y-m-d H:i:s")
            ]);
            
        } catch (PDOException $e) {
            Logger::error("Sending message failed: " . $e->getMessage(), [
                'ticket_id' => $this->ticket_id,
                'file' => __FILE__,
                'line' => __LINE__
            ]);
            
            return $this->error("Greška pri slanju poruke!", 500);
        }
    }

    /**
     * Long polling za nove poruke
     */
    public function pollMessages(): array 
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

                // Provera da li je tiket zatvoren
                $ticket = $this->getTicket($ticketID);
                if ($ticket && $ticket->status === 'closed') {
                    return $this->success([
                        'messages' => [],
                        'ticket_closed' => true
                    ]);
                }

                usleep(300000); // 300ms
            }

            return $this->success([
                'messages' => [],
                'timeout' => true
            ]);
            
        } catch (PDOException $e) {
            Logger::error("Poll messages failed: " . $e->getMessage(), [
                'ticket_id' => $ticketID,
                'file' => __FILE__,
                'line' => __LINE__
            ]);
            
            return $this->error('Greška pri preuzimanju poruka', 500);
        }
    }

    /**
     * Long polling za nove tikete (admin)
     */
    public function pollNewTickets(): array 
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
                $stmt->bindParam(":created_at", $lastChecked, PDO::PARAM_STR);
                $stmt->bindParam(":updated_at", $lastChecked, PDO::PARAM_STR);
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
            Logger::error("Poll tickets failed: " . $e->getMessage(), [
                'file' => __FILE__,
                'line' => __LINE__
            ]);
            
            return $this->error('Greška pri preuzimanju tiketa', 500);
        }
    }

    /**
     * Dobijanje svih tiketa (admin)
     */
    public function getAllTickets(): array
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
            Logger::error("Get all tickets failed: " . $e->getMessage(), [
                'file' => __FILE__,
                'line' => __LINE__
            ]);
            
            return $this->error('Greška pri preuzimanju tiketa', 500);
        }
    }

    /**
     * Dobijanje poruka tiketa
     */
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
            Logger::error("Get ticket messages failed: " . $e->getMessage(), [
                'ticket_id' => $ticketId ?? null,
                'file' => __FILE__,
                'line' => __LINE__
            ]);
            
            return $this->error('Greška pri preuzimanju poruka', 500);
        }
    }

    /**
     * Označavanje poruka kao pročitanih
     */
    public function markAsRead(): array 
    {
        try {
            $ticketId = $this->ticket_id ?? 0;
            $readerType = $this->reader_type ?? null;

            if (empty($ticketId) || empty($readerType)) {
                return $this->error('Nedostaju parametri', 400);
            }

            // Označava poruke sa druge strane
            $senderType = ($readerType === 'admin') ? 'customer' : 'admin';

            $stmt = $this->db->prepare(
                "UPDATE chat_messages 
                SET is_read = TRUE 
                WHERE ticket_id = ? AND sender_type = ? AND is_read = FALSE"
            );
            $stmt->execute([$ticketId, $senderType]);

            return $this->success(['marked' => $stmt->rowCount()]);

        } catch (PDOException $e) {
            Logger::error("Mark as read failed: " . $e->getMessage(), [
                'ticket_id' => $ticketId ?? null,
                'file' => __FILE__,
                'line' => __LINE__
            ]);
            
            return $this->error('Greška pri označavanju poruka', 500);
        }
    }

    /**
     * Dodela tiketa admin-u
     */
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

            $stmt = $this->db->prepare("SELECT name FROM users WHERE id = ? LIMIT 1");
            $stmt->execute([$adminId]);
            $admin = $stmt->fetch(PDO::FETCH_OBJ);

            $stmt = $this->db->prepare(
                "UPDATE chat_tickets 
                SET assigned_to = ?, status = 'in_progress', updated_at = NOW() 
                WHERE id = ?"
            );
            $stmt->execute([$adminId, $ticketId]);

            if ($admin) {
                $assignMessage = "Admin {$admin->name} je preuzeo Vašu konverzaciju i uskoro će Vam odgovoriti. 👨‍💼";
                $this->addMessage($ticketId, 'admin', $adminId, $assignMessage);
            }

            Logger::audit("Chat ticket #{$ticket->ticket_number} assigned to admin ID: $adminId", $adminId);

            return $this->success(['assigned' => true]);

        } catch (PDOException $e) {
            Logger::error("Assign ticket failed: " . $e->getMessage(), [
                'ticket_id' => $ticketId ?? null,
                'admin_id' => $adminId ?? null,
                'file' => __FILE__,
                'line' => __LINE__
            ]);
            
            return $this->error('Greška pri preuzimanju tiketa', 500);
        }
    }

    /**
     * Zatvaranje tiketa
     */
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
            Logger::error("Close ticket failed: " . $e->getMessage(), [
                'ticket_id' => $ticketId ?? null,
                'file' => __FILE__,
                'line' => __LINE__
            ]);
            
            return $this->error('Greška pri zatvaranju tiketa', 500);
        }
    }

    /**
     * Ažuriranje typing indikatora
     */
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
            Logger::error("Update typing failed: " . $e->getMessage(), [
                'ticket_id' => $ticketId ?? null,
                'file' => __FILE__,
                'line' => __LINE__
            ]);
            
            return $this->error('Greška pri ažuriranju typing statusa', 500);
        }
    }

    /**
     * Dobijanje typing indikatora
     */
    public function getTyping(): array 
    {
        try {
            $ticketId = $this->ticket_id ?? 0;

            $stmt = $this->db->prepare(
                "SELECT * FROM chat_typing 
                WHERE ticket_id = ? 
                AND updated_at > DATE_SUB(NOW(), INTERVAL 3 SECOND)
                LIMIT 1"
            );
            $stmt->execute([$ticketId]);
            $typing = $stmt->fetch(PDO::FETCH_OBJ);

            return $this->success(['typing' => $typing]);

        } catch (PDOException $e) {
            Logger::error("Get typing failed: " . $e->getMessage(), [
                'ticket_id' => $ticketId ?? null,
                'file' => __FILE__,
                'line' => __LINE__
            ]);
            
            return $this->error('Greška pri preuzimanju typing statusa', 500);
        }
    }
}

?>