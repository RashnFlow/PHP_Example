<?php


namespace classes;

use Exception;

class StaticResources
{
    static private string $ROOTImages = ROOT . "/resources/image/";


    private function __construct() {}


    static public function GetImage(string $FileName) : Resource
    {
        if(!File::Exists(self::$ROOTImages . $FileName) || preg_match("~[\\/]+~", $FileName))
            throw new Exception('Resource not found');
        return new Resource(self::$ROOTImages . $FileName);
    }


    static public function GetAllImages() : array
    {
        $Resources = [];
        foreach(Directory::GetFiles(self::$ROOTImages) as $File)
            if($File != '.' && $File != '..')
                $Resources[] = new Resource(self::$ROOTImages . $File);
        return $Resources;
    }
}