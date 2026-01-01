<?php 

namespace Guards;

class DemoGuard
{
    public static function denyIfDemo(string $message = 'Akcija nije dozvoljena u demo režimu.'): void
    {
        if (!isset($_SESSION['user'])) {
            http_response_code(401);
            echo json_encode([
                'error' => 'Morate biti ulogovani za ovu akciju'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        if (!empty($_SESSION['user']['is_demo'])) {
            http_response_code(403);
            echo json_encode([
                'error' => $message
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    public static function requireAuth(string $message = 'Morate biti ulogovani'): void
    {
        if (!isset($_SESSION['user'])) {
            http_response_code(401);
            echo json_encode([
                'error' => $message
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    public static function requireRole($allowedRoles, string $message = 'Nemate dozvolu za ovu akciju'): void
    {
        self::requireAuth();

        $roles = is_array($allowedRoles) ? $allowedRoles : [$allowedRoles];
        $userRole = $_SESSION['user']['status'] ?? null;

        if (!in_array($userRole, $roles, true)) {
            http_response_code(403);
            echo json_encode([
                'error' => $message
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }
}

?>