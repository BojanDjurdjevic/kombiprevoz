<?php

namespace Helpers;

use PDO;
use PDOException;
use Rules\Validator;

class Logger 
{
    private $db;

    public function __construct($db) 
    {
        $this->db = $db;
    }

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

    public function getUserLogs() 
    {
        if (!Validator::isAdmin() && !Validator::isSuper()) {
            $sql = "SELECT created_at 
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
            $userId = $_GET['user_id'] ?? $_SESSION['user']['id'];
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        }

        $stmt->execute();
        $logs = $stmt->fetchAll(PDO::FETCH_OBJ);

        echo json_encode(['logs' => $logs], JSON_UNESCAPED_UNICODE);
    }

}

?>