<?php


namespace models;

use classes\Typiser;
use Exception;


class DataBase
{
    static public function Connect()
    {
        if(empty($GLOBALS["DBConnect"]))
            $GLOBALS["DBConnect"] = pg_connect("host=" . DB_IP . " port=" . DB_PORT . " dbname=" . DB_NAME . " user=" . DB_USER . " password=" . DB_PASSWORD);
    }


    static public function Query(Query $Query) : array
    {
        if($Query->Type == 'QueryBuffer')
            $out = pg_query($GLOBALS["DBConnect"], $Query->Query);
        else
            $out = pg_query_params($GLOBALS["DBConnect"], $Query->Query, array_slice($Query->Parameters, 0, $Query->ParametersCountInQuery()));

        if($out === false)
            throw new Exception(pg_last_error($GLOBALS["DBConnect"]));
        
        $Result = [];
        while ($data = pg_fetch_object($out))
            $Result[] = self::ConvertOutValues($data);
        return $Result;
    }


    static public function Disconnect()
    {
        if(!empty($GLOBALS["DBConnect"]))
        {
            pg_close($GLOBALS["DBConnect"]);
            unset($GLOBALS["DBConnect"]);
        }
    }


    static private function ConvertOutValues(object $Out) : object
    {
        foreach($Out as &$obj)
        {
            if($obj == "t")
                $obj = true;
            else if($obj == "f")
                $obj = false;
            else
                $obj = Typiser::TypeConversion($obj);
        }
        return $Out;
    }


    static public function Prepare(string $Name, string $Query)
    {
        $out = pg_prepare($GLOBALS["DBConnect"], $Name, $Query);
        if($out === false)
            throw new Exception(pg_last_error($GLOBALS["DBConnect"]));
    }


    static public function Escape($Data) : string
    {
        if(is_array($Data))
            $Data = json_encode($Data);

        if(is_bool($Data))
            $Data = $Data ? 'true' : 'false';

        if(is_object($Data))
            $Data = serialize($Data);

        if(is_string($Data))
            $Data = "'" . str_replace("'", "''", $Data) . "'";

        if($Data === 0)
            $Data = "0";

        return $Data == null ? 'null' : $Data;
    }


    static public function EscapeArray(array $Array) : array
    {
        foreach($Array as &$obj)
        {
            if(is_array($obj))
                $obj = self::EscapeArray($Array);
            else
                $obj = DataBase::Escape($obj);
        }

        return $Array;
    }
}