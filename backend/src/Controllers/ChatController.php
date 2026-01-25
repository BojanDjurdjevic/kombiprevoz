<?php
declare(strict_types=1);

namespace Controllers;

use Guards\DemoGuard;
use Helpers\Logger;
use Models\Chat;
use PDO;
use Rules\Validator;

if (!defined('APP_ACCESS')) {
    http_response_code(403);
    die('Direct access forbidden');
}

/**
 * ChatController
 * Svaka metoda = jedna akcija
 */
class ChatController {
    private PDO $db;
    private object $data;
    private Chat $chat;

    public function __construct(PDO $db, object $data) 
    {
        $this->db = $db;
        $this->data = $data;
        $this->chat = new Chat($this->db);
    }

    /**
     * Pomoćna metoda za dodelu podataka iz $data u $chat objekat
     */
    private function assignChatData(): void
    {
        $this->chat->ticket_id = (int)$this->data->chat->ticket_id ?? null;
        $this->chat->ticket_number = $this->data->chat->ticket_number ?? null;
        $this->chat->ticket_status = $this->data->chat->ticket_status ?? null;
        $this->chat->customer_name = $this->data->chat->customer_name ?? null;
        $this->chat->customer_email = $this->data->chat->customer_email ?? null;
        $this->chat->reservation_number = $this->data->chat->reservation_number ?? null;
        $this->chat->customer_phone = $this->data->chat->customer_phone ?? null;
        $this->chat->initial_message = $this->data->chat->initial_message ?? null;
        $this->chat->last_message_id = (int)$this->data->chat->last_message_id ?? null;
        $this->chat->last_checked = $this->data->chat->last_checked ?? null;
        $this->chat->sender_id = $this->data->chat->sender_id ?? null;
        $this->chat->sender_type = $this->data->chat->sender_type ?? null;
        $this->chat->message = $this->data->chat->message ?? null;
        $this->chat->assigned_to = $this->data->chat->assigned_to ?? null;
        $this->chat->limit = $this->data->chat->limit ?? null;
        $this->chat->offset = $this->data->chat->offset ?? null;
        $this->chat->reader_type = $this->data->chat->reader_type ?? null;
        $this->chat->admin_id = $this->data->chat->admin_id ?? null;
        $this->chat->user_id = $this->data->chat->user_id ?? null;
        $this->chat->user_type = $this->data->chat->user_type ?? null;
        $this->chat->closed_by = $this->data->chat->closed_by ?? null;
    }

    /**
     * Pomoćna metoda za response
     */
    private function sendResponse(array $result): void
    {
        if ($result['code'] !== 200) {
            http_response_code($result['code']);
            echo json_encode($result['error'], JSON_UNESCAPED_UNICODE);
        } else {
            http_response_code(200);
            echo json_encode($result, JSON_UNESCAPED_UNICODE);
        }
    }

    // ======================== GET METHODS ========================

    /**
     * POLL new messages (long polling)
     * Akcija: { "chat": { "poll_messages": true, "ticket_id": N, "last_message_id": N } }
     */
    public function pollMessages(): void
    {
        $this->assignChatData();
        $result = $this->chat->pollMessages();
        $this->sendResponse($result);
    }

    /**
     * GET ticket messages
     * Akcija: { "chat": { "messages": true, "ticket_id": N, "limit": N, "offset": N } }
     */
    public function getTicketMessages(): void
    {
        $this->assignChatData();
        $result = $this->chat->getTicketMessages();
        $this->sendResponse($result);
    }

    /**
     * GET typing indicator
     * Akcija: { "chat": { "get_typing": true, "ticket_id": N } }
     */
    public function getTypingIndicator(): void
    {
        $this->assignChatData();
        $result = $this->chat->getTyping();
        $this->sendResponse($result);
    }

    /**
     * GET all tickets - admin only
     * Akcija: { "chat": { "admin": { "tickets": true } } }
     */
    public function getAllTickets(): void
    {
        $this->assignChatData();
        $result = $this->chat->getAllTickets();
        $this->sendResponse($result);
    }

    /**
     * POLL new tickets - admin only (long polling)
     * Akcija: { "chat": { "admin": { "poll_tickets": true, "last_checked": "..." } } }
     */
    public function pollNewTickets(): void
    {
        $this->assignChatData();
        $result = $this->chat->pollNewTickets();
        $this->sendResponse($result);
    }

    // ======================== POST METHODS ========================

    /**
     * CREATE new ticket
     * Akcija: { "chat": { "create_ticket": true, "customer_name": "...", ... } }
     */
    public function createTicket(): void
    {
        $this->assignChatData();

        // Validacija
        if (empty($this->chat->customer_name) || empty($this->chat->customer_email)) {
            http_response_code(400);
            echo json_encode([
                'error' => 'Ime i email su obavezni'
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        if (!filter_var($this->chat->customer_email, FILTER_VALIDATE_EMAIL)) {
            http_response_code(400);
            echo json_encode([
                'error' => 'Neispravan format email adrese'
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        $result = $this->chat->createTicket();
        $this->sendResponse($result);
    }

    /**
     * SEND message in ticket
     * Akcija: { "chat": { "send_message": true, "ticket_id": N, "sender_type": "...", ... } }
     */
    public function sendMessage(): void
    {
        $this->assignChatData();

        // Validacija
        if (empty($this->chat->ticket_id)) {
            http_response_code(400);
            echo json_encode([
                'error' => 'ID tiketa je obavezan'
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        if (empty($this->chat->sender_type)) {
            http_response_code(400);
            echo json_encode([
                'error' => 'Tip pošiljaoca je obavezan'
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        if (empty($this->chat->message)) {
            http_response_code(400);
            echo json_encode([
                'error' => 'Poruka ne može biti prazna'
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        $result = $this->chat->sendMessage();
        $this->sendResponse($result);
    }

    /**
     * UPDATE typing indicator
     * Akcija: { "chat": { "typing": true, "ticket_id": N, "user_type": "..." } }
     */
    public function updateTypingIndicator(): void
    {
        $this->assignChatData();

        if (empty($this->chat->ticket_id) || empty($this->chat->user_type)) {
            http_response_code(400);
            echo json_encode([
                'error' => 'Nedostaju parametri'
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        $result = $this->chat->updateTyping();
        $this->sendResponse($result);
    }

    /**
     * MARK messages as read
     * Akcija: { "chat": { "mark_read": true, "ticket_id": N, "reader_type": "..." } }
     */
    public function markMessagesAsRead(): void
    {
        $this->assignChatData();

        if (empty($this->chat->ticket_id) || empty($this->chat->reader_type)) {
            http_response_code(400);
            echo json_encode([
                'error' => 'Nedostaju parametri'
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        $result = $this->chat->markAsRead();
        $this->sendResponse($result);
    }

    /**
     * ASSIGN ticket to admin - admin only
     * Akcija: { "chat": { "admin": { "assign": true }, "ticket_id": N, "admin_id": N } }
     */
    public function assignTicket(): void
    {
        $this->assignChatData();

        if (empty($this->chat->ticket_id) || empty($this->chat->admin_id)) {
            http_response_code(400);
            echo json_encode([
                'error' => 'ID tiketa i ID admina su obavezni'
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        $result = $this->chat->assignTicket();
        $this->sendResponse($result);
    }

    /**
     * CLOSE ticket - admin only
     * Akcija: { "chat": { "admin": { "close": true }, "ticket_id": N, "closed_by": N } }
     */
    public function closeTicket(): void
    {
        $this->assignChatData();

        if (empty($this->chat->ticket_id)) {
            http_response_code(400);
            echo json_encode([
                'error' => 'ID tiketa je obavezan'
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        $result = $this->chat->closeTicket();
        $this->sendResponse($result);
    }

    /**
     * REOPEN ticket - admin only
     * Akcija: { "chat": { "admin": { "reopen": true }, "ticket_id": N } }
     */
    public function reopenTicket(): void
    {
        $this->assignChatData();

        if (empty($this->chat->ticket_id)) {
            http_response_code(400);
            echo json_encode([
                'error' => 'ID tiketa je obavezan'
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        try {
            $ticketId = filter_var($this->chat->ticket_id, FILTER_VALIDATE_INT);
            
            if ($ticketId === false) {
                http_response_code(400);
                echo json_encode([
                    'error' => 'Neispravan ID tiketa'
                ], JSON_UNESCAPED_UNICODE);
                return;
            }

            $stmt = $this->db->prepare(
                "UPDATE chat_tickets 
                SET status = 'in_progress', updated_at = NOW() 
                WHERE id = ?"
            );
            $stmt->execute([$ticketId]);
            
            Logger::audit("Chat ticket ID $ticketId reopened", $_SESSION['user']['id'] ?? null);
            
            http_response_code(200);
            echo json_encode([
                'success' => true, 
                'data' => ['reopened' => true]
            ], JSON_UNESCAPED_UNICODE);
            
        } catch (\PDOException $e) {
            Logger::error("Failed to reopen ticket: " . $e->getMessage(), [
                'ticket_id' => $this->chat->ticket_id,
                'file' => __FILE__,
                'line' => __LINE__
            ]);
            
            http_response_code(500);
            echo json_encode([
                'success' => false, 
                'error' => 'Greška pri ponovnom otvaranju tiketa'
            ], JSON_UNESCAPED_UNICODE);
        }
    }
}

?>