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
    } 

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
    } */

    public static function all(): object {
        $method = $_SERVER['REQUEST_METHOD'];
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

        $data = [];

        if ($method === 'GET') {
            $data = $_GET;
        }

        elseif (str_contains($contentType, 'application/json')) {
            $json = file_get_contents("php://input");
            $decoded = json_decode($json, true);
            $data = is_array($decoded) ? $decoded : [];
        }

        elseif (str_contains($contentType, 'multipart/form-data')) {
            $data = $_POST;

            if (!empty($_FILES)) {
                foreach ($_FILES as $key => $file) {
                    if (is_array($file['name'])) {
                        $files = [];
                        for ($i = 0; $i < count($file['name']); $i++) {
                            $files[] = [
                                'name' => $file['name'][$i],
                                'type' => $file['type'][$i],
                                'tmp_name' => $file['tmp_name'][$i],
                                'error' => $file['error'][$i],
                                'size' => $file['size'][$i],
                            ];
                        }
                        $data[$key] = $files;
                    } else {
                        $data[$key] = $file;
                    }
                }
            }
        }

        else {
            $raw = file_get_contents("php://input");
            $decoded = json_decode($raw, true);
            $data = is_array($decoded) ? $decoded : [];
        }

        return json_decode(json_encode($data));
    }
}

?>