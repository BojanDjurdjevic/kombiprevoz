<?php

namespace Models;

use PDO;

class Order {
    public $id;
    public $tour_id;
    public $user_id;
    public $places;
    public $add_from;
    public $add_to;
    public $date;
    public $deleted;
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function getAll() 
    {
        $sql = "SELECT * from orders WHERE deleted = 0 order by orders.date";
        $res = $this->db->query($sql);
        $num = $res->rowCount();

        if($num > 0) {
            $orders = [];
            while($row = $res->fetch(PDO::FETCH_OBJ)) {
                array_push($orders, $row);
            }
            echo json_encode(['orders' => $orders]);
        } else {
            echo json_encode(['msg' => 'Nema rezervisanih vožnji']);
            exit();
        }
    }
}

?>