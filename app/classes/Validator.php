<?php


namespace classes;


use Exception;
use TypeError;
use views\PrintJson;


class Validator
{
    private function __construct() {}


    static public function IsValid(array $Parameters, array $Rules, $Response = true) : bool
    {
        foreach($Rules as $Rule)
        {
            if(!isset($Parameters[$Rule["Key"]]) && $Rule["IsNull"])
                return true;

            if(!isset($Parameters[$Rule["Key"]]))
            {
                if($Response)
                    PrintJson::OperationError(ParameterIsNull . ' "' . $Rule["Key"] . '"', REQUEST_FAILED);
                return false;
            }

            if(!empty($Rule["Type"]))
            {
                switch($Rule["Type"])
                {
                    case "string":
                        if(!is_string($Parameters[$Rule["Key"]]))
                        {
                            if($Response)
                                PrintJson::OperationError($Rule["Key"] .' '. DataTypeError . ' "' . $Rule["Type"] . '"', REQUEST_FAILED);
                            return false;
                        }
                        break;

                    case "int":
                        if(!is_int($Parameters[$Rule["Key"]]) && !preg_match("/^-{0,1}[0-9]+$/", $Parameters[$Rule["Key"]]))
                        {
                            if($Response)
                                PrintJson::OperationError($Rule["Key"] .' '. DataTypeError . ' "' . $Rule["Type"] . '"', REQUEST_FAILED);
                            return false;
                        }
                        break;

                    case "array":
                        if(!is_array($Parameters[$Rule["Key"]]))
                        {
                            if($Response)
                                PrintJson::OperationError($Rule["Key"] .' '. DataTypeError . ' "' . $Rule["Type"] . '"', REQUEST_FAILED);
                            return false;
                        }
                        break;

                    case "bool":
                        if(!is_bool($Parameters[$Rule["Key"]]))
                        {
                            if($Response)
                                PrintJson::OperationError($Rule["Key"] .' '. DataTypeError . ' "' . $Rule["Type"] . '"', REQUEST_FAILED);
                            return false;
                        }
                        break;

                    default:
                        throw new Exception("Unsupported data type");
                        break;
                }
            }

            if(isset($Rule["IntMax"]))
            {
                if((int)$Parameters[$Rule["Key"]] > (int)$Rule["IntMax"])
                {
                    if($Response)
                        PrintJson::OperationError($Rule["Key"] .' '. IntMax . ' "' . $Rule["IntMax"] . '"', REQUEST_FAILED);
                    return false;
                }
            }

            if(isset($Rule["IntMin"]))
            {
                if((int)$Parameters[$Rule["Key"]] < $Rule["IntMin"])
                {
                    if($Response)
                        PrintJson::OperationError($Rule["Key"] .' '. IntMin . ' "' . $Rule["IntMin"] . '"', REQUEST_FAILED);
                    return false;
                }
            }


            if(isset($Rule["StrMax"]))
            {
                if(strlen($Parameters[$Rule["Key"]]) > $Rule["StrMax"])
                {
                    if($Response)
                        PrintJson::OperationError($Rule["Key"] .' '. StrMax . ' "' . $Rule["StrMax"] . '"', REQUEST_FAILED);
                    return false;
                }
            }

            if(isset($Rule["StrMin"]))
            {
                if(strlen($Parameters[$Rule["Key"]]) < $Rule["StrMin"])
                {
                    if($Response)
                        PrintJson::OperationError($Rule["Key"] .' '. StrMin . ' "' . $Rule["StrMin"] . '"', REQUEST_FAILED);
                    return false;
                }
            }
        }

        return true;
    }


    static public function CheckCardNumber(string $CardNumber) : bool
    {
        if(preg_match("/(?<!\d)\d{16}(?!\d)|(?<!\d[ _-])(?<!\d)\d{4}(?:[_ -]\d{4}){3}(?![_ -]?\d)/", $CardNumber))
            return true;
        else
            return false;
    }


    static public function NormalizePhone(string $Phone) : string
    {
        $Phone = preg_replace("/[^\d]/", "", $Phone);
        $Phone = preg_replace("/^8/", "7", $Phone);

        if(mb_strlen($Phone) != 11)
            throw new Exception("Phone does not match format. Phone: $Phone", 400);
        return $Phone;
    }


    static public function PascalCaseToSnakeCase(string $String) : string
    {
        $String = Tools::MbLcfirst(trim($String));
        preg_match_all("/[A-ZА-Я]/u", $String, $Matches);
        foreach($Matches[0] as $obj)
            $String = str_replace($obj, "_" . Tools::MbLcfirst(trim($obj)), $String);

        return $String;
    }


    static public function SnakeCaseToPascalCase(string $String) : string
    {
        $Out = "";
        foreach(explode("_", trim($String)) as $obj)
            $Out .= Tools::MbUcfirst($obj);

        return $Out;
    }


    static public function ArrayKeyPascalCaseToSnakeCase(array $Array) : array
    {
        $Out = [];
        foreach($Array as $key => $obj)
            $Out[self::PascalCaseToSnakeCase($key)] = is_array($obj) ? self::ArrayKeyPascalCaseToSnakeCase($obj) : $obj;
        
        return $Out;
    }


    static public function ArrayKeySnakeCaseToPascalCase(array $Array) : array
    {
        $Out = [];
        foreach($Array as $key => $obj)
            $Out[self::SnakeCaseToPascalCase($key)] = is_array($obj) ? self::ArrayKeySnakeCaseToPascalCase($obj) : $obj;
        
        return $Out;
    }


    static public function TypeCheckValue(string $Type, $Val) : bool
    {
        if(!isset($Val))
        {
            if($Type[0] == "?")
                return true;
            throw new TypeError("Value cannot be null");
        }

        if($Type[0] == "?")
            $Type = ltrim($Type, "?");

        switch(mb_strtolower($Type))
        {
            case "int":
                if(!is_int($Val))
                    throw new TypeError("Does not match the int type");
                return true;
                break;

            case "string":
                if(!is_string($Val))
                    throw new TypeError("Does not match the string type");
                return true;
                break;

            case "array":
                if(!is_array($Val))
                    throw new TypeError("Does not match the array type");
                return true;
                break;

            case "bool":
                if(!is_bool($Val))
                    throw new TypeError("Does not match the bool type");
                return true;
                break;

            case "mixed":
                return true;
                break;

            default:
                if($Val instanceof $Type)
                    return true;

                throw new Exception("Unknown type: $Type");
            break;
        }
    }
}