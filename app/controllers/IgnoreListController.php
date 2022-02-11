<?php


namespace controllers;

use classes\Validator;
use models\Authentication;
use models\IgnoreList;
use views\PrintJson;

class IgnoreListController
{
    public function ActionAddPhone(array $Parameters)
    {
        if(Validator::IsValid($Parameters["Post"], [
            ["Key" => "phone"],
            ["Key" => "is_amocrm", "IsNull" => true],
            ["Key" => "is_yclients", "IsNull" => true],
            ["Key" => "is_mass_sending", "IsNull" => true],
        ]))
        {
            if(empty($IgnoreList = IgnoreList::FindByUserId(Authentication::GetAuthUser()->GetId())))
            {
                $Parameters["Post"]["phone"] = Validator::NormalizePhone($Parameters["Post"]["phone"]);
                $IgnoreList = new IgnoreList();
                $IgnoreList->UserId = Authentication::GetAuthUser()->GetId();
                if($Parameters["Post"]["is_amocrm"])
                    $IgnoreList->IgnoreParameters["AmoCRM"][] = $Parameters["Post"]["phone"];
                if($Parameters["Post"]["is_yclients"])
                    $IgnoreList->IgnoreParameters["Yclients"][] = $Parameters["Post"]["phone"];
                if($Parameters["Post"]["is_mass_sending"])
                    $IgnoreList->IgnoreParameters["MassSending"][] = $Parameters["Post"]["phone"];
                $IgnoreList->Save();
                PrintJson::OperationSuccessful();
            }
            else
            {
                $Ignore = $IgnoreList->IgnoreParameters;

                foreach($Ignore as $Type => $Phones)
                {
                    if(in_array($Parameters["Post"]["phone"], $Phones))
                    {
                        PrintJson::OperationError(PhoneAlreadyIgnore, REQUEST_FAILED);
                        return;
                    }
                }

                if($Parameters["Post"]["is_amocrm"])
                    $IgnoreList->IgnoreParameters["AmoCRM"][] = $Parameters["Post"]["phone"];
                if($Parameters["Post"]["is_yclients"])
                    $IgnoreList->IgnoreParameters["Yclients"][] = $Parameters["Post"]["phone"];
                if($Parameters["Post"]["is_mass_sending"])
                    $IgnoreList->IgnoreParameters["MassSending"][] = $Parameters["Post"]["phone"];
                $IgnoreList->Save();
                PrintJson::OperationSuccessful();
            }
        }
    }


    public function ActionUpdatePhone(array $Parameters)
    {
        if(Validator::IsValid($Parameters["Post"], [
            ["Key" => "phone"],
            ["Key" => "is_amocrm"],
            ["Key" => "is_yclients"],
            ["Key" => "is_mass_sending"],
        ]))
        {
            if(!empty($IgnoreList = IgnoreList::FindByUserId(Authentication::GetAuthUser()->GetId())))
            {
                $Parameters["Post"]["phone"] = Validator::NormalizePhone($Parameters["Post"]["phone"]);
                $Ignore = $IgnoreList->IgnoreParameters;

                foreach($Ignore as $Type => $Phones)
                {
                    foreach($Phones as $Phone)
                    {  
                        if($Parameters["Post"]["phone"] == $Phone && $Type == "AmoCRM" && !$Parameters["Post"]["is_amocrm"])
                            unset($Ignore[$Type][key($Phones)]);
                        else if($Parameters["Post"]["phone"] == $Phone && $Type == "Yclients" && !$Parameters["Post"]["is_yclients"])
                            unset($Ignore[$Type][key($Phones)]);
                        else if($Parameters["Post"]["phone"] == $Phone && $Type == "MassSending" && !$Parameters["Post"]["is_mass_sending"])
                            unset($Ignore[$Type][key($Phones)]);
                        else
                            next($Phones);
                        
                    }
                    if(!in_array($Parameters["Post"]["phone"], $Phones) && $Type == "AmoCRM" && $Parameters["Post"]["is_amocrm"])
                        $Ignore["AmoCRM"][] = $Parameters["Post"]["phone"];
                    else if(!in_array($Parameters["Post"]["phone"], $Phones) && $Type == "Yclients" && $Parameters["Post"]["is_yclients"])
                        $Ignore["Yclients"][] = $Parameters["Post"]["phone"];
                    else if(!in_array($Parameters["Post"]["phone"], $Phones) && $Type == "MassSending" && $Parameters["Post"]["is_mass_sending"])
                        $Ignore["MassSending"][] = $Parameters["Post"]["phone"];
                    
                }
                $IgnoreList->IgnoreParameters = $Ignore;
                $IgnoreList->Save();
                PrintJson::OperationSuccessful();
            }
        }
    }


    public function ActionGetAllPhone(array $Parameters)
    {
        if(!empty($IgnoreList = IgnoreList::FindByUserId(Authentication::GetAuthUser()->GetId())))
        {
            $Out = [];
            $Ignore = $IgnoreList->IgnoreParameters;

                foreach($Ignore as $Type => $Phones)
                {
                    foreach($Phones as $Phone)
                    {
                        $Out[$Phone]['phone'] = $Phone;
                        if($Type == "AmoCRM")
                            $Out[$Phone]["is_amocrm"] = true;
                        else if($Type == "Yclients")
                            $Out[$Phone]["is_yclients"] = true;
                        else if($Type == "MassSending")
                            $Out[$Phone]["is_mass_sending"] = true;
                    }
                }
            PrintJson::OperationSuccessful(["phones" => $Out]);
        }
        else
            PrintJson::OperationError(IgnoreListNotFound, NOT_FOUND);
    }


    public function ActionGetPhone(array $Parameters)
    {
        if(Validator::IsValid($Parameters["Get"], [
            ["Key" => "phone"]
        ]))
        {
            if(!empty($IgnoreList = IgnoreList::FindByUserId(Authentication::GetAuthUser()->GetId())))
            {
                $Parameters["Get"]["phone"] = Validator::NormalizePhone($Parameters["Get"]["phone"]);
                $Ignore = $IgnoreList->IgnoreParameters;
                $Out = [];
                $Out["phone"] = $Parameters["Get"]["phone"];

                foreach($Ignore as $Type => $Phones)
                {
                    foreach($Phones as $Phone)
                    if($Parameters["Get"]["phone"] == $Phone && $Type == "AmoCRM")
                        $Out["is_amocrm"] = true;
                    else if($Parameters["Get"]["phone"] == $Phone && $Type == "Yclients")
                        $Out["is_yclients"] = true;
                    else if($Parameters["Get"]["phone"] == $Phone && $Type == "MassSending")
                        $Out["is_mass_sending"] = true;
                }
                PrintJson::OperationSuccessful($Out);
            }
            else
                PrintJson::OperationError(IgnoreListNotFound, NOT_FOUND);
        }
    }


    public function ActionDeleteIgnorePhone(array $Parameters)
    {
        if(Validator::IsValid($Parameters["Post"], [
            ["Key" => "phone"]
        ]))
        {
            if(!empty($IgnoreList = IgnoreList::FindByUserId(Authentication::GetAuthUser()->GetId())))
            {
                $Parameters["Post"]["phone"] = Validator::NormalizePhone($Parameters["Post"]["phone"]);
                $Ignore = $IgnoreList->IgnoreParameters;

                foreach($Ignore as $Type => $Phones)
                {
                    foreach($Phones as $Phone)
                    if($Parameters["Post"]["phone"] == $Phone)
                        unset($Ignore[$Type][key($Phones)]);
                    else
                        next($Phones);
                }
                $IgnoreList->IgnoreParameters = $Ignore;
                $IgnoreList->Save();
                PrintJson::OperationSuccessful();
            }
            else
                PrintJson::OperationError(IgnoreListNotFound, NOT_FOUND);
        }
    }
}