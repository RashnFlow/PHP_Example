<?php


namespace classes;

use models\Authentication;
use Throwable;

class Logger
{
    private const   LOGGER_SOCKET   = "tcp://localhost:1436";
    public const    LOGS_DIRECTORY  = ROOT . "/logs_dump";
    

    static private string   $SessionUid = "";
    static private bool     $Disable    = false;


    private function __construct() {}


    static public function Log(string $Type, string $Message, $Data = null)
    {
        if(self::$Disable)
            return;

        if(empty(self::$SessionUid))
            self::$SessionUid = str_replace(".", "", microtime(true));

        if(is_object($Data))
            $Data = serialize($Data);

        $User = Authentication::GetAuthUser();
        try
        {
            Socket::Send(self::LOGGER_SOCKET, ["Message" => $Message, "Type" => $Type, "SessionUid" => self::$SessionUid, "Data" => $Data, "UserId" => (!empty($User) ? $User->GetId() : null)]);
        }
        catch(Throwable $error) {
            self::$Disable = true;
        }
    }
}
