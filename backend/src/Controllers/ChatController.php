<?php

namespace Controllers;

use PDO;
use PDOException;
use Helpers\Logger;
use Rules\Validator;

if (!defined('APP_ACCESS')) {
    http_response_code(403);
    die('Direct access forbidden');
}

class ChatController {

    private $db;
    private $data;
    public function __construct($db, $data) {
        $this->db = $db;
        $this->data = $data;
    }

    public function handleRequest() {
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