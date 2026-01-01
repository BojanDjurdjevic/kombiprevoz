<?php

use Helpers\Logger;

class Database {

    private static $instance = null;
    private $conn;

    private function __construct() {}
    private function __clone() {}

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function connect() {
        if ($this->conn !== null) {
            return $this->conn;
        }

        try {
            $dsn = sprintf(
                'mysql:host=%s;dbname=%s;charset=utf8mb4',
                $_ENV["DATABASE_HOSTNAME"],
                $_ENV["DATABASE_NAME"]
            );
            /*
            $this->conn = new PDO(
                'mysql:host=' . $_ENV["DATABASE_HOSTNAME"] . ';dbname='. $_ENV["DATABASE_NAME"], $_ENV["DATABASE_USERNAME"], $_ENV["DATABASE_PASSWORD"]
            ); 
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            */
            $this->conn = new PDO(
                $dsn,
                $_ENV["DATABASE_USERNAME"],
                $_ENV["DATABASE_PASSWORD"],
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false, 
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
                ]
            );
            Logger::info('Database connection established successfully');
        } catch (PDOException $e) {
            echo json_encode(['error' => 'Došlo je do problema pri učitavanju!'], JSON_UNESCAPED_UNICODE);
            Logger::error('Connection failed at DatabasePhp ', [
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
                'file' => __FILE__,
                'line' => __LINE__,
                'host' => $_ENV["DATABASE_HOSTNAME"] ?? 'not set',
                'database' => $_ENV["DATABASE_NAME"] ?? 'not set'
            ]);

            if ($_ENV['APP_ENV'] === 'production') {
                http_response_code(503);
                die(json_encode([
                    'success' => false,
                    'message' => 'Servis je trenutno nedostupan. Molimo pokušajte kasnije.'
                ], JSON_UNESCAPED_UNICODE));
            } else {
                http_response_code(503);
                die(json_encode([
                    'success' => false,
                    'message' => 'Database connection failed',
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ], JSON_UNESCAPED_UNICODE));
            }
        }

        return $this->conn;
    }

    public function isConnected() {
        return $this->conn !== null;
    }

    public function disconnect() {
        $this->conn = null;
        Logger::info('Database connection closed');
    }

}

?>