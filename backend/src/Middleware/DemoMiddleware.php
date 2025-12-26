<?php

namespace Middleware;

class DemoMiddleware
{
    public static function handle()
    {
        if (!empty($_SESSION['user']['is_demo'])) {
            http_response_code(403);
            echo json_encode([
                'error' => 'Demo nalog nema dozvolu za ovu akciju.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }
}


?>