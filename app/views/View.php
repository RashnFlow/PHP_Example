<?php


namespace views;

use classes\Log;
use classes\Logger;
use Exception;

class View
{
    private function __construct() {}


    static public function Print(string $Name, array $Parameters = [])
    {
        echo self::Generate($Name, $Parameters);
    }


    static public function Generate(string $Name, array $Parameters = []) : ?string
    {
        Logger::Log(Log::TYPE_INFO, "Генерация шаблона ответа. Name: $Name;", $Parameters);

        $FileName = ROOT . "/views/$Name.php";
        
        if(!file_exists($FileName)) throw new Exception('The file "' . $Name . '" does not exist');

        ob_start();

        require $FileName;

        $Bufer = ob_get_contents();
        ob_end_clean();
        
        return $Bufer;
    }
}