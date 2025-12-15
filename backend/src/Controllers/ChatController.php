<?php

namespace Controllers;

use PDO;
use PDOException;
use Helpers\Logger;
use Models\Chat;
use Rules\Validator;

if (!defined('APP_ACCESS')) {
    http_response_code(403);
    die('Direct access forbidden');
}

class ChatController {

    private $db;
    private $data;
    public $chat;
    public function __construct($db, $data) {
        $this->db = $db;
        $this->data = $data;
        $this->chat = new Chat($this->db);
    }

    public function handleRequest() {
        $this->chat->ticket_id = $this->data->chat->ticket_id ?? null;
        $this->chat->ticket_number = $this->data->chat->ticket_number ?? null;
        $this->chat->ticket_status = $this->data->chat->ticket_status ?? null;
        $this->chat->customer_name = $this->data->chat->customer_name ?? null;
        $this->chat->customer_email = $this->data->chat->customer_email ?? null;
        $this->chat->reservation_number = $this->data->chat->reservation_number ?? null;
        $this->chat->customer_phone = $this->data->chat->customer_phone ?? null;
        $this->chat->initial_message = $this->data->chat->initial_message ?? null;
        $this->chat->last_message_id = $this->data->chat->last_message_id ?? null;
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


        $request = $_SERVER['REQUEST_METHOD'];

        switch ($request) {
            case 'GET':
                if (isset($this->data->chat->poll_messages) && $this->data->chat->poll_messages) {
                    $res = $this->chat->pollMessages();
                    if($res['code'] !== 200) {
                        http_response_code($res['code']);
                        echo json_encode($res['error'], JSON_UNESCAPED_UNICODE);
                        exit;
                    }
                    echo json_encode($res, JSON_UNESCAPED_UNICODE);
                    exit;
                }
                if (isset($this->data->chat->messages) && $this->data->chat->messages) {
                    $res = $this->chat->getTicketMessages();
                    if($res['code'] !== 200) {
                        http_response_code($res['code']);
                        echo json_encode($res['error'], JSON_UNESCAPED_UNICODE);
                        exit;
                    }
                    echo json_encode($res, JSON_UNESCAPED_UNICODE);
                    exit;
                }
                if (isset($this->data->chat->get_typing) && $this->data->chat->get_typing) {
                    $res = $this->chat->getTyping();
                    if($res['code'] !== 200) {
                        http_response_code($res['code']);
                        echo json_encode($res['error'], JSON_UNESCAPED_UNICODE);
                        exit;
                    }
                    echo json_encode($res, JSON_UNESCAPED_UNICODE);
                    exit;
                }
                if (isset($this->data->chat->admin->tickets) && $this->data->chat->admin->tickets) {
                    if (!Validator::isAdmin() && !Validator::isSuper()) {
                        http_response_code(403);
                        echo json_encode(['success' => false, 'error' => 'Niste autorizovani da preuzmete', 'code' => 403], JSON_UNESCAPED_UNICODE);
                        exit;
                    }

                    $res = $this->chat->getAllTickets();
                    if($res['code'] !== 200) {
                        http_response_code($res['code']);
                        echo json_encode($res['error'], JSON_UNESCAPED_UNICODE);
                        exit;
                    }
                    echo json_encode($res, JSON_UNESCAPED_UNICODE);
                    exit;
                }
                if (isset($this->data->chat->admin->poll_tickets) && $this->data->chat->admin->poll_tickets) {
                    if (!Validator::isAdmin() && !Validator::isSuper()) {
                        http_response_code(403);
                        echo json_encode(['success' => false, 'error' => 'Niste autorizovani da preuzmete', 'code' => 403], JSON_UNESCAPED_UNICODE);
                        exit;
                    }

                    $res = $this->chat->pollNewTickets();
                    if($res['code'] !== 200) {
                        http_response_code($res['code']);
                        echo json_encode($res['error'], JSON_UNESCAPED_UNICODE);
                        exit;
                    }
                    echo json_encode($res, JSON_UNESCAPED_UNICODE);
                    exit;
                }
                break;
            case 'POST':
                if (isset($this->data->chat->create_ticket) && $this->data->chat->create_ticket) {
                    $res = $this->chat->createTicket();
                    if($res['code'] !== 200) {
                        http_response_code($res['code']);
                        echo json_encode($res['error'], JSON_UNESCAPED_UNICODE);
                        exit;
                    }
                    echo json_encode($res, JSON_UNESCAPED_UNICODE);
                    exit;
                }
                if (isset($this->data->chat->send_message) && $this->data->chat->send_message) {
                    $res = $this->chat->sendMessage();
                    if($res['code'] !== 200) {
                        http_response_code($res['code']);
                        echo json_encode($res['error'], JSON_UNESCAPED_UNICODE);
                        exit;
                    }
                    echo json_encode($res, JSON_UNESCAPED_UNICODE);
                    exit;
                }
                if (isset($this->data->chat->typing) && $this->data->chat->typing) {
                    $res = $this->chat->updateTyping();
                    if($res['code'] !== 200) {
                        http_response_code($res['code']);
                        echo json_encode($res['error'], JSON_UNESCAPED_UNICODE);
                        exit;
                    }
                    echo json_encode($res, JSON_UNESCAPED_UNICODE);
                    exit;
                }
                if (isset($this->data->chat->mark_read) && $this->data->chat->mark_read) {
                    $res = $this->chat->markAsRead();
                    if($res['code'] !== 200) {
                        http_response_code($res['code']);
                        echo json_encode($res['error'], JSON_UNESCAPED_UNICODE);
                        exit;
                    }
                    echo json_encode($res, JSON_UNESCAPED_UNICODE);
                    exit;
                }
                if (isset($this->data->chat->admin->assign) && $this->data->chat->admin->assign) {
                    if (!Validator::isAdmin() && !Validator::isSuper()) {
                        http_response_code(403);
                        echo json_encode(['success' => false, 'error' => 'Niste autorizovani da preuzmete', 'code' => 403], JSON_UNESCAPED_UNICODE);
                        exit;
                    }

                    $res = $this->chat->assignTicket();
                    if($res['code'] !== 200) {
                        http_response_code($res['code']);
                        echo json_encode($res['error'], JSON_UNESCAPED_UNICODE);
                        exit;
                    }
                    echo json_encode($res, JSON_UNESCAPED_UNICODE);
                    exit;
                }
                if (isset($this->data->chat->admin->close) && $this->data->chat->admin->close) {
                    if (!Validator::isAdmin() && !Validator::isSuper()) {
                        http_response_code(403);
                        echo json_encode(['success' => false, 'error' => 'Niste autorizovani da preuzmete', 'code' => 403], JSON_UNESCAPED_UNICODE);
                        exit;
                    }

                    $res = $this->chat->closeTicket();
                    if($res['code'] !== 200) {
                        http_response_code($res['code']);
                        echo json_encode($res['error'], JSON_UNESCAPED_UNICODE);
                        exit;
                    }
                    echo json_encode($res, JSON_UNESCAPED_UNICODE);
                    exit;
                }
                if (isset($this->data->chat->admin->reopen) && $this->data->chat->admin->reopen) {
                    if (!Validator::isAdmin() && !Validator::isSuper()) {
                        http_response_code(403);
                        echo json_encode(['success' => false, 'error' => 'Niste autorizovani da preuzmete', 'code' => 403], JSON_UNESCAPED_UNICODE);
                        exit;
                    }

                    try {
                        $ticketId = $this->chat->ticket_id ?? 0;
                        $stmt = $this->db->prepare("UPDATE chat_tickets SET status = 'in_progress', updated_at = NOW() WHERE id = ?");
                        $stmt->execute([$ticketId]);
                        
                        Logger::audit("Chat ticket ID $ticketId reopened", $_SESSION['user']['id'] ?? null);
                        
                        echo json_encode(['success' => true, 'data' => ['reopened' => true]], JSON_UNESCAPED_UNICODE);
                    } catch (PDOException $e) {
                        Logger::error("Failed to reopen ticket by Admin: " . $e->getMessage());
                        http_response_code(500);
                        echo json_encode(['success' => false, 'error' => 'Greška pri ponovnom otvaranju tiketa', 'code' => 500], JSON_UNESCAPED_UNICODE);
                    }
                }
                break;

            case 'PUT':

                break;
            
            case 'DELETE':

                break;
        }
    }

    
}

?>