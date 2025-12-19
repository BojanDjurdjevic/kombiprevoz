<?php

namespace Helpers;

use PDO;
use PDOException;
use Rules\Validator;

if (!defined('APP_ACCESS')) {
    http_response_code(403);
    die('Direct access forbidden');
}

class Logger 
{
    private $db;
    private static $logDir = __DIR__ . "/../Logs/";

    public function __construct($db) 
    {
        $this->db = $db;

        if (!is_dir(self::$logDir)) {
            if (!mkdir(self::$logDir, 0755, true)) {
                die("Failed to create log directory: " . self::$logDir);
            }
        }

        if (!is_writable(self::$logDir)) {
            die("Log directory is not writable: " . self::$logDir);
        }
    }

    // LOG Events:
    private static function writeLog($filename, $message, $level = 'INFO') 
    {
        $timestamp = date('Y-m-d H:i:s');
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        
        $logMessage = sprintf(
            "[%s] [%s] [IP: %s] %s | User-Agent: %s\n",
            $timestamp,
            $level,
            $ip,
            $message,
            $userAgent
        );
        
        $filepath = self::$logDir . $filename;
        file_put_contents($filepath, $logMessage, FILE_APPEND | LOCK_EX);
    }

    // LOG Errors
    public static function error($message, $context = []) 
    {
        $contextStr = !empty($context) ? ' | Context: ' . json_encode($context) : '';
        self::writeLog('errors.log', $message . $contextStr, 'ERROR');
    }

   // LOG Security issues
    public static function security($message, $severity = 'MEDIUM') 
    {
        self::writeLog('security.log', $message, "SECURITY-$severity");
    }

    // LOG Admin actions
    public static function audit($message, $userId = null) 
    {
        $user = $userId ?? ($_SESSION['user']['id'] ?? 'unknown');
        self::writeLog('audit.log', "[User: $user] $message", 'AUDIT');
    }

    // LOG Infos 
    public static function info($message) 
    {
        self::writeLog('info.log', $message, 'INFO');
    }

    // Rotate - Store and Remove log files

    public static function rotateLog($filename, $maxSizeMB = 10) 
    {
        $filepath = self::$logDir . $filename;
        
        if (!file_exists($filepath)) return;
        
        $fileSizeMB = filesize($filepath) / 1024 / 1024;
        
        if ($fileSizeMB > $maxSizeMB) {
            $archiveName = $filename . '.' . date('Y-m-d_His') . '.bak';
            rename($filepath, self::$logDir . $archiveName);
            
            touch($filepath);
            
            self::cleanOldArchives($filename, 30);
        }
    }

    private static function cleanOldArchives($filename, $days) 
    {
        $files = glob(self::$logDir . $filename . '.*.bak');
        $now = time();
        
        foreach ($files as $file) {
            if ($now - filemtime($file) >= 60 * 60 * 24 * $days) {
                unlink($file);
            }
        }
    }

    // LOG VARIOUS Changes to DB

    public function logUserChange($userId, $changedBy, $action, $fieldChanged = null, $oldValue = null, $newValue = null) 
    {
        $sql = "INSERT INTO user_logs 
                SET user_id = :user_id, changed_by = :changed_by, 
                action = :action, field_changed = :field_changed, 
                old_value = :old_value, new_value = :new_value, 
                ip_address = :ip_address, user_agent = :user_agent";

        $stmt = $this->db->prepare($sql);
        
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':changed_by', $changedBy, PDO::PARAM_INT);
        $stmt->bindParam(':action', $action);
        $stmt->bindParam(':field_changed', $fieldChanged);
        $stmt->bindParam(':old_value', $oldValue);
        $stmt->bindParam(':new_value', $newValue);
        
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
        
        $stmt->bindParam(':ip_address', $ipAddress);
        $stmt->bindParam(':user_agent', $userAgent);
        
        try {
            $stmt->execute();

            self::audit("User $userId changed by $changedBy: $action ($fieldChanged)", $changedBy);
        } catch (PDOException $e) {
            error_log("Failed to log user change: " . $e->getMessage());
        }
    }

    public function logOrderChange($orderId, $userId, $action, $fieldChanged = null, $oldValue = null, $newValue = null) 
    {
        $sql = "INSERT INTO order_logs 
                SET order_id = :order_id,  user_id = :user_id, 
                action = :action, field_changed = :field_changed, 
                old_value = :old_value, new_value = :new_value, 
                ip_address = :ip_address, user_agent = :user_agent";

        $stmt = $this->db->prepare($sql);
        
        $stmt->bindParam(':order_id', $orderId, PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':action', $action);
        $stmt->bindParam(':field_changed', $fieldChanged);
        $stmt->bindParam(':old_value', $oldValue);
        $stmt->bindParam(':new_value', $newValue);
        
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
        
        $stmt->bindParam(':ip_address', $ipAddress);
        $stmt->bindParam(':user_agent', $userAgent);
        
        try {
            $stmt->execute();
        } catch (PDOException $e) {
            error_log("Failed to log user change: " . $e->getMessage());
        }
    }

    public function getUserLogs(? int $id = null)
    {
        if (!Validator::isAdmin() && !Validator::isSuper()) {
            $sql = "SELECT id, created_at, field_changed
                    FROM user_logs 
                    WHERE user_id = :user_id 
                    ORDER BY created_at DESC 
                    LIMIT 10";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':user_id', $_SESSION['user']['id'], PDO::PARAM_INT);
        } else {
            $sql = "SELECT ul.*, 
                        u.name as changed_by_name
                    FROM user_logs ul
                    LEFT JOIN users u ON ul.changed_by = u.id
                    WHERE ul.user_id = :user_id
                    ORDER BY ul.created_at DESC
                    LIMIT 50";
            
            $stmt = $this->db->prepare($sql);
            $userId = $id ?? $_SESSION['user']['id'];
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        }

        $stmt->execute();
        $logs = $stmt->fetchAll(PDO::FETCH_OBJ);

        return $logs;
    }

    public function getOrderLogs(? int $id = null)
    {
        if (!Validator::isAdmin() && !Validator::isSuper()) {
            $sql = "SELECT created_at, field_changed as field
                    FROM order_logs 
                    WHERE order_id = :order_id 
                    ORDER BY created_at DESC 
                    LIMIT 10";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':order_id', $id, PDO::PARAM_INT);
        } else {
            $sql = "SELECT ol.*, 
                        u.name as changed_by_name
                    FROM order_logs ol
                    LEFT JOIN users u ON ol.changed_by = u.id
                    WHERE ol.order_id = :order_id
                    ORDER BY ol.created_at DESC
                    LIMIT 50";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':order_id', $id, PDO::PARAM_INT);
        }

        $stmt->execute();
        $logs = $stmt->fetchAll(PDO::FETCH_OBJ);

        return $logs;
    }

}

?>