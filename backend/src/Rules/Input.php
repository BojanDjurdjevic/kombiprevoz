<?php

namespace Rules;

class Input {
    public static function all(): object {
        $method = $_SERVER['REQUEST_METHOD'];
        if($method === 'GET') {
            $data = $_GET['data'] ?? $_GET;
        } else {
            $json = file_get_contents("php://input");
            $data = json_decode($json, true);
        }
        return json_decode(json_encode($data));
    }
}

?>