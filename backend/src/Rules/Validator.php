<?php

namespace Rules;

class Validator {
    public static function validateString($str) 
    {
        if(strlen($str) < 3) return false;
        $forbiden = ["=", ")", "(", "+", "-", "*"];
        foreach($forbiden as $f) {
            if(strpos($str, $f) !== false) {
                return false;
            } else return true;
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
}

?>