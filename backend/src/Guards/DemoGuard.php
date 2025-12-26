<?php 

namespace Guards;

class DemoGuard
{
    public static function denyIfDemo(string $message = 'Akcija nije dozvoljena u demo režimu.')
    {
        if (!isset($_SESSION['user']) || !empty($_SESSION['user']['is_demo'])) {
            http_response_code(403);
            echo json_encode([
                'error' => $message
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }
}

?>