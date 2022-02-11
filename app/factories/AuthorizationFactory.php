<?php

namespace factories;

use Exception;
use models\Counter;


class AuthorizationFactory
{
    static private function GetCounterClass() : string
    {
        return Counter::class;
    }


    static public function GetCounterByAccessLevel($AccessLevel) : string
    {
        $MethodName = "";
        switch($AccessLevel)
        {
            case "SetDialog":
                $MethodName = "CountDialoguesPerMonth";
                break;

            case "SetWhatsapp":
                $MethodName = "CountWhatsapps";
                break;

            case "SetInstagram":
                $MethodName = "CountInstagrams";
                break;

            default:
                throw new Exception("AccessLevel invalid");
            break;
        }
        return self::GetCounterClass() . "::" . $MethodName;
    }
}
