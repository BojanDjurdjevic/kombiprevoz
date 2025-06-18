<?php

namespace Models;

use PDO;
use PDOException;
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
        $sql = "SELECT id, name, email, status, city, address, phone 
        FROM users WHERE deleted = 0 and id = '$this->id'"
        ;
        $res = $this->db->query($sql);
        $num = $res->rowCount();
        if($num > 0) {
            $users = [];
            while($row = $res->fetch(PDO::FETCH_OBJ)) {
                array_push($users, $row);
            }
            echo json_encode(["user" => $users], JSON_PRETTY_PRINT);
        } else
        echo json_encode(["user" => 'Nema registrovanih korisnika sa poslatim ID-em.'], JSON_PRETTY_PRINT);
    }

    // By EMAIL
    public function getByEmail() 
    {
        $sql = "SELECT id, name, email, status, city, address, phone 
        FROM users WHERE deleted = 0 and email = '$this->email'"
        ;
        $res = $this->db->query($sql);
        $num = $res->rowCount();
        if($num > 0) {
            $users = [];
            while($row = $res->fetch(PDO::FETCH_OBJ)) {
                array_push($users, $row);
            }
            echo json_encode(["user" => $users], JSON_PRETTY_PRINT);
        } else
        echo json_encode(["user" => 'Nije pronađen korisnik.'], JSON_PRETTY_PRINT);
    }

    // By NAME
    public function getByName()
    {
        $sql = "SELECT id, name, email, status, city, address, phone 
        FROM users WHERE deleted = 0 and name LIKE '%$this->name%'"
        ;
        $res = $this->db->query($sql);
        $num = $res->rowCount();
        if($num > 0) {
            $users = [];
            while($row = $res->fetch(PDO::FETCH_OBJ)) {
                array_push($users, $row);
            }
            echo json_encode(["user" => $users], JSON_PRETTY_PRINT);
        } else
        echo json_encode(["user" => 'Nema registrovanih korisnika sa naznačenim imenom.'], JSON_PRETTY_PRINT);
    }

    // By CITY
    public function getByCity()
    {
        $sql = "SELECT id, name, email, status, city, address, phone 
        FROM users WHERE deleted = 0 and city LIKE '%$this->city%'"
        ;
        $res = $this->db->query($sql);
        $num = $res->rowCount();
        if($num > 0) {
            $users = [];
            while($row = $res->fetch(PDO::FETCH_OBJ)) {
                array_push($users, $row);
            }
            echo json_encode(["user" => $users], JSON_PRETTY_PRINT);
        } else
        echo json_encode(["user" => 'Nema registrovanih korisnika sa naznačenim mestom stanovanja.'], JSON_PRETTY_PRINT);
    }

    // POST

    // signin - create
    public function create()
    {
        if(Validator::validateString($this->name) && Validator::validatePassword($this->pass)
            && filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            $sql = "INSERT INTO users SET name = :name, email = :email, pass = :pass, status = :status,
                    city = :city, address = :address, phone = :phone"
            ;
            $stmt = $this->db->prepare($sql);

            $this->id = htmlspecialchars(strip_tags($this->id), ENT_QUOTES);
            $this->name = htmlspecialchars(strip_tags($this->name), ENT_QUOTES);
            $this->email = htmlspecialchars(strip_tags($this->email), ENT_QUOTES);
            //$this->pass = htmlspecialchars(strip_tags($this->pass), ENT_QUOTES);
            $this->status = htmlspecialchars(strip_tags($this->status), ENT_QUOTES);
            $this->city = htmlspecialchars(strip_tags($this->city), ENT_QUOTES);
            $this->address = htmlspecialchars(strip_tags($this->address), ENT_QUOTES);
            $this->phone = htmlspecialchars(strip_tags($this->phone), ENT_QUOTES);

            $hashed = password_hash($this->pass, PASSWORD_DEFAULT);

            $stmt->bindParam(':name', $this->name);
            $stmt->bindParam(':email', $this->email);
            $stmt->bindParam(':pass', $hashed);
            $stmt->bindParam(':status', $this->status);
            $stmt->bindParam(':city', $this->city);
            $stmt->bindParam(':address', $this->address);
            $stmt->bindParam(':phone', $this->phone);
            
            try {
                if($stmt->execute()) {
                    echo json_encode(['user' => [
                        'msg' => 'Novi korisnik je uspešno kreiran.',
                        'user_id' => $this->db->lastInsertId()
                        ]
                    ]);
                }
            } catch (PDOException $e) {
                if($e->getCode() == 23000) {
                    echo json_encode([
                        "user" => 'Email nije dostupan! Molimo Vas da probate sa drugim.'
                    ]);
                } else 
                echo json_encode([
                    'status' => 500,
                    "user" => 'Tije moguće kreirati novog korisnika.'
                ]); 
            }
            
            
        } else {
            echo json_encode(['user' => 'Molimo Vas da pravilno unesete podatke!']);
        }
        
    }

    public function login()
    {
        $find = "SELECT * FROM users WHERE email = '$this->email' AND deleted = 0";
        $res = $this->db->query($find);
        $user = $res->fetch(PDO::FETCH_OBJ);

        if($user) {
            echo json_encode(['userPass' => $user->pass, 'pass' => $this->pass], JSON_PRETTY_PRINT);
            if(password_verify($this->pass, $user->pass)) {
                session_start();

                $_SESSION['user_id'] = $user->id;
                $_SESSION['user_name'] = $user->name;
                $_SESSION['user_email'] = $user->email;
                $_SESSION['user_status'] = $user->status;
                $_SESSION['sid'] = session_id();

                $splited = explode(" ", $user->name);
                $arr = [];
                foreach($splited as $s) {
                    array_push($arr, strtoupper($s[0]));
                    echo $s . " - ". $s[0];
                }
                $initials = implode("", $arr);

                $logedUser = [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'status' => $user->status,
                    'city' => $user->city,
                    "address" => $user->address,
                    'phone' => $user->phone,
                    'initials' => $initials
                ];

                echo json_encode([
                    'sid' => $_SESSION['sid'],
                    'user' => $logedUser
                ], JSON_PRETTY_PRINT);
            } else
            echo json_encode(['user' => 'Pogrešan email / lozinka!'], JSON_PRETTY_PRINT);
        } else
        echo json_encode(['user' => 'Pogrešan email ili lozinka!'], JSON_PRETTY_PRINT);
    }
}

/*
    "user": {
        "id": null, 
        "name": "Bogdan Đurović",
        "email": "bogy@test.com",
        "pass": "BogyCar!123",
        "status": "User",
        "city": "Novi Sad",
        "address": "Puškinova 9",
        "phone": "062648963"
    },
    "all": null,
    "byID": null,
    "byEmail": null,
    "byName": null,
    "byCity": null,
    "signin": null,
    "login": true


    "sid": "g3l0a87rf3c863qeab8070uvo8",
    "orders": {
        "all": true
    }
*/

?>