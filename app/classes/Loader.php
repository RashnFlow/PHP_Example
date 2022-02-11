<?php


namespace classes;


use Exception;


class Loader
{
    private function __construct() {}


    static public function LoadClass(string $Class)
    {
        $Class = ROOT . "/".str_replace("\\", "/", $Class).".php";

        if(file_exists($Class))
            include_once $Class;
        else
            throw new Exception("Class not found");
    }


    static public function LoadConfig()
    {
        $ConfigDir = ROOT . "/config/";
        foreach(scandir($ConfigDir) as $ConfigFile)
            if(file_exists($ConfigDir . $ConfigFile) && preg_match("/.php$/i", $ConfigFile) && !preg_match("/example/i", $ConfigFile))
                include_once $ConfigDir . $ConfigFile;
    }
}