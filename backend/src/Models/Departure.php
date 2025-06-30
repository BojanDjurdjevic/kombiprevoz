<?php

namespace Models;

use PDO;
use PDOException;

class Departure {
    public $id;
    public $driver_id;
    public $dep_orders;
    public $code;
    public $path;
    public $time;

    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function getAll() 
    {
        $sql = "SELECT * FROM departures WHERE deleted = 0";

        try {
            $res = $this->db->query($sql);
            $num = $res->rowCount();

            if($num > 0) {
                $departures = [];
                while($row = $res->fetch(PDO::FETCH_OBJ)) {
                    array_push($departures, $row);
                }
                echo json_encode([
                    'departures' => $departures
                ], JSON_PRETTY_PRINT);
            }
        } catch (PDOException $e) {
            echo json_encode([
                'departure' => 'Došlo je do problema pri konekciji na bazu!',
                'msg' => $e->getMessage()
            ], JSON_PRETTY_PRINT);
        }
    }
}
?>