<?php


namespace classes;


class CSRF
{
    private function __construct() {}
    

    static public function Generate() : string
    {
        return md5("Whatsapp:".$_SERVER['REMOTE_ADDR'].$_SERVER['HTTP_USER_AGENT']);
    }

    
    static public function Check(?string $CSRFToken) : bool
    {
        return $CSRFToken == self::Generate();
    }
}