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
                # code...
                break;
            case 'POST':

                break;

            case 'PUT':

                break;
            
            case 'DELETE':

                break;
        }
    }

    
}

?>