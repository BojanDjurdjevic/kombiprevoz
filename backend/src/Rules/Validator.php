<?php
/*
namespace Rules;

use Helpers\Logger;
use Models\Order;
use PDO;
use PDOException;

if (!defined('APP_ACCESS')) {
    http_response_code(403);
    die('Direct access forbidden');
}

class Validator {
    public static function validateString($str) 
    {
        if (mb_strlen($str, 'UTF-8') < 2 || mb_strlen($str, 'UTF-8') > 100) {
            return false;
        }

        if (!preg_match("/^[\p{L}\s'-]+$/u", $str)) {
            return false;
        }

        return true;
    }
+/
    public static function validateCode($code) {
        $mycode = strtoupper(trim((string) $code)); /* 
        if (!preg_match('/^\d{7}KP$/', $mycode)) {
            return false;
        } else return true; */ /*-------------------
        if(empty($mycode)) return false;
        return preg_match('/^\d{7}KP$/', $mycode) === 1;
    }

    public static function validatePassword($str) 
    {
        if(strlen($str)<6 or strlen($str)>21) return false;
        $passRe = "/(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?=.*[^a-zA-Z0-9])(?!.*\s).{6,21}/";
        if(preg_match($passRe, $str)) {
            return true;
        } else
        return false;
    }

    public static function isSuper()
    {
        if(isset($_SESSION['user']['status']) && $_SESSION['user']['status'] == 'Superadmin') return true;
        else return false;
    }
    public static function isAdmin()
    {
        if(isset($_SESSION['user']['status']) && $_SESSION['user']['status'] == 'Admin') return true;
        else return false;
    }
    public static function isUser()
    {
        if(isset($_SESSION['user']['status']) && $_SESSION['user']['status'] == 'User') return true;
        else return false;
    }
    public static function isDriver()
    {
        if(isset($_SESSION['user']['status']) && $_SESSION['user']['status'] == 'Driver') return true;
        else return false;
    }

    // Current user is demo
    public static function isDemo() 
    {
        return !empty($_SESSION['user']['is_demo']);
    }

    // User whos profile will be changed (or order) is demo
    public static function custIsDemo($userID, $db)
    {
        $sql = "SELECT is_demo from users WHERE id = :id";
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':id', $userID);

        try {
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_OBJ);

            if($user) {
                return (bool) $user->is_demo;
            } else {
                return false;
            }
        } catch (PDOException $e) {
            Logger::error('Failed to fetch customer-user in custIsDemo()', [
                'error' => $e->getMessage(),
                'file' => __FILE__,
                'line' => __LINE__
            ]);
            return true;
        }
    }

    public static function tourIsDemo($tourID, $db) 
    {
        $sql = "SELECT is_demo from tours WHERE id = :id";
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':id', $tourID);

        try {
            $stmt->execute();
            $tour = $stmt->fetch(PDO::FETCH_OBJ);
            return $tour ? (bool) $tour->is_demo : false;
            
        } catch (PDOException $e) {
            Logger::error('Failed to fetch demo tour in tourIsDemo()', [
                'error' => $e->getMessage(),
                'file' => __FILE__,
                'line' => __LINE__
            ]);
            return true;
        }
    }

    public static function isOrderOfDemoUser($order_itemsID, $db)
    {
        $order = new Order($db);
        $order->getFromDB($order_itemsID);
        if(!$order->user_id) return false;

        return self::custIsDemo($order->user_id, $db);
    }

    public static function mailerTemplate($html, $code, ? string $name)
    {
        $html = str_replace("{{ code }}", $code, $html);
        if($name) $html = str_replace("{{ name }}", $name, $html);

        return $html;
    }
    public static function mailerDriverTemplate($order, $name, $places, $address, $city, $add_to, $city_to, $date, $time, $price, $tel) 
    {
        $formated = date_create($date);
        $d = date("d.m.Y", date_timestamp_get($formated));
        $html = "
            <div>
                <h2>Rezervacija <span>{{ order }}</span>  </h2>
                <br>
                <p>Ime glavnog putnika: <span>{{ name }}</span> </p>
                <br>
                <p>Broj rezervisanih mesta: <span>{{ places }}</span> </p>
                <br>
                <p>Adresa polaska: <span>{{ address }}, {{ city }}</span>  </p>
                <br>
                <p>Adresa dolaska: <span>{{ add_to }}, {{ city_to }}</span>  </p>
                <br>
                <p>Vreme polaska: <span>{{ date }} u {{ time }} sati</span> </p>
                <br>
                <p>Cena: <span>{{ price }} EUR</span> </p>
                <br>
                <p>Telefon putnika: <span>{{ tel }}</span> </p>
            </div>
        ";

        $html = str_replace("{{ order }}", $order, $html);
        $html = str_replace("{{ name }}", $name, $html);
        $html = str_replace("{{ places }}", $places, $html);
        $html = str_replace("{{ address }}", $address, $html);
        $html = str_replace("{{ city }}", $city, $html);
        $html = str_replace("{{ add_to }}", $add_to, $html);
        $html = str_replace("{{ city_to }}", $city_to, $html);
        $html = str_replace("{{ date }}", $d, $html);
        $html = str_replace("{{ time }}", $time, $html);
        $html = str_replace("{{ price }}", $price, $html);
        $html = str_replace("{{ tel }}", $tel, $html);

        return $html;
    }

    public static function cleanParams(array $params): array
    {
        $clean = [];

        foreach($params as $key => $value) {
            if(is_numeric($value)) {
                $clean[$key] = $value;
            } elseif(is_string($value)) {
                $clean[$key] = htmlspecialchars(strip_tags($value), ENT_QUOTES, 'UTF-8');
            } elseif(is_array($value)) {
                $clean[$key] = self::cleanParams($value);
            } else {
                $clean[$key] = htmlspecialchars(strip_tags(strval($value)), ENT_QUOTES, 'UTF-8');
            }
        }

        return $clean;
    }

    public static function formatDateForFront($date) {
        $formated = date_create($date);
        $d = date("d.m.Y", date_timestamp_get($formated));
        return $d;
    }
}

?> -------------------*/
// REFACOR CODE

namespace Rules;

use Helpers\Logger;
use Models\Order;
use PDO;
use PDOException;

if (!defined('APP_ACCESS')) {
    http_response_code(403);
    die('Direct access forbidden');
}

class Validator {
    
    // ======================== STRING VALIDATION ========================
    
    public static function validateString($str, $min = 2, $max = 100) 
    {
        $length = mb_strlen($str, 'UTF-8');
        
        if ($length < $min || $length > $max) {
            return false;
        }

        // Dozvoljava Unicode slova, razmake, apostrofe i crtice
        if (!preg_match("/^[\p{L}\s'\-]+$/u", $str)) {
            return false;
        }

        return true;
    }

    // ======================== CODE VALIDATION ========================
    
    public static function validateCode($code) 
    {
        if (empty($code)) {
            return false;
        }
        
        $mycode = strtoupper(trim((string) $code));
        
        // Format: 7 cifara + "KP"
        return preg_match('/^\d{7}KP$/', $mycode) === 1;
    }

    // ======================== PASSWORD VALIDATION ========================
    
    public static function validatePassword($str) 
    {
        $length = strlen($str);
        
        if ($length < 8 || $length > 128) {
            return false;
        }

        // Mora sadržati:
        // - Najmanje jedno malo slovo
        // - Najmanje jedno veliko slovo
        // - Najmanje jedan broj
        // - Najmanje jedan specijalni karakter
        $pattern = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&#^()_\-+=[\]{};:\'",.<>\/\\|`~])[A-Za-z\d@$!%*?&#^()_\-+=[\]{};:\'",.<>\/\\|`~]{8,128}$/';
        
        return preg_match($pattern, $str) === 1;
    }

    // ======================== PHONE VALIDATION ========================
    
    public static function validatePhone($phone) 
    {
        // Srpski format: 06X-XXX-XXXX ili 06XXXXXXXX
        $cleaned = preg_replace('/[\s\-]/', '', $phone);
        
        return preg_match('/^06\d{7,8}$/', $cleaned) === 1;
    }

    // ======================== ROLE CHECKS ========================
    
    public static function isSuper()
    {
        return isset($_SESSION['user']['status']) && $_SESSION['user']['status'] === 'Superadmin';
    }
    
    public static function isAdmin()
    {
        return isset($_SESSION['user']['status']) && $_SESSION['user']['status'] === 'Admin';
    }
    
    public static function isUser()
    {
        return isset($_SESSION['user']['status']) && $_SESSION['user']['status'] === 'User';
    }
    
    public static function isDriver()
    {
        return isset($_SESSION['user']['status']) && $_SESSION['user']['status'] === 'Driver';
    }

    public static function hasRole($role)
    {
        $validRoles = ['Superadmin', 'Admin', 'User', 'Driver'];
        
        if (!in_array($role, $validRoles, true)) {
            return false;
        }
        
        return isset($_SESSION['user']['status']) && $_SESSION['user']['status'] === $role;
    }

    // ======================== DEMO CHECKS ========================
    
    public static function isDemo() 
    {
        return !empty($_SESSION['user']['is_demo']);
    }

    public static function custIsDemo($userID, $db)
    {
        $sql = "SELECT is_demo FROM users WHERE id = :id AND deleted = 0";
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':id', $userID, PDO::PARAM_INT);

        try {
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_OBJ);

            return $user ? (bool) $user->is_demo : false;
            
        } catch (PDOException $e) {
            Logger::error('Failed to fetch customer in custIsDemo()', [
                'user_id' => $userID,
                'error' => $e->getMessage(),
                'file' => __FILE__,
                'line' => __LINE__
            ]);
            
            return true;
        }
    }

    public static function tourIsDemo($tourID, $db) 
    {
        $sql = "SELECT is_demo FROM tours WHERE id = :id AND deleted = 0";
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':id', $tourID, PDO::PARAM_INT);

        try {
            $stmt->execute();
            $tour = $stmt->fetch(PDO::FETCH_OBJ);
            
            return $tour ? (bool) $tour->is_demo : false;
            
        } catch (PDOException $e) {
            Logger::error('Failed to fetch tour in tourIsDemo()', [
                'tour_id' => $tourID,
                'error' => $e->getMessage(),
                'file' => __FILE__,
                'line' => __LINE__
            ]);
            
            return true;
        }
    }

    public static function isOrderOfDemoUser($order_itemsID, $db)
    {
        $order = new Order($db);
        $order->getFromDB($order_itemsID);
        
        if (!$order->user_id) {
            return false;
        }

        return self::custIsDemo($order->user_id, $db);
    }

    // ======================== INPUT SANITIZATION ========================

    public static function sanitizeForOutput($value)
    {
        if (is_numeric($value)) {
            return $value;
        }
        
        if (is_string($value)) {
            return htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }
        
        if (is_array($value)) {
            return array_map([self::class, 'sanitizeForOutput'], $value);
        }
        
        return $value;
    }


    public static function cleanParams(array $params): array
    {
        return array_map([self::class, 'sanitizeForOutput'], $params);
    }

    // ======================== EMAIL TEMPLATES ========================
    
    public static function mailerTemplate($html, $code, ?string $name)
    {
        $html = str_replace("{{ code }}", htmlspecialchars($code, ENT_QUOTES, 'UTF-8'), $html);
        
        if ($name) {
            $html = str_replace("{{ name }}", htmlspecialchars($name, ENT_QUOTES, 'UTF-8'), $html);
        }

        return $html;
    }

    public static function mailerDriverTemplate($order, $name, $places, $address, $city, $add_to, $city_to, $date, $time, $price, $tel) 
    {
        $formattedDate = date('d.m.Y', strtotime($date));
        
        $html = "
            <div>
                <h2>Rezervacija <span>{{ order }}</span></h2>
                <br>
                <p>Ime glavnog putnika: <span>{{ name }}</span></p>
                <br>
                <p>Broj rezervisanih mesta: <span>{{ places }}</span></p>
                <br>
                <p>Adresa polaska: <span>{{ address }}, {{ city }}</span></p>
                <br>
                <p>Adresa dolaska: <span>{{ add_to }}, {{ city_to }}</span></p>
                <br>
                <p>Vreme polaska: <span>{{ date }} u {{ time }} sati</span></p>
                <br>
                <p>Cena: <span>{{ price }} EUR</span></p>
                <br>
                <p>Telefon putnika: <span>{{ tel }}</span></p>
            </div>
        ";

        $replacements = [
            '{{ order }}' => htmlspecialchars($order, ENT_QUOTES, 'UTF-8'),
            '{{ name }}' => htmlspecialchars($name, ENT_QUOTES, 'UTF-8'),
            '{{ places }}' => (int)$places,
            '{{ address }}' => htmlspecialchars($address, ENT_QUOTES, 'UTF-8'),
            '{{ city }}' => htmlspecialchars($city, ENT_QUOTES, 'UTF-8'),
            '{{ add_to }}' => htmlspecialchars($add_to, ENT_QUOTES, 'UTF-8'),
            '{{ city_to }}' => htmlspecialchars($city_to, ENT_QUOTES, 'UTF-8'),
            '{{ date }}' => $formattedDate,
            '{{ time }}' => htmlspecialchars($time, ENT_QUOTES, 'UTF-8'),
            '{{ price }}' => number_format((float)$price, 2),
            '{{ tel }}' => htmlspecialchars($tel, ENT_QUOTES, 'UTF-8')
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $html);
    }

    // ======================== DATE FORMATTING ========================
    
    public static function formatDateForFront($date) 
    {
        $timestamp = strtotime($date);
        
        if ($timestamp === false) {
            Logger::error('Invalid date format in formatDateForFront', [
                'date' => $date,
                'file' => __FILE__,
                'line' => __LINE__
            ]);
            return '';
        }
        
        return date('d.m.Y', $timestamp);
    }

    // ======================== RATE LIMITING ========================
    
    /**
     * Jednostavna rate limiting zaštita (brute force)
     * Čuva podatke u session
     */
    public static function checkRateLimit($action, $maxAttempts = 5, $timeWindow = 300)
    {
        if (!isset($_SESSION['rate_limit'])) {
            $_SESSION['rate_limit'] = [];
        }

        $key = $action . '_' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
        $now = time();

        // Inicijalizuj ako ne postoji
        if (!isset($_SESSION['rate_limit'][$key])) {
            $_SESSION['rate_limit'][$key] = [
                'attempts' => 0,
                'first_attempt' => $now
            ];
        }

        $data = &$_SESSION['rate_limit'][$key];

        // Reset ako je prošao time window
        if ($now - $data['first_attempt'] > $timeWindow) {
            $data['attempts'] = 0;
            $data['first_attempt'] = $now;
        }

        // Provera limita
        if ($data['attempts'] >= $maxAttempts) {
            $remainingTime = $timeWindow - ($now - $data['first_attempt']);

            $context = [
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                'attempts' => $data['attempts']
            ];
            
            Logger::security("Rate limit exceeded for action: $action" . $context, 'HIGH');
            
            return [
                'allowed' => false,
                'remaining_time' => $remainingTime
            ];
        }

        $data['attempts']++;

        return [
            'allowed' => true,
            'attempts' => $data['attempts'],
            'max_attempts' => $maxAttempts
        ];
    }
}

?>