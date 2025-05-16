<?php

namespace Models;

use PDO;

class Tour {
    public $id;
    public $from_city_id;
    public $to_city_id;
    public $departures;
    public $time;
    public $duration;
    public $price;
    public $seats;
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function getAll() 
    {
        $sql = "SELECT * from tours WHERE deleted = 0";
        $res = $this->db->query($sql);
        $num = $res->rowCount();

        if($num > 0) {
            $tours = [];

            while($row = $res->fetch(PDO::FETCH_OBJ)) {
                array_push($tours, $row);
            }

            echo json_encode(['tours' => $tours]);
        } else
        echo json_encode(['msg' => "Nema dostupnih vožnji."]);
    } 

    public function getOne() {

    }
}

?>