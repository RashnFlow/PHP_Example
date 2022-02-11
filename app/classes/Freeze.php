<?php


namespace classes;


use Exception;
use Throwable;

class Freeze
{
    private const FREEZE_SOCKET = "tcp://localhost:3120";


    private function __construct() {}


    static public function Wait(string $Key)
    {
        if(!is_array($GLOBALS["Freeze"])) $GLOBALS["Freeze"] = [];
        if(array_search($Key, $GLOBALS["Freeze"]) !== false) return;

        set_time_limit(0);
        while(true)
        {
            try
            {
                if(json_decode(Socket::SendAwaitResponse(self::FREEZE_SOCKET, ["command" => "check", "uid" => $Key]), true)["check"] != true)
                    break;
            }
            catch(Exception $error) {}
            sleep(rand(1, 10));
        }
    }


    static public function SetProgress(string $Key)
    {
        try
        {
            if(!is_array($GLOBALS["Freeze"])) $GLOBALS["Freeze"] = [];
            if(array_search($Key, $GLOBALS["Freeze"]) !== false) return;

            set_time_limit(0);
            while(true)
            {
                $Res = json_decode(Socket::SendAwaitResponse(self::FREEZE_SOCKET, ["command" => "set", "uid" => $Key]), true);
                if($Res["status"] == "ok")
                    break;
                else
                    self::Wait($Key);
            }

            if(array_search($Key, $GLOBALS["Freeze"]) === false)
                $GLOBALS["Freeze"][] = $Key;
        }
        catch(Throwable $error){}
    }


    static public function ReplaceProgress(string $Key, string $ToKey)
    {
        if(!is_array($GLOBALS["Freeze"])) $GLOBALS["Freeze"] = [];
        if(array_search($Key, $GLOBALS["Freeze"]) === false)
            $GLOBALS["Freeze"][] = $Key;
        
        Socket::Send(self::FREEZE_SOCKET, ["command" => "replace", "uid" => $Key, "touid" => $ToKey]);
    }


    static public function DeleteProgress(string $Key)
    {
        $Find = array_search($Key, $GLOBALS["Freeze"]);
        if($Find !== false)
            unset($GLOBALS["Freeze"][$Find]);
        
        try
        {
            Socket::Send(self::FREEZE_SOCKET, ["command" => "delete", "uid" => $Key]);
        }
        catch(Throwable $error){}
    }


    static public function Clear()
    {
        foreach($GLOBALS["Freeze"] as $obj)
            self::DeleteProgress($obj);
    }
}