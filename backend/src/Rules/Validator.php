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
}

?>