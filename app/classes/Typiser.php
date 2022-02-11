<?php


namespace classes;


class Typiser
{
    static public function TypeConversion($Field)
    {
        if(is_string($Field))
        {
            if(preg_match("/^true$/", $Field))
                $Field = true;
            else if(preg_match("/^false$/", $Field))
                $Field = false;
            else if(preg_match("/^-*\d+\.\d+$/", $Field))
                $Field = (float)$Field;
            else if(preg_match("/^-*\d{1,19}$/", $Field))
                $Field = (int)$Field;
            else if(empty($Field) || $Field =="")
                $Field = null;
        }
        return $Field;
    }
}