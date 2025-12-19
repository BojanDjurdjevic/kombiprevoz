<?php

namespace Models;

use Helpers\Logger;
use PDO;
use PDOException;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;
use Rules\Validator;

if (!defined('APP_ACCESS')) {
    http_response_code(403);
    die('Direct access forbidden');
}

class User {
    public $id;
    public $name;
    public $email;
    public $pass;
    public $new_pass;
    public $new_pass_confirm;
    public $remember;
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

    // -------------------------  FUNCTIONS BEFORE ACTION --------------------------------- //

    // Check if it the User is logedin:
    public static function isLoged($db)
    {

        //$logs = isset($_SESSION['user']) ? new Logger(self::$db)->getUserLogs($_SESSION['user']['id']) : [];


        if(!isset($_SESSION['user']) && isset($_COOKIE['remember_me'])) {
            $sql = "SELECT * FROM users WHERE id = :id";
            $stmt = $db->prepare($sql);

            $stmt->bindParam(':id', $_COOKIE['remember_me']);

            try {
                if($stmt->execute()) {
                    $user = $stmt->fetch(PDO::FETCH_OBJ);

                    if($user) {
                        $splited = explode(" ", $user->name);
                        $arr = [];
                        foreach($splited as $s) {
                            array_push($arr, mb_strtoupper(mb_substr($s, 0,1, "UTF-8")));
                        }
                        $initials = implode("", $arr);

                        $_SESSION['user'] = [
                            'id' => $user->id,
                            'name' => $user->name,
                            'email' => $user->email,
                            'status' => $user->status,
                            'city' => $user->city,
                            'address' => $user->address,
                            'phone' => $user->phone,
                            'initials' => $initials
                        ];
                        /*
                        $logger = new Logger(self::$db);
                        $logs = $logger->getUserLogs($user->id); */
                    }
                }
            } catch (PDOException $e) {
                Logger::error("Database error in userUpdateByAdmin()", [
                            'user_id' => 'unknown',
                            'error' => $e->getMessage(),
                            'file' => __FILE__,
                            'line' => __LINE__
                        ]);

                http_response_code(500);
                echo json_encode(['error' => 'Došlo je do greške pri ažuriranju!'], JSON_UNESCAPED_UNICODE);
            }
        }
        
        if(isset($_SESSION['user'])) { /*
            if (empty($logs)) {
                $logger = new Logger(self::$db);
                $logs = $logger->getUserLogs($_SESSION['user']['id']);
            } */

            echo json_encode([
                'success' => true,
                'user' => $_SESSION['user']
            ], JSON_PRETTY_PRINT);
            return true;
        } else {
            http_response_code(422);
            echo json_encode([
                'error' => 'Korisnik nije prepoznat, molmo Vas da se ulogujete'
            ], JSON_PRETTY_PRINT);
            return false;
        } 
    }
    // Check if the User is owner of account
    public function isOwner()
    {
        if(isset($_SESSION['user']) && !empty($this->id) && $this->id == $_SESSION['user']['id']) return true;
        else return false;
    }

    public function getLogs() 
    {
        $logger = new Logger($this->db);
        return $logger->getUserLogs($this->id);
    }

    // -------------------------  FUNCTIONS AFTER ACTION --------------------------------- //

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
                    http_response_code(401);
                    echo json_encode([
                        'token' => 404,
                        'error' => 'Token nije pronađen. Molimo Vas da ponovo unesete email i kliknete dugme: Pošalji'
                    ], JSON_PRETTY_PRINT);
                    return false;
                }
                if(strtotime($user->reset_token_expires) <= time()) {
                    http_response_code(401);
                    echo json_encode([
                        'token' => 404,
                        'error' => 'Token je istekao. Molimo Vas da ponovo kliknete dugme: Pošalji novi link!'
                    ], JSON_PRETTY_PRINT);
                    return false;
                }

                $this->id = $user->id;
                $this->name = $user->name;
                $this->email = $user->email;
                /*
                echo json_encode([
                    'token' => 200,
                    'msg' => 'Token je u redu. Molimo Vas da izmenite lozinku.'
                ], JSON_PRETTY_PRINT); */
                return true;
            }
        } catch (PDOException $e) {
            Logger::error("Database error in userUpdateByAdmin()", [
                            'user_id' => $this->id,
                            'error' => $e->getMessage(),
                            'file' => __FILE__,
                            'line' => __LINE__
                        ]);

            http_response_code(500);
            echo json_encode(['error' => 'Došlo je do greške pri ažuriranju!'], JSON_UNESCAPED_UNICODE);
        }
    }

    // Email to the user:
    public function sendEmail($html, $code, $name, $subject, $output) 
    {
        $template = Validator::mailerTemplate($html, $code, $name);
        $mail = new PHPMailer(true);
        //$mail->SMTPDebug = SMTP::DEBUG_SERVER;
        $mail->isSMTP();
        $mail->SMTPAuth = true;

        $mail->Host = "smtp.gmail.com";
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = 465;
        $mail->Username = $_ENV["SMTP_USER"];
        $mail->Password = $_ENV["SMTP_PASS"];

        $mail->setFrom("noreply-kombiprevoz@gmail.com", "Bojan");
        $mail->addAddress($this->email, $this->name);

        $mail->CharSet = 'UTF-8';
        $mail->Encoding = 'base64';

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->setLanguage('sr');
        $mail->Body = <<<END

        $template

        END;

        try {
            $mail->send();
            http_response_code(200);
            echo json_encode([
                        'success' => true,
                        'msg' => $output
                    ], JSON_UNESCAPED_UNICODE);
        } catch (PDOException $e) {
            Logger::error('Failed email to user', [
                'DB_message'=> $e->getMessage(),
                'file' => __FILE__,
                'line' => __LINE__
            ]);
        }
    
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
            //echo json_encode(["user" => $user], JSON_PRETTY_PRINT);
            return $user;
        } else
        echo json_encode(["user" => 'Nema registrovanih korisnika sa poslatim ID-em.'], JSON_PRETTY_PRINT);
    }

    // By EMAIL
    public function getByEmail() 
    {
        if(filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            $sql = "SELECT id, name, email, status, city, address, phone 
            FROM users WHERE deleted = 0 and email = :email"
            ;
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(":email", $this->email);
            try {
                $stmt->execute();
                $user = $stmt->fetch(PDO::FETCH_OBJ);

                $logger = new Logger($this->db);
                $logs = $logger->getUserLogs($user->id);

                if($user) {
                    return [
                        'success' => true,
                        'user' => $user,
                        'logs'=> $logs
                    ];
                } else {
                    return [
                        'success' => false,
                        'error' => 'not_found',
                        'message' => "Korisnik sa email-om $this->email nije pronađen"
                    ];
                }
            } catch (PDOException $e) {
                Logger::error("Database error in userUpdateByAdmin()", [
                            'user_id' => $this->id,
                            'error' => $e->getMessage(),
                            'file' => __FILE__,
                            'line' => __LINE__
                ]);
                return [
                    'success' => false,
                    'error' => 'database_error',
                    'message' => 'Greška pri pristupu bazi podataka'
                ];
            }
        } else {
            return [
                'success' => false,
                'error' => 'invalid_email',
                'message' => 'Neispravan email format'
            ];
        }
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

    public function getAvailableDrivers($date)
    {
        $sql = "SELECT id, name, email, phone FROM users
                WHERE status = 'Driver'
                and id NOT IN (SELECT driver_id FROM order_items WHERE date = :date)
        ";
        $stmt = $this->db->prepare($sql);
        $date = htmlspecialchars(strip_tags($date), ENT_QUOTES);
        $stmt->bindParam(':date', $date);

        $drivers = [];

        try {
            if($stmt->execute()) {
                while($row = $stmt->fetch(PDO::FETCH_OBJ)) {
                    array_push($drivers, $row);
                }
                return $drivers;
            }
        } catch (PDOException $e) {
            Logger::error("Database error in userUpdateByAdmin()", [
                            'user_id' => $this->id,
                            'error' => $e->getMessage(),
                            'file' => __FILE__,
                            'line' => __LINE__
            ]);

            http_response_code(500);
            echo json_encode(['error' => 'Došlo je do greške pri ažuriranju!'], JSON_UNESCAPED_UNICODE);
        }
    }

    // -------------------------  POST --------------------------------- // 

    // SIGNIN - create new User
    public function create()
    {
        if(Validator::validateString($this->name) && Validator::validatePassword($this->pass)
            && filter_var($this->email, FILTER_VALIDATE_EMAIL) /* && Validator::validateString($this->address)
            && Validator::validateString($this->city) && filter_var($this->phone, FILTER_VALIDATE_INT) */) {
            $sql = "INSERT INTO users SET name = :name, email = :email, pass = :pass, status = :status,
                    city = :city, address = :address, phone = :phone"
            ;
            $stmt = $this->db->prepare($sql);

            $this->id = htmlspecialchars(strip_tags($this->id), ENT_QUOTES);
            $this->name = htmlspecialchars(strip_tags(trim($this->name)), ENT_QUOTES);
            $this->email = htmlspecialchars(strip_tags(trim($this->email)), ENT_QUOTES);
            //$this->pass = htmlspecialchars(strip_tags($this->pass), ENT_QUOTES);
            $this->status = htmlspecialchars(strip_tags('User'), ENT_QUOTES);
            $this->city = htmlspecialchars(strip_tags(trim($this->city)), ENT_QUOTES);
            $this->address = htmlspecialchars(strip_tags(trim($this->address)), ENT_QUOTES);
            $this->phone = htmlspecialchars(strip_tags(trim($this->phone)), ENT_QUOTES);

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
                    $this->id = $this->db->lastInsertId(); 
                    $sql = "SELECT * FROM users WHERE id = :id";
                    $stmt = $this->db->prepare($sql);
                    $stmt->bindParam(':id', $this->id);

                    try {
                        if($stmt->execute()) {
                            $user = $stmt->fetch(PDO::FETCH_OBJ);
                            if($user) {
                                session_regenerate_id();

                                $splited = explode(" ", $user->name);
                                $arr = [];
                                foreach($splited as $s) {
                                    array_push($arr, mb_strtoupper(mb_substr($s, 0,1, "UTF-8")));
                                }
                                $initials = implode("", $arr);

                                $_SESSION['user'] = [
                                    'id' => $user->id,
                                    'name' => $user->name,
                                    'email' => $user->email,
                                    'status' => $user->status,
                                    'city' => $user->city,
                                    'address' => $user->address,
                                    'phone' => $user->phone,
                                    'initials' => $initials
                                ];

                                if($this->remember) {
                                    setcookie('remember_token', $user->id, [
                                        'expires' => time() + (86400 * 30),
                                        'path' => "/",
                                        'secure' => false,
                                        'httponly' => true,
                                        'samesite' => 'Lax'
                                    ]);
                                }
                                $name = $_SESSION['user']['name'];
                                echo json_encode([
                                    'success' => true,
                                    'user' => $_SESSION['user'],
                                    'msg' => "Dobrodošli $name! Uspešno ste se registrovali." 
                                ], JSON_PRETTY_PRINT);
                            } else {
                                http_response_code(422);
                                echo json_encode([
                                    'error' => 'Novi korisnik je uspešno kreiran, ali nije ulogovan. Molimo Vas da se ulogujete!',
                                    'status' => 422
                                ]);
                            }
                        }
                    } catch (PDOException $e) {
                        Logger::error("Database error in userUpdateByAdmin()", [
                            'user_id' => '',
                            'error' => $e->getMessage(),
                            'file' => __FILE__,
                            'line' => __LINE__
                        ]);

                        http_response_code(500);
                        echo json_encode(['error' => 'Došlo je do greške pri ažuriranju!'], JSON_UNESCAPED_UNICODE);
                    }

                    
                }
            } catch (PDOException $e) {
                if($e->getCode() == 23000) {
                    http_response_code(422);
                    echo json_encode([
                        "error" => 'Email nije dostupan! Molimo Vas da probate sa drugim.'
                    ]);
                } else {
                    Logger::error("Database error in User -> create()", [
                            'user_id' => $this->id,
                            'error' => $e->getMessage(),
                            'file' => __FILE__,
                            'line' => __LINE__
                    ]);

                    http_response_code(500);
                    echo json_encode(['error' => 'Došlo je do greške pri kreiranju novog korisnika!'], JSON_UNESCAPED_UNICODE);
                }
            }
            
            
        } else {
            http_response_code(422);
            echo json_encode([
                'error' => 'Molimo Vas da pravilno unesete sve podatke!'
            ]);
        }
        
    }

    // Admin can choose the role while creating
    public function createByAdmin()
    {
        if(Validator::validateString($this->name) /* && Validator::validatePassword($this->pass) */
            && filter_var($this->email, FILTER_VALIDATE_EMAIL) && Validator::validateString($this->address)
            && Validator::validateString($this->city) && Validator::validateString($this->phone)) {
            
            if(Validator::validateString($this->status)) {
                if($this->status === 'Admin' || $this->status === 'Driver' || $this->status === 'User') {
                    $sql = "INSERT INTO users SET name = :name, email = :email, pass = :pass, status = :status,
                            city = :city, address = :address, phone = :phone"
                    ;
                    $stmt = $this->db->prepare($sql);
                    /*
                    $this->id = htmlspecialchars(strip_tags($this->id), ENT_QUOTES);
                    $this->name = htmlspecialchars(strip_tags($this->name), ENT_QUOTES);
                    $this->email = htmlspecialchars(strip_tags($this->email), ENT_QUOTES);
                    //$this->pass = htmlspecialchars(strip_tags($this->pass), ENT_QUOTES);
                    $this->status = htmlspecialchars(strip_tags($this->status), ENT_QUOTES);
                    $this->city = htmlspecialchars(strip_tags($this->city), ENT_QUOTES);
                    $this->address = htmlspecialchars(strip_tags($this->address), ENT_QUOTES);
                    $this->phone = htmlspecialchars(strip_tags($this->phone), ENT_QUOTES);
                    */
                    $generated = bin2hex(random_bytes(6));

                    $hashed = password_hash($generated, PASSWORD_DEFAULT);

                    $stmt->bindParam(':name', $this->name, PDO::PARAM_STR);
                    $stmt->bindParam(':email', $this->email);
                    $stmt->bindParam(':pass', $hashed);
                    $stmt->bindParam(':status', $this->status, PDO::PARAM_STR);
                    $stmt->bindParam(':city', $this->city, PDO::PARAM_STR);
                    $stmt->bindParam(':address', $this->address, PDO::PARAM_STR);
                    $stmt->bindParam(':phone', $this->phone, PDO::PARAM_STR);

                    
                    try {
                        if($stmt->execute()) {
                            $html = "
                                <p>Poštovani {{ name }}, </p><br>
                                <p>Vaš nalog je uspešno kreiran.</p>
                                <p>Vaš korisnički email je {$this->email} a Vaša lozinka je: {{ code }} </p>
                                <br>
                                <p>Molimo Vas da uđete na našu platformu, ulogujete se a zatim odmah promenite Vašu lozinku.</p>
                                <br>
                                <p>Da biste to uradili, kliknite na link: http://localhost:5173/login </p>
                                <br><br>
                                <p>Srdačan pozdrav od KombiPrevoz tima!</p>
                            ";
                            $this->sendEmail($html, $generated, $this->name, 'Kreiran Nalog',
                            "Email sa kredencijalima je poslat novom korisniku po imenu: $this->name !");
                            /*
                            echo json_encode(['user' => [
                                'msg' => 'Novi korisnik je uspešno kreiran.',
                                'user_id' => $this->db->lastInsertId()
                                ]
                            ]); */
                        }
                    } catch (PDOException $e) {
                        http_response_code(500);
                        if($e->getCode() == 23000) {
                            echo json_encode([
                                "error" => 'Email nije dostupan! Molimo Vas da probate sa drugim.'
                            ]);
                        } else {
                            Logger::error("Database error in User -> createByAdmin()", [
                                'user_id' => '',
                                'error' => $e->getMessage(),
                                'file' => __FILE__,
                                'line' => __LINE__
                            ]);

                            http_response_code(500);
                            echo json_encode(['error' => 'Došlo je do greške pri ažuriranju!'], JSON_UNESCAPED_UNICODE);
                        }
                    }
                } else {
                    http_response_code(403);
                    echo json_encode(['error' => 'Korisnik može imati samo jednu od 3 uloge!']);
                } 
            } else {
                http_response_code(422);
                echo json_encode(['error' => 'Molimo Vas da pravilno unesete podatke!']);
            } 
            
        } else {
            http_response_code(422);
            echo json_encode(['error' => 'Molimo Vas da pravilno unesete podatke!']);
        }
        
    }

    public function login()
    {
        //$logs = [];
        $find = "SELECT * FROM users WHERE email = :email AND deleted = 0";
        $stmt = $this->db->prepare($find);
        if(filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            //$this->email = htmlspecialchars(strip_tags($this->email));
            $stmt->bindParam(':email', $this->email);

            try {
                
                if($stmt->execute()) {
                    $user = $stmt->fetch(PDO::FETCH_OBJ);
                    
                    if($user) {
                        if(password_verify($this->pass, $user->pass)) {
                            
                            session_regenerate_id();

                            $splited = explode(" ", $user->name);
                            $arr = [];
                            
                            foreach($splited as $s) {
                                array_push($arr, mb_strtoupper(mb_substr($s, 0,1, "UTF-8")));
                            }
                            
                            $initials = implode("", $arr);
                            

                            $_SESSION['user'] = [
                                'id' => $user->id,
                                'name' => $user->name,
                                'email' => $user->email,
                                'status' => $user->status,
                                'city' => $user->city,
                                'address' => $user->address,
                                'phone' => $user->phone,
                                'initials' => $initials
                            ];

                            if($this->remember) {
                                setcookie('remember_token', $user->id, [
                                    'expires' => time() + (86400 * 30),
                                    'path' => "/",
                                    'secure' => false,
                                    'httponly' => true,
                                    'samesite' => 'Lax'
                                ]);
                            }
                            $name = $_SESSION['user']['name'];
                            /*
                            $logger = new Logger($this->db);
                            $logs = $logger->getUserLogs($user->id);
                            'logs' => $logs,
                            */
                            echo json_encode([
                                'success' => true,
                                'user' => $_SESSION['user'],
                                'msg' => "Dobrodošli nazad $name!" 
                            ]);
                            return;
                        } else {
                            http_response_code(401);
                            echo json_encode([
                                'success' => false,
                                'error' => 'Pogrešan email ili lozinka!'
                            ], JSON_PRETTY_PRINT);
                            Logger::security("Wrong email or password - failed login at login() [ User Email: $this->email ]", 'HIGH');
                            return;
                        }
                        
                    } else {
                        http_response_code(401);
                        echo json_encode([
                            'success' => false,
                            'error' => 'Pogrešan email ili lozinka!'
                        ], JSON_PRETTY_PRINT);
                        Logger::security("Wrong email or password - failed login at login() [ User Email: $this->email ]", 'HIGH');
                        return;
                    }
                } else {
                    http_response_code(500);
                    echo json_encode([
                        'error' => 'Došlo je do greške pri konektovanju na bazu!'
                    ], JSON_UNESCAPED_UNICODE);
                }
            } catch (PDOException $e) {
                Logger::error("Database error in User -> login()", [
                            'user_id' => $this->id,
                            'error' => $e->getMessage(),
                            'file' => __FILE__,
                            'line' => __LINE__
                        ]);

                        http_response_code(500);
                        echo json_encode(['error' => 'Došlo je do greške pri ažuriranju!'], JSON_UNESCAPED_UNICODE);
            }
        } else {
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'error' => 'Pogrešno upisan email! Molimo Vas da unesete validan email!'
            ], JSON_PRETTY_PRINT);
        }
    }

    public function logout()
    {
        $name = $_SESSION['user']['name'];
        session_unset();
        session_destroy();
        setcookie('remember_me', '', time() - 3600, '/', '', false, true);
        echo json_encode([
            'success' => true,
            'msg' => "Doviđenja $name"
        ], JSON_PRETTY_PRINT);
    }

    // -------------------------  PUT --------------------------------- // 

    // Update User general data:
    public function update() 
    {   
        $findSql = "SELECT * FROM users WHERE id = :id";

        $stmtF = $this->db->prepare($findSql);
        $stmtF->bindParam(':id', $this->id, PDO::PARAM_INT);
        $stmtF->execute();

        $currentUser = $stmtF->fetch(PDO::FETCH_OBJ);

        if(!$currentUser) {
            http_response_code(404);
            echo json_encode(['error' => 'Korisnik nije pronađen']);
            return;
        }

        if($this->isOwner() || Validator::isSuper() || Validator::isAdmin()) {
            $sql = "UPDATE users SET name = :name, email = :email, city = :city, address = :address, phone = :phone
                    WHERE id = :id and deleted = 0"
            ;
            $stmt = $this->db->prepare($sql);

            if(filter_var($this->email, FILTER_VALIDATE_EMAIL) && Validator::validateString($this->name)) {

                $stmt->bindParam(':id', $this->id, PDO::PARAM_INT);
                $stmt->bindParam(':name', $this->name);
                $stmt->bindParam(':email', $this->email);
                $stmt->bindParam(':city', $this->city);
                $stmt->bindParam(':address', $this->address);
                $stmt->bindParam(':phone', $this->phone);

                try {
                    if($stmt->execute()) {

                        $logger = new Logger($this->db);

                        if ($currentUser->name !== $this->name) {
                            $logger->logUserChange($this->id, $_SESSION['user']['id'],
                            'update', 'name', $currentUser->name, $this->name);
                        }
                        if ($currentUser->email !== $this->email) {
                            $logger->logUserChange($this->id, $_SESSION['user']['id'], 
                            'update', 'email', $currentUser->email, $this->email);
                        }

                        if ($currentUser->city !== $this->city) {
                            $logger->logUserChange($this->id, $_SESSION['user']['id'],
                            'update', 'city', $currentUser->city, $this->name);
                        }
                        if ($currentUser->address !== $this->address) {
                            $logger->logUserChange($this->id, $_SESSION['user']['id'], 
                            'update', 'address', $currentUser->address, $this->email);
                        }
                        if ($currentUser->phone !== $this->phone) {
                            $logger->logUserChange($this->id, $_SESSION['user']['id'], 
                            'update', 'phone', $currentUser->phone, $this->status);
                        }

                        $splited = explode(" ", $this->name);
                        $arr = [];
                        foreach($splited as $s) {
                            array_push($arr, mb_strtoupper(mb_substr($s, 0,1, "UTF-8")));
                        }
                        $initials = implode("", $arr);

                        $logedUser = [
                            'id' => $this->id,
                            'name' => $this->name,
                            'email' => $this->email,
                            'status' => $_SESSION['user']['status'],
                            'city' => $this->city,
                            "address" => $this->address,
                            'phone' => $this->phone,
                            'initials' => $initials
                        ];
                        $_SESSION['user'] = $logedUser;

                        http_response_code(200);
                        echo json_encode([
                            'success' => true,
                            'msg' => 'Uspešno ste ažurirali lične podatke.',
                            'user' => $logedUser
                        ]);
                    }
                } catch(PDOException $e) {
                    Logger::error("Database error in User update()", [
                        'user_id' => $this->id,
                        'error' => $e->getMessage(),
                        'file' => __FILE__,
                        'line' => __LINE__
                    ]);

                    http_response_code(500);
                    echo json_encode([
                        'error' => 'Došlo je do greške prilikom ažuriranja. Molimo obratite se našoj podršci!'
                    ]);
                }
                
            } else {
                http_response_code(422);
                echo json_encode(['error' => 'Nije moguće ažurirati profil. Molimo Vas da pravilno unesete podatke!']);
            }
        } else {
            http_response_code(401);
            echo json_encode(['error' => 'Niste autorizovani da ažurirate profil!']);
        } 
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
                                http_response_code(200);
                                echo json_encode([
                                    'success' => true,
                                    'msg' => 'Vaša lozinka je uspešno izmenjena!'
                                ]);
                            }
                        } catch (PDOException $e) {
                            Logger::error("Database error in User updatePassword()", [
                                'user_id' => $this->id,
                                'error' => $e->getMessage(),
                                'file' => __FILE__,
                                'line' => __LINE__
                            ]);

                            http_response_code(500);
                            echo json_encode(['error' => 'Došlo je do greške pri ažuriranju!'], JSON_UNESCAPED_UNICODE);
                            }
                    } else {
                        http_response_code(401);
                        echo json_encode([
                            'error' => 'Nedovoljno jaka lozinka! Lozinka mora sadržati najmenje: 1 karakter, 1 malo/veliko slovo i 1 broj.'
                        ], JSON_PRETTY_PRINT);
                    }
                } else {
                    http_response_code(401);
                    echo json_encode([
                        'error' => 'Lozinka i potvrda lozinke se ne poklapaju. Molimo pokušajte ponovo.'
                    ], JSON_PRETTY_PRINT);
                } 
            } else {
                http_response_code(401);
                echo json_encode(['error' => 'Pogrešana trenutna lozinka! Molimo Vas da unesete važeću lozinku'], JSON_PRETTY_PRINT);
            } 
        } else {
            http_response_code(401);
            echo json_encode(['error' => 'Nije pronađen korisnik, molimo da nas kontaktirate.'], JSON_PRETTY_PRINT);
        }       
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
                if($stmt->rowCount() == 1) {
                    $html = "
                        <p>Poštovani/a <? echo $this->name ?></p>
                        <br>
                        <p>Da biste promenili vašu zaboravljenu lozinku, molimo Vas da kliknete na link: </p>
                        <p>http://localhost:5173/password-reset?token={{ code }}</p>
                        <br>
                        <p>Srdačan pozdrav od KombiPrevoz tima!</p>
                    ";
                    $output = 'Link je upravo poslat na Vašu email adresu. Molimo proverite Vaš email!';
                    
                    $this->sendEmail($html, $token, $this->name, 'Ponistavanje Lozinke', $output);
                    
                    //return $mail; // sbps uiqu hdmt besz  
                } //else
                
            }
        } catch (PDOException $e) {
            Logger::error("Database error in User resetPassword()", [
                            'user_id' => $this->id,
                            'error' => $e->getMessage(),
                            'file' => __FILE__,
                            'line' => __LINE__
            ]);

            http_response_code(500);
            echo json_encode(['error' => 'Došlo je do greške pri ažuriranju!'], JSON_UNESCAPED_UNICODE);
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
                                'success' => true,
                                'msg' => "Poštovani/a $this->name uspešno ste izmenili lozinku. Sada možete da se ulogujete."
                            ], JSON_PRETTY_PRINT);
                        }
                    } catch (PDOException $e) {
                        Logger::error("Database error in User processResetPassword()", [
                            'user_id' => $this->id,
                            'error' => $e->getMessage(),
                            'file' => __FILE__,
                            'line' => __LINE__
                        ]);

                        http_response_code(500);
                        echo json_encode(['error' => 'Došlo je do greške pri ažuriranju!'], JSON_UNESCAPED_UNICODE);
                    }
                } else {
                    http_response_code(401);
                    echo json_encode([
                        'user' => 404,
                        'error' => 'Lozinka i potvrda lozinke se ne podudaraju, pokušajte ponovo.'
                    ], JSON_PRETTY_PRINT);
                }
            } else {
                http_response_code(401);
                echo json_encode([
                    'user' => 404,
                    'error' => 'Lozinka nije validna! Lozinka obavezno mora da sadrži najmanje: 8 karaktera, 1 veliko/malo slovo, 1 broj i 1 specijalni karakter.'
                ], JSON_PRETTY_PRINT);
            }
            
        }
    }

    public function userUpdateByAdmin() 
    {
        if(!(Validator::isAdmin() || Validator::isSuper())) {
            http_response_code(403);
            echo json_encode(['error' => 'Niste autorizovani da ažurirate profil!']);
            return;
        }
        if (!filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            http_response_code(422);
            echo json_encode(['error' => 'Neispravan email']);
            return;
        }

        if (!Validator::validateString($this->name)) {
            http_response_code(422);
            echo json_encode(['error' => 'Neispravno ime']);
            return;
        }

        $findSql = "SELECT * FROM users WHERE id = :id";

        $stmtF = $this->db->prepare($findSql);
        $stmtF->bindParam(':id', $this->id, PDO::PARAM_INT);
        $stmtF->execute();

        $currentUser = $stmtF->fetch(PDO::FETCH_OBJ);

        if(!$currentUser) {
            http_response_code(404);
            echo json_encode(['error' => 'Korisnik nije pronađen']);
            return;
        }

        if ($currentUser->id == $_SESSION['user']['id'] && $this->status !== $currentUser->status) {
            http_response_code(403);
            echo json_encode(['error' => 'Ne možete menjati svoj status!'], JSON_UNESCAPED_UNICODE);
            return;
        }

        if($currentUser->status === 'Superadmin' && !Validator::isSuper()) {
            http_response_code(403);
            echo json_encode(['error' => 'Niste autorizovani da ažurirate profil!']);
            return;
        }

        if ($this->status === 'Superadmin' && $currentUser->status !== 'Superadmin' && !Validator::isSuper()) {
            http_response_code(403);
            echo json_encode(['error' => 'Samo Super Admin može da dodeljuje Super Admin status!'], JSON_UNESCAPED_UNICODE);
            return;
        }

        $emailCheckSql = "SELECT id FROM users WHERE email = :email AND id != :id AND deleted = 0";
        $stmtEmail = $this->db->prepare($emailCheckSql);
        $stmtEmail->bindParam(':email', $this->email);
        $stmtEmail->bindParam(':id', $this->id, PDO::PARAM_INT);
        $stmtEmail->execute();

        if ($stmtEmail->fetch()) {
            http_response_code(422);
            echo json_encode(['error' => 'Email adresa je već zauzeta'], JSON_UNESCAPED_UNICODE);
            return;
        }
        
        $sql = "UPDATE users SET name = :name, email = :email, city = :city, address = :address, 
                phone = :phone, status = :status WHERE id = :id";

        $stmt = $this->db->prepare($sql);

        $stmt->bindParam(':id', $this->id, PDO::PARAM_INT);
        $stmt->bindParam(':name', $this->name);
        $stmt->bindParam(':email', $this->email);
        $stmt->bindParam(':city', $this->city);
        $stmt->bindParam(':address', $this->address);
        $stmt->bindParam(':phone', $this->phone);
        $stmt->bindParam(':status', $this->status);

        try {
            $stmt->execute();

            if ($stmt->rowCount() > 0) {

                $logger = new Logger($this->db);

                if ($currentUser->name !== $this->name) {
                    $logger->logUserChange($this->id, $_SESSION['user']['id'],
                    'update', 'name', $currentUser->name, $this->name);
                }
                if ($currentUser->email !== $this->email) {
                    $logger->logUserChange($this->id, $_SESSION['user']['id'], 
                    'update', 'email', $currentUser->email, $this->email);
                }
                if ($currentUser->status !== $this->status) {
                    $logger->logUserChange($this->id, $_SESSION['user']['id'], 
                    'status_change', 'status', $currentUser->status, $this->status);
                }

                if ($currentUser->city !== $this->city) {
                    $logger->logUserChange($this->id, $_SESSION['user']['id'],
                    'update', 'city', $currentUser->city, $this->city);
                }
                if ($currentUser->address !== $this->address) {
                    $logger->logUserChange($this->id, $_SESSION['user']['id'], 
                    'update', 'address', $currentUser->address, $this->address);
                }
                if ($currentUser->phone !== $this->phone) {
                    $logger->logUserChange($this->id, $_SESSION['user']['id'], 
                    'update', 'phone', $currentUser->phone, $this->phone);
                }

                http_response_code(200);
                echo json_encode([
                    'success' => true,
                    'msg' => "Korisnik $this->name je uspešno ažuriran"
                ], JSON_UNESCAPED_UNICODE);
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Korisnik nije pronađen'], JSON_UNESCAPED_UNICODE);
            }

        } catch(PDOException $e) {
            //error_log("Admin update failed for user {$this->id}: " . $e->getMessage());

            Logger::error("Database error in userUpdateByAdmin()", [
                'user_id' => $this->id,
                'error' => $e->getMessage(),
                'file' => __FILE__,
                'line' => __LINE__
            ]);

            http_response_code(500);
            echo json_encode(['error' => 'Greška pri ažuriranju'], JSON_UNESCAPED_UNICODE);
        }
    }

    public function changeRole() 
    {
        $sql = "UPDATE users SET status = :status WHERE id = :id";
        if(Validator::validateString($this->status)) {
            if($this->status === 'Admin' || $this->status === 'Driver' || $this->status === 'User') {
                $stmt = $this->db->prepare($sql);
                $this->status = htmlspecialchars(strip_tags($this->status), ENT_QUOTES);
                $this->id = htmlspecialchars(strip_tags($this->id), ENT_QUOTES);
                $stmt->bindParam(':status', $this->status);
                $stmt->bindParam(':id', $this->id);

                try {
                    if($stmt->execute()) {
                        echo json_encode(['user' => "Uspešno ste izmenili ulogu korisniku {$this->name} u {$this->status}"], JSON_PRETTY_PRINT);
                    } 
                } catch (PDOException $e) {
                    echo json_encode([
                        'user' => 'Došlo je do greške prilikom konekcije na bazu!',
                        'msg' => $e->getMessage()
                    ], JSON_PRETTY_PRINT);
                }
            } else echo json_encode(['user' => 'Korisnik može imati samo jednu od 3 uloge!']);
        } else echo json_encode(['user' => 'Nepravilno unesen podatak! Izaberite jednu od 3 uloge!']);
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
            Logger::error("Database error in User delete()", [
                            'user_id' => $this->id,
                            'error' => $e->getMessage(),
                            'file' => __FILE__,
                            'line' => __LINE__
            ]);

            http_response_code(500);
            echo json_encode(['error' => 'Došlo je do greške pri ažuriranju!'], JSON_UNESCAPED_UNICODE);
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
            Logger::error("Database error in userUpdateByAdmin()", [
                            'user_id' => $this->id,
                            'error' => $e->getMessage(),
                            'file' => __FILE__,
                            'line' => __LINE__
            ]);

            http_response_code(500);
            echo json_encode(['error' => 'Došlo je do greške pri ažuriranju!'], JSON_UNESCAPED_UNICODE);
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

    ---------------------------

    giuliano: Giuliano!999

    ---------------------------
    create ByAdmin
    "users": true,
    "user": {
        "id": null,
        "name": "Bojan Giuliano",
        "email": "bojan.giuliano@gmail.com",
        "pass": "Giuliano!999",
        "status": "Deiver",
        "city": "Novi Sad",
        "address": "Seljačkih Buna 29",
        "phone": "062640227"
    },
    "byAdmin": true,
    "role": null

    -----------------------

    "users": false,
    "user": {
        "id": 10,
        "name": "Valentina",
        "email": "pininfarina164@gmail.com",
        "pass": "Ljubavsonmojija!369",
        "status": "Driver",
        "city": "Novi Sad",
        "address": "Seljačkih Buna 29",
        "phone": "062640227"
    },
    "orders": {
        "selected": [
            {
                "id": 2,
                "tour_id": 1,
                "user_id": 12,
                "places": 2,
                "from_city": "Novi Sad",
                "pickup": "Kočićeva 9",
                "to_city": "Rijeka",
                "dropoff": "Zadarska 33",
                "date": "2025-07-15",
                "pickuptime": "06:45:00",
                "duration": 6,
                "price": 100,
                "code": "3693692KP",
                "voucher": "src/assets/pdfs/3693692KP.pdf",
                "user": "Valentina Djurdjevic",
                "email": "valentajndj@gmail.com",
                "phone": "0641178898"
            },
            {
                "id": 83,
                "tour_id": 1,
                "user_id": 10,
                "places": 2,
                "from_city": "Novi Sad",
                "pickup": "Gajeva 9",
                "to_city": "Rijeka",
                "dropoff": "Primorska 18",
                "date": "2025-07-15",
                "pickuptime": "06:45:00",
                "duration": 6,
                "price": 100,
                "code": "1016996KP",
                "voucher": "src/assets/pdfs/1016996KP.pdf",
                "user": "Bojan",
                "email": "pininfarina164@gmail.com",
                "phone": "062640273"
            }
        ],
        "driver": {
            "id": 15,
            "name": "Bojan Giuliano",
            "email": "bojan.giuliano@gmail.com",
            "phone": "062640227"
        }
    }
*/

?>