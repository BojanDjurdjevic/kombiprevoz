<?php

namespace Models;

class Country {
    public $id;
    public $name;
    public $db;

    public function __construct($db) {
        $this->db = $db;
        //$this->db->connect();
    }

    public function getCountries() {
        $query = "SELECT * from countries";
        
        $stmt = $this->db->prepare($query);
        $result = $stmt->execute(); 

        //$num = $result->num_rows();

        if($result) {
            $countries = [];

            while($row = $result->fetch_object()) {
                array_push($row);
            }

            echo json_encode($countries, JSON_PRETTY_PRINT);

        } else {
            $msg = [
                "message" => 'Nije pronađena nijedna država'
            ];
            echo json_encode($msg, JSON_PRETTY_PRINT);
        }
    }

}

?>