<?php


namespace models;

use classes\Http;
use Exception;

/**
 * @property int $Port {public get; public set;}
 * @property int $ProxyId {public get; private set;}
 * @property string $HostName {public get; public set;}
 * @property string $Host {public get; public set;}
 * @property string $Password {public get; public set;}
 * @property string $Login {public get; public set;}
 * @property string $Protocol {public get; public set;}
 * @property bool $IsActive {public get; public set;}
 * @property bool $IsBusy {public get; public set;}
 */
class Proxy extends Model
{
    public static ?string     $Table      = "Proxies";
    public static ?string     $PrimaryKey = "ProxyId";
    protected static array    $Properties = [
        "public ?string HostName {write false;}",
        "public string Host",
        "public int Port",
        "public ?string Password",
        "public ?string Login",
        "public bool IsActive",
        "public bool IsBusy",
        "public ?string Protocol"
    ];

    protected function OnCreate()
    {
        if($this->IsNew)
            $this->IsActive = true;
    }

    protected function OnUpdate() {}

    protected function OnDelete() {}

    protected function OnSave() {}


    protected function __setHostName($Value)
    {
        if(!preg_match("/^(?:(?:25[0-5]|2[0-4]\d|[01]?\d\d?)\.){3}(?:25[0-5]|2[0-4]\d|[01]?\d\d?)(:\d{1,5})$/", $Value) &&
            !preg_match("/^(([a-zA-Z0-9]|[a-zA-Z0-9][a-zA-Z0-9\-]*[a-zA-Z0-9])\.)*([a-zA-Z0-9]|[a-zA-Z0-9][a-zA-Z0-9\-]*[a-zA-Z0-9])\.([A-Za-z]|[A-Za-z][A-Za-z0-9\-]*[A-Za-z0-9])(:\d{1,5})$/", $Value))
            throw new Exception("Invalid format");
        $Temp = explode(":", $Value);
        $this->Host = $Temp[0];
        $this->Port = (int)$Temp[1];
        return null;
    }

    protected function __getHostName($Value)
    {
        return (!empty($this->Host) && !empty($this->Port)) ? ($this->Host . ":" . $this->Port) : null;
    }


    protected function __setHost($Value)
    {
        if(!preg_match("/^(?:(?:25[0-5]|2[0-4]\d|[01]?\d\d?)\.){3}(?:25[0-5]|2[0-4]\d|[01]?\d\d?)$/", $Value) &&
            !preg_match("/^(([a-zA-Z0-9]|[a-zA-Z0-9][a-zA-Z0-9\-]*[a-zA-Z0-9])\.)*([a-zA-Z0-9]|[a-zA-Z0-9][a-zA-Z0-9\-]*[a-zA-Z0-9])\.([A-Za-z]|[A-Za-z][A-Za-z0-9\-]*[A-Za-z0-9])$/", $Value))
            throw new Exception("Invalid format");
        return $Value;
    }


    protected function __setPort($Value)
    {
        if(!preg_match("/^\d{1,5}$/", $Value))
            throw new Exception("Invalid format");
        return $Value;
    }


    public function UrlCheck(string $Url) : bool
    {
        try
        {
            $Http = new Http();
            $Http->Proxy = $this;
            $Http->TimeOut = 30;
            $Http->SendGet($Url);
            return true;
        }
        catch(Exception $error)
        {
            return false;
        }
    }


    static public function Next(bool $CheckAccess = true) : Proxy
    {
        $Find = self::FindOne("\"IsActive\" = true and \"IsBusy\" = false", [], $CheckAccess);
        if(empty($Find))
            throw new Exception("Proxy not found");

        $Find->IsBusy = true;
        $Find->Save();
        return $Find;
    }
}