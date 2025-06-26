<?php

namespace Models;

use PDO;
use PDOException;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;
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
    public $token;
    public $expiry;
    public $db;
    public $sid;

    public function __construct($db)
    {
        $this->db = $db;
    }

    // Check if it the User is logedin:
    public static function isLoged($id, $email, $db)
    {
        if(isset($_SESSION['user_id']) && isset($_SESSION['user_name']) && isset($_SESSION['user_email'])) return true;
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
            $user = [];
            while($row = $res->fetch(PDO::FETCH_OBJ)) {
                array_push($user, [
                    "id" => $row->id,
                    "name" => $row->name,
                    "email" => $row->email,
                    "status" => $row->status,
                    "city" => $row->city,
                    "address" => $row->address,
                    "phone" => $row->phone
                ]);
            }
            echo json_encode(["user" => $user], JSON_PRETTY_PRINT);
            return $user;
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

    // Check if the User i owner of account
    public function isOwner()
    {
        if(isset($_SESSION['user_id']) && !empty($this->id) && $this->id == $_SESSION['user_id']) return true;
        else return false;
    }

    // Check if the reset-password token is good
    public function checkToken($token)
    {
        $token_hash = hash("sha256", $token);
        $sql = "SELECT * FROM users WHERE reset_token_hash = :token";

        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':token', $token_hash);

        try {
            if($stmt->execute()) {
                $user = $stmt->fetch(PDO::FETCH_OBJ);

                if(!$user) {
                    echo json_encode([
                        'token' => 404,
                        'msg' => 'Token nije pronađen.'
                    ], JSON_PRETTY_PRINT);
                    return false;
                }
                if(strtotime($user->reset_token_expires) <= time()) {
                    echo json_encode([
                        'token' => 404,
                        'msg' => 'Token je istekao. Molimo Vas da ponovo kliknete link: Zaboravljena Lozinka'
                    ], JSON_PRETTY_PRINT);
                    return false;
                }

                $this->id = $user->id;
                $this->name = $user->name;
                $this->email = $user->email;

                echo json_encode([
                    'token' => 200,
                    'msg' => 'Token je u redu. Molimo Vas da izmenite lozinku.'
                ], JSON_PRETTY_PRINT);
                return true;
            }
        } catch (PDOException $e) {
            echo json_encode([
                'user' => 'Došlo je do greške!',
                'msg' => $e->getMessage()
            ], JSON_PRETTY_PRINT);
        }
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
            $sql = "UPDATE users SET name = :name, email = :email, city = :city, address = :address, phone = :phone
                    WHERE id = :id and deleted = 0"
            ;
            $stmt = $this->db->prepare($sql);

            if(filter_var($this->email, FILTER_VALIDATE_EMAIL) && Validator::validateString($this->name)) {
                $this->id = htmlspecialchars(strip_tags($this->id), ENT_QUOTES);
                $this->name = htmlspecialchars(strip_tags($this->name), ENT_QUOTES);
                $this->email = htmlspecialchars(strip_tags($this->email), ENT_QUOTES);
                $this->city = htmlspecialchars(strip_tags($this->city), ENT_QUOTES);
                $this->address = htmlspecialchars(strip_tags($this->address), ENT_QUOTES);
                $this->phone = htmlspecialchars(strip_tags($this->phone), ENT_QUOTES);

                $stmt->bindParam(':id', $this->id);
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
        $find = "SELECT * FROM users WHERE id = '$this->id' AND deleted = 0";
        $res = $this->db->query($find);
        $user = $res->fetch(PDO::FETCH_OBJ);

        if($user) {
            if(password_verify($this->pass, $user->pass)) {
                if($this->new_pass == $this->new_pass_confirm) {
                    if(Validator::validatePassword($this->new_pass)) {
                        $sql = "UPDATE users SET pass = :pass WHERE id = :id";
                        $stmt = $this->db->prepare($sql);
                        
                        $this->id = htmlspecialchars(strip_tags($this->id), ENT_QUOTES);
                        $hashed = password_hash($this->new_pass, PASSWORD_DEFAULT);

                        $stmt->bindParam(':id', $this->id);
                        $stmt->bindParam(':pass', $hashed);

                        try {
                            if($stmt->execute()) {
                                echo json_encode(['user' => 'Vaša lozinka je uspešno izmenjena!']);
                            }
                        } catch (PDOException $e) {
                            echo json_encode([
                                'user' => 'Došlo je do greške! Lozinka nije izmenjena.',
                                'msg' => $e->getMessage()
                            ], JSON_PRETTY_PRINT);
                        }
                    } else 
                    echo json_encode([
                        'user' => 'Nedovoljno jaka lozinka! Lozinka mora sadržati najmenje: 1 karakter, 1 malo/veliko slovo i 1 broj.'
                    ], JSON_PRETTY_PRINT);
                } else
                    echo json_encode([
                        'user' => 'Lozinka i potvrda lozinke se ne poklapaju. Molimo pokušajte ponovo.'
                    ], JSON_PRETTY_PRINT);
                
            } else echo json_encode(['user' => 'Pogrešana trenutna lozinka! Molimo Vas da unesete važeću lozinku'], JSON_PRETTY_PRINT);
        } else echo json_encode(['user' => 'Nije pronađen korisnik, molimo da nas kontaktirate.'], JSON_PRETTY_PRINT);
        
    }

    // Reset Password
    public function resetPassword() 
    {
        $token = bin2hex(random_bytes(16));
        $token_hash = hash("sha256", $token);

        $expiry = date("Y-m-d H:i:s", time() + 60 * 31);

        $sql = "UPDATE users SET 
        reset_token_hash = :token, reset_token_expires = :expiry
        WHERE email = :email"
        ;
        $stmt = $this->db->prepare($sql);

        $stmt->bindParam(':token', $token_hash);
        $stmt->bindParam(':expiry', $expiry);
        $stmt->bindParam(':email', $this->email);

        try {
            if($stmt->execute()) {
                echo json_encode(['token' => $token_hash]);
                if($stmt->rowCount() == 1) {

                    $mail = new PHPMailer(true);
                    $mail->SMTPDebug = SMTP::DEBUG_SERVER;
                    $mail->isSMTP();
                    $mail->SMTPAuth = true;

                    $mail->Host = "smtp.gmail.com";
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                    $mail->Port = 465;
                    $mail->Username = $_ENV["SMTP_USER"];
                    $mail->Password = $_ENV["SMTP_PASS"];

                    $mail->setFrom("noreply-kombiprevoz@gmail.com", "Bojan");
                    $mail->addAddress($this->email, $this->name);
                    $mail->isHTML(true);
                    $mail->Subject = "Password Reset";
                    $mail->Body = <<<END

                    Kliknite na link: http://localhost:5173/password-reset?token=$token

                    END;

                    try {
                        $mail->send();
                        echo json_encode(['user' => 'Link je upravo poslat na Vašu email adresu. Molimo proverite Vaš email!']);
                    } catch (Exception $e) {
                        echo json_encode([
                            'user' => 'Došlo je do greške!',
                            'msg' => $mail->ErrorInfo
                        ]);
                    }
                    //return $mail; // sbps uiqu hdmt besz
                } else
                echo json_encode([
                    'user' => 'Link je upravo poslat na Vašu email adresu. Molimo proverite Vaš email!',
                    'msg' => 'Korisnik nije pronađen !!!'
                ]);
            }
        } catch (PDOException $e) {
            echo json_encode([
                'user' => 'Došlo je do greške! Link nije poslat.',
                'msg' => $e->getMessage()
            ], JSON_PRETTY_PRINT);
        }
    }

    public function processResetPassword()
    {
        if($this->checkToken($this->token)) {
            if(Validator::validatePassword($this->new_pass)) {
                if($this->new_pass == $this->new_pass_confirm) {
                    $sql = "UPDATE users SET
                            pass = :pass, reset_token_hash = NULL, reset_token_expires = NULL
                            WHERE id = :id"
                    ;
                    $stmt = $this->db->prepare($sql);
                    $hashed = password_hash($this->new_pass, PASSWORD_DEFAULT);
                    $stmt->bindParam(':pass', $hashed);
                    $stmt->bindParam(':id', $this->id);

                    try {
                        if($stmt->execute()) {
                            echo json_encode([
                                'user' => 200,
                                'msg' => "Poštovani/a {$this->name} spešno ste izmenili lozinku. Sada možete da se ulogujete."
                            ], JSON_PRETTY_PRINT);
                        }
                    } catch (PDOException $e) {
                        echo json_encode([
                        'user' => 'Došlo je do greške pri konekciji.',
                        'msg' => $e->getMessage()
                    ], JSON_PRETTY_PRINT);
                    }
                } else
                echo json_encode([
                    'user' => 404,
                    'msg' => 'Vaša nova lozinka mora da se podudara sa potvrdom lozinke. Molmo proverite još jednom i pošaljite ponovo!'
                ], JSON_PRETTY_PRINT);
            } else
            echo json_encode([
                'user' => 404,
                'msg' => 'Lozinka nije validna! Lozinka obavezno mora da sadrži najmanje: 8 karaktera, 1 veliko/malo slovo, 1 broj i 1 specijalni karakter.'
            ], JSON_PRETTY_PRINT);
        }
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
    "users": true,
    "user": {
        "id": null, 
        "name": "Bojan Djurdjevic",
        "email": "pininfarina164@gmail.com",
        "pass": "Ljubavsonmojija!369",
        "pass_confirm": "",
        "city": "Sremska Kamenica",
        "address": "Gavrila Principa 6",
        "phone": "0641178898"
    },
    "new_pass": {
        "password": "Ljubavsonmojija!369",
        "confirmation_pass": "Ljubavsonmojija!369"
    },
    "all": null,
    "byID": null,
    "byEmail": null,
    "byName": null,
    "byCity": null,
    "signin": null,
    "login": null,
    "logout": null,
    "updateProfile": null,
    "updatePass": null,
    "delete": null,
    "restore": null,
    "resetPass": null,
    "token": null

    ------------------------------------------------

    "pass": "Ljubavsonmojija!369",
    "password": "EniBaneni!123",
    Valentina - LjubavicBuljavi!123

    --------------------------------------

    "user_id": 10,
    "user": {
        "id": 10,
        "email": "pininfarina164@gmail.com"
    },
    "orders": {
        "create": {
            "tour_id": 1,
            "user_id": 10,
            "places": 2,
            "add_from": "Ise Bajića 9",
            "add_to": "Stipice Jelavića 15",
            "date": "2025-07-25",
            "price": null
        },
        "ord_code": null
    }
*/

?>