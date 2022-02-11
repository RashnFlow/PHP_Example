<?php


namespace classes;


use Exception;


class Headers
{
    static public function GetAuth() : string
    {
        $Token = explode(" ", self::GetHeaders()["authorization"]);
        
        if(mb_strtolower($Token[0]) != "bearer")
            throw new Exception("Authorization token invalid");
            
        return empty($Token[1]) ? "" : $Token[1];
    }


    static public function GetHeader(string $Name) : string
    {
        $Val = self::GetHeaders()[mb_strtolower($Name)];
        if(empty($Val))
            throw new Exception("Not found");
        return $Val;
    }


    static private function GetHeaders()
    {
        return array_change_key_case(getallheaders(), CASE_LOWER);
    }
}