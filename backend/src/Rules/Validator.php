<?php

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

    public static function validateCode($code) {
        $mycode = strtoupper(trim((string) $code)); /* 
        if (!preg_match('/^\d{7}KP$/', $mycode)) {
            return false;
        } else return true; */
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

?>