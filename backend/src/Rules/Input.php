<?php

namespace Rules;

class Input { /*
    public static function all(): object {
        $method = $_SERVER['REQUEST_METHOD'];
        if($method === 'GET') {
            $data = $_GET['data'] ?? $_GET;
        } else {
            $json = file_get_contents("php://input");
            $data = json_decode($json, true);
        }
        return json_decode(json_encode($data));
    } */

    public static function all(): object {
        $method = $_SERVER['REQUEST_METHOD'];

        if (!empty($_FILES) || isset($_SERVER['CONTENT_TYPE']) && str_contains($_SERVER['CONTENT_TYPE'], 'multipart/form-data')) {
            $data = array_merge($_POST, ['_files' => $_FILES]);
        }

        else {
            if ($method === 'GET') {
                $data = $_GET['data'] ?? $_GET;
            } else {
                $json = file_get_contents("php://input");
                $decoded = json_decode($json, true);
                $data = is_array($decoded) ? $decoded : [];
            }
        }

        return json_decode(json_encode($data));
    }
}

?>