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
    public $new_pass;
    public $new_pass_confirm;
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

    // Check if it the User is logedin:
    public static function isLoged($id, $email, $db)
    {
        if(isset($_SESSION['user_id']) && $_SESSION['user_id'] == $id && isset($_SESSION['user_name']) && isset($_SESSION['user_email'])) return true;
        elseif(isset($id) && !empty($id)) {
            $find = "SELECT * FROM users WHERE id = '$id' AND deleted = 0";
            $res = $db->query($find);
            $user = $res->fetch(PDO::FETCH_OBJ);

            if($user) {
                if($email == $user->email) {
                    $_SESSION['user_id'] = $user->id;
                    $_SESSION['user_name'] = $user->name;
                    $_SESSION['user_email'] = $user->email;
                    $_SESSION['user_status'] = $user->status;
                    $_SESSION['sid'] = session_id();
                    return true;
                } else return false;
            } else return false;
            
        } else return false;
    }

    // Check if the User i owner of account
    public function isOwner()
    {
        if($this->id == $_SESSION['user_id']) return true;
        else return false;
    }

    // -------------------------  GET --------------------------------- // 
    
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

    // -------------------------  POST --------------------------------- // 

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
                    "user" => 'Nije moguće kreirati novog korisnika.'
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
            if(password_verify($this->pass, $user->pass)) {
                //session_start();

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

    public function logout()
    {
        echo json_encode(['user' => "Doviđenja {$_SESSION['user_name']}"], JSON_PRETTY_PRINT);
        session_unset();
        session_destroy();
    }

    // -------------------------  PUT --------------------------------- // 

    // Update User general data:
    public function update() 
    {   if($this->isOwner() || Validator::isSuper() || Validator::isAdmin()) {
            $sql = "UPDATE users SET name = :name, email = :email, city = :city, address = :adress, phone = :phone
                    WHERE id = '$this->id' and deleted = 0"
            ;
            $stmt = $this->db->prepare($sql);

            if(filter_var($this->email, FILTER_VALIDATE_EMAIL) && Validator::validateString($this->name)) {
                $this->name = htmlspecialchars(strip_tags($this->name), ENT_QUOTES);
                $this->email = htmlspecialchars(strip_tags($this->email), ENT_QUOTES);
                $this->city = htmlspecialchars(strip_tags($this->city), ENT_QUOTES);
                $this->address = htmlspecialchars(strip_tags($this->address), ENT_QUOTES);
                $this->phone = htmlspecialchars(strip_tags($this->phone), ENT_QUOTES);

                $stmt->bindParam(':name', $this->name);
                $stmt->bindParam(':email', $this->email);
                $stmt->bindParam(':city', $this->city);
                $stmt->bindParam(':address', $this->address);
                $stmt->bindParam(':phone', $this->phone);

                try {
                    if($stmt->execute()) {
                        $_SESSION['user_name'] = $this->name;
                        $_SESSION['user_email'] = $this->email;

                        $splited = explode(" ", $this->name);
                        $arr = [];
                        foreach($splited as $s) {
                            array_push($arr, strtoupper($s[0]));
                            echo $s . " - ". $s[0];
                        }
                        $initials = implode("", $arr);

                        $logedUser = [
                            'id' => $this->id,
                            'name' => $this->name,
                            'email' => $this->email,
                            'city' => $this->city,
                            "address" => $this->address,
                            'phone' => $this->phone,
                            'initials' => $initials
                        ];

                        echo json_encode([
                            'msg' => 'Uspešno ste ažurirali lične podatke.',
                            'user' => $logedUser
                        ]);
                    }
                } catch(PDOException $e) {
                    echo json_encode([
                        'user' => 'Došlo je do greške prilikom ažuriranja. Molimo obratite se našoj podršci!', 
                        'error' => $e->getMessage()
                    ]);
                }
                
            } else {
                echo json_encode(['user' => 'Nije moguće ažurirati profil. Molimo Vas da pravilno unesete podatke!']);
            }
        } else echo json_encode(['user' => 'Niste autorizovani da ažurirate profil!']);
    }

    // PASSWORD Update
    public function updatePassword()
    {
        
    }

     // -------------------------  DELETE --------------------------------- // 

     // Delete User
     public function delete()
     {
        $sql = "UPDATE users SET deleted = 1 WHERE id = :id";
        $stmt = $this->db->prepare($sql);

        $this->id = htmlspecialchars(strip_tags($this->id), ENT_QUOTES);
        $stmt->bindParam(':id', $this->id);

        try {
            if($stmt->execute()) {
                echo json_encode(['user' => 'Korisnik je uspešno obrisan']);
            }
        } catch (PDOException $e) {
            echo json_encode([
                'user' => 'Došlo je do greške! Korisnik nije obrisan.',
                'msg' => $e->getMessage()
            ]);
        }
        
     }

     // Restore User
     public function restore()
     {
        $sql = "UPDATE users SET deleted = 0 WHERE id = :id";
        $stmt = $this->db->prepare($sql);

        $this->id = htmlspecialchars(strip_tags($this->id), ENT_QUOTES);
        $stmt->bindParam(':id', $this->id);

        try {
            if($stmt->execute()) {
                echo json_encode(['user' => 'Korisnik je uspešno aktiviran']);
            }
        } catch (PDOException $e) {
            echo json_encode([
                'user' => 'Došlo je do greške! Korisnik nije aktiviran, molimo Vas da se obratite podršci.',
                'msg' => $e->getMessage()
            ]);
        }
        
     }
}

/*
    "user": {
        "id": null, 
        "name": "Bogdan Đurović",
        "email": "bogy@test.com",
        "pass": "BogyCar!123",
        "city": "Novi Sad",
        "address": "Puškinova 9",
        "phone": "062648963"
    },
    "new_pass": {
        "password": "",
        "confirmation_pass": ""
    },
    "all": null,
    "byID": null,
    "byEmail": null,
    "byName": null,
    "byCity": null,
    "signin": null,
    "login": null,
    "updateProfile": null,
    "updatePass": null,
    "delete": null,
    "restore": null

    ------------------------------------------------

    "pass": "Ljubavicmojija!123",

    "sid": "g3l0a87rf3c863qeab8070uvo8",
    "user_id": 8,
    "orders": {
        "all": true
    }
*/

?>