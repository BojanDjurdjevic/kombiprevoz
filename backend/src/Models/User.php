<?php

namespace Models;

use PDO;
use Rules\Validator;

class User {
    public $id;
    public $name;
    public $email;
    public $pass;
    public $status;
    public $city;
    public $address;
    public $phone;
    public $verified;
    public $db;
    public $sid;

    public function __construct($db)
    {
        $this->db = $db;
    }

    // GET 
    
    //all users
    public function getAll() 
    {
        $sql = "SELECT id, name, email, status, city, address, phone FROM users WHERE deleted = 0";
        $res = $this->db->query($sql);
        $num = $res->rowCount();

        if($num > 0) {
            $users = [];
            while($row = $res->fetch(PDO::FETCH_OBJ)) {
                array_push($users, $row);
            }
            echo json_encode(["user" => $users], JSON_PRETTY_PRINT);
        } else
        echo json_encode(["user" => 'Trenutno nema registrovanih korisnika.'], JSON_PRETTY_PRINT);
    }

    // By ID
    public function getByID() 
    {

    }

    // By EMAIL
    public function getByEmail() 
    {

    }

    // By NAME
    public function getByName()
    {

    }

    // By CITY
    public function getByCity()
    {

    }

    // POST

    // signin - create
    public function create()
    {
        if(Validator::validatePassword($this->pass)) {
            echo json_encode(['user' => 'Password OK']);
        } else {
            echo json_encode(['user' => 'Molimo Vas da pravilno unesete podatke!']);
        }
        
    }
}

?>