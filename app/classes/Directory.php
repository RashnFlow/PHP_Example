<?php


namespace classes;

use Exception;

class Directory
{
    private function __construct() {}


    static public function Create(string $Path, int $Rights = 0755)
    {
        if(!self::Exists($Path))
            mkdir($Path, $Rights);
    }


    static public function Delete(string $Path)
    {
        rmdir($Path);
    }


    static public function Exists(string $Path) : bool
    {
        return file_exists($Path);
    }


    static public function GetFiles(string $Path) : array
    {
        $Dir = scandir($Path);
        if($Dir === false)
            throw new Exception('Error get files');

        foreach($Dir as $key => $File)
            if($File == '.' || $File == '..')
                unset($Dir[$key]);
        asort($Dir);
        return $Dir;
    }
}