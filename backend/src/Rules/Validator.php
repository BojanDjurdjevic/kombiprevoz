<?php

namespace Rules;

class Validator {
    public static function validateString($str) 
    {
        if(strlen($str) < 3) return false;
        $forbiden = array("=", ")", "(", "+", "-", "*", "/", "|");
        foreach($forbiden as $f) {
            if(strpos($str, $f) !== false) return false; 
            else return true;
        }
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
        if(isset($_SESSION['user_status']) && $_SESSION['user_status'] == 'Superadmin') return true;
        else return false;
    }
    public static function isAdmin()
    {
        if(isset($_SESSION['user_status']) && $_SESSION['user_status'] == 'Admin') return true;
        else return false;
    }
    public static function isUser()
    {
        if(isset($_SESSION['user_status']) && $_SESSION['user_status'] == 'User') return true;
        else return false;
    }
    public static function isDriver()
    {
        if(isset($_SESSION['user_status']) && $_SESSION['user_status'] == 'Driver') return true;
        else return false;
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
                <p>Telefon putnika: <span>{{ tel }} EUR</span> </p>
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
}

?>