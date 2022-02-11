<?php


namespace classes;


use Exception;


class File
{
    private function __construct() {}


    static private function Write($FileName, $Text, $Flags = 0)
    {
        if(file_put_contents($FileName, $Text, $Flags) === false)
            throw new Exception("Write error");
        return true;
    }


    static public function WriteAllText(string $FileName, string $Text) : bool
    {
        return self::Write($FileName, $Text);
    }


    static public function AppendText(string $FileName, string $Text) : bool
    {
        return self::Write($FileName, $Text . "\n", FILE_APPEND);
    }


    static public function AppendLines(string $FileName, array $Lines) : bool
    {
        return self::AppendText($FileName, implode("\n", $Lines));
    }


    static public function WriteAllLines(string $FileName, array $Lines) : bool
    {
        return self::WriteAllText($FileName, implode("\n", $Lines));
    }


    static public function ReadAllText(string $FileName) : string
    {
        $Read = file_get_contents($FileName);
        if($Read === false || $Read === null) throw new Exception("Read error");
        return $Read;
    }


    static public function ReadAllLines(string $FileName) : array
    {
        return explode("\n", self::ReadAllText($FileName));
    }


    static public function Exists(string $FileName) : bool
    {
        return (bool)file_exists($FileName);
    }


    static public function Create(string $FileName)
    {
        if(!self::Exists($FileName)) self::WriteAllText($FileName, "");
    }


    static public function Delete(string $FileName)
    {
        unlink($FileName);
    }


    static public function Move(string $FileName, string $ToFileName)
    {
        rename($FileName, $ToFileName);
    }
}