<?php


namespace classes;


class Cache
{
    private function __construct() {}


    static public function Set(string $Key, $Val)
    {
        if(empty($GLOBALS["Cache"])) $GLOBALS["Cache"] = [];

        $GLOBALS["Cache"][$Key] = $Val; 
    }


    /**
     * @return mixed
     */
    static public function Get(string $Key)
    {
        if(empty($GLOBALS["Cache"])) $GLOBALS["Cache"] = [];

        return $GLOBALS["Cache"][$Key]; 
    }
}