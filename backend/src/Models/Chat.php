<?php

namespace Models;

use Helpers\Logger;

if (!defined('APP_ACCESS')) {
    http_response_code(403);
    die('Direct access forbidden');
}

class Chat {
    private $db;
    public $logger;

    public function __construct($db) {
        $this->db = $db;
        $this->logger = new Logger($this->db);
    }

    
}

?>