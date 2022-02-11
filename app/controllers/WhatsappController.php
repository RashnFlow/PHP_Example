<?php


namespace controllers;


use classes\StaticResources;
use classes\Validator;
use Exception;
use models\Authentication;
use models\Folder;
use models\Proxy;
use models\Whatsapp;
use Throwable;
use views\PrintJson;
use views\View;


class WhatsappController
{
    public function ActionGetAllWhatsapps(array $Parameters)
    {
        $Out = [];
        foreach(Whatsapp::FindAllByUserId() as $Whatsapp)
            if(!$Whatsapp->GetIsDynamic())
                $Out[] = self::WhatsappToArray($Whatsapp);

        PrintJson::OperationSuccessful(["whatsapps" => $Out]);
    }


    public function ActionGetAllWhatsappActivities(array $Parameters)
    {
        PrintJson::OperationSuccessful(["activities" => Whatsapp::ACTIVITIES]);
    }


    public function ActionCreateWhatsapp(array $Parameters)
    {
        if(Validator::IsValid($Parameters["Post"], [
            ["Key" => "name"],
            ["Key" => "default_folder", "Type" => "int", "IsNull" => true],
        ]))
        {
            $ProxyId = null;
            try
            {
                $Count = 0;
                while(true)
                {
                    $Proxy = Proxy::Next();
                    if($Proxy->UrlCheck("https://web.whatsapp.com/runtime.31e465dd4d3fe0a0ad2a.js"))
                    {
                        $ProxyId = $Proxy->ProxyId;
                        break;
                    }
                    else
                    {
                        $Proxy->IsActive = false;
                        $Proxy->Save();
                    }
                    if($Count > 3)
                        throw new Exception("Proxy not found");
                    $Count++;
                }
            }
            catch(Exception $error)
            {
                PrintJson::OperationError(WhatsappErrorActivate, SERVER_ERROR);
                return;
            }


            //Обратная совместимость
            if(false)
            {
                $Whatsapp = new Whatsapp();
                $Whatsapp->SetUserId(Authentication::GetAuthUser()->GetId());
                $Whatsapp->SetName($Parameters["Post"]["name"]);
                if(!empty($ProxyId))
                    $Whatsapp->SetProxyId($ProxyId);

                if(!empty((int)$Parameters["Post"]["default_folder"]))
                    $Whatsapp->SetDefaultFolder((int)$Parameters["Post"]["default_folder"]);

                $Whatsapp->Save();

                try
                {
                    (new VenomBotController())->InitWhatsapp($Whatsapp);
                    PrintJson::OperationSuccessful();
                }
                catch(Throwable $error)
                {
                    if($error->getCode() == IS_EXISTS)
                    {
                        try
                        {
                            (new VenomBotController())->CloseWhatsapp($Whatsapp);
                        }
                        catch(Throwable $error){}
                        $Whatsapp->Delete();
                        PrintJson::OperationError(WhatsappIsExists, IS_EXISTS);
                    }
                    else
                    {
                        $Whatsapp->Delete();
                        PrintJson::OperationError(WhatsappErrorActivate, SERVER_ERROR);
                    }
                }
                return;
            }

            if(Whatsapp::CheckPhone($Parameters["Post"]["phone"]))
            {
                $Whatsapp = new Whatsapp();
                $Whatsapp->SetPhone($Parameters["Post"]["phone"]);
                $Whatsapp->SetUserId(Authentication::GetAuthUser()->GetId());
                $Whatsapp->SetName($Parameters["Post"]["name"]);
                $Whatsapp->SetStatusId(0);
                if(!empty($ProxyId))
                    $Whatsapp->SetProxyId($ProxyId);

                if(!empty((int)$Parameters["Post"]["default_folder"]))
                    $Whatsapp->SetDefaultFolder((int)$Parameters["Post"]["default_folder"]);

                $Whatsapp->Save();

                PrintJson::OperationSuccessful();
            }
            else
                PrintJson::OperationError(WhatsappIsExists, IS_EXISTS);
        }
    }


    public function ActionGetQR(array $Parameters)
    {
        if(Validator::IsValid($Parameters["Get"], [
            ["Key" => "whatsapp_id", "Type" => "int"]
        ]))
        {
            $Whatsapp = Whatsapp::FindById((int)$Parameters["Get"]["whatsapp_id"]);
            if(!empty($Whatsapp))
            {
                try
                {
                    $QR = (new VenomBotController())->GetQR($Whatsapp);
                    if(empty($QR))
                        PrintJson::OperationError(WhatsappQRNotFound, NOT_FOUND);
                    else
                        PrintJson::OperationSuccessful(["img" => $QR]);
                }
                catch(Exception $error)
                {
                    PrintJson::OperationError(WhatsappQRNotFound, NOT_FOUND);
                }
            }
            else
                PrintJson::OperationError(WhatsappNotFound, NOT_FOUND);
        }
    }


    public function ActionUpdateWhatsapp(array $Parameters)
    {
        if(Validator::IsValid($Parameters["Post"], [
            ["Key" => "whatsapp_id", "Type" => "int"],
            ["Key" => "default_folder", "Type" => "int", "IsNull" => true],
            ["Key" => "name"]
        ]))
        {
            $Whatsapp = Whatsapp::FindById((int)$Parameters["Post"]["whatsapp_id"]);
            if(!empty($Whatsapp))
            {
                if(!$Whatsapp->GetIsDynamic())
                {
                    $Whatsapp->SetName($Parameters["Post"]["name"]);
                    if(!empty((int)$Parameters["Post"]["default_folder"]))
                        $Whatsapp->SetDefaultFolder((int)$Parameters["Post"]["default_folder"]);
                    $Whatsapp->Save();

                    PrintJson::OperationSuccessful();
                }
                else
                    PrintJson::OperationError(WhatsappNotFound, NOT_FOUND);
            }
            else
                PrintJson::OperationError(WhatsappNotFound, NOT_FOUND);
        }
    }


    public function ActionDeleteWhatsapp(array $Parameters)
    {
        if(Validator::IsValid($Parameters["Post"], [
            ["Key" => "whatsapp_ids", "Type" => "array"]
        ]))
        {
            foreach($Parameters["Post"]["whatsapp_ids"] as $WhatsappId)
            {
                $Whatsapp = Whatsapp::FindById($WhatsappId);
                if(!empty($Whatsapp))
                {
                    if(!$Whatsapp->GetIsDynamic())
                    {
                        try
                        {
                            (new VenomBotController())->CloseWhatsapp($Whatsapp);
                        }
                        catch(Throwable $error){}
                        $Whatsapp->Delete();
                    }
                }
            }

            PrintJson::OperationSuccessful();
        }
    }


    public function ActionActivateWhatsapp(array $Parameters)
    {
        if(Validator::IsValid($Parameters["Post"], [
            ["Key" => "whatsapp_id", "Type" => "int"]
        ]))
        {
            $Whatsapp = Whatsapp::FindById((int)$Parameters["Post"]["whatsapp_id"]);
            if(!empty($Whatsapp))
            {
                if(!$Whatsapp->GetIsDynamic())
                {
                    try
                    {
                        (new VenomBotController())->ActivateWhatsapp($Whatsapp);
                        PrintJson::OperationSuccessful();
                    }
                    catch(Exception $error)
                    {
                        PrintJson::OperationError(WhatsappErrorActivate, SERVER_ERROR);
                    }
                }
                else
                    PrintJson::OperationError(WhatsappNotFound, NOT_FOUND);
            }
            else
                PrintJson::OperationError(WhatsappNotFound, NOT_FOUND);
        }
    }


    public function ActionSynchronizationWhatsapp(array $Parameters)
    {
        if(Validator::IsValid($Parameters["Post"], [
            ["Key" => "whatsapp_id", "Type" => "int"]
        ]))
        {
            $Whatsapp = Whatsapp::FindById((int)$Parameters["Post"]["whatsapp_id"]);
            if(!empty($Whatsapp))
            {
                if(!$Whatsapp->GetIsDynamic())
                {
                    (new VenomBotController())->ExportMessages($Whatsapp);
                    PrintJson::OperationSuccessful();
                }
                else
                    PrintJson::OperationError(WhatsappNotFound, NOT_FOUND);
            }
            else
                PrintJson::OperationError(WhatsappNotFound, NOT_FOUND);
        }
    }


    public function ActionGetWhatsappAvatar(array $Parameters)
    {
        if(Validator::IsValid($Parameters["Uri"], [
            ["Key" => 0]
        ]))
        {
            $Whatsapp = Whatsapp::FindById((int)$Parameters["Uri"][0]);
            if(!empty($Whatsapp))
            {
                $Avatar = $Whatsapp->GetAvatar();
                View::Print("Image", ["Type" => "jpeg", "Image" => empty($Avatar) ? StaticResources::GetImage(USER_UNKNOWN_AVATAR)->GetResource() : $Avatar]);
            }
            else
                PrintJson::OperationError(WhatsappNotFound, NOT_FOUND);
        }
    }


    static public function WhatsappToArray(Whatsapp $Whatsapp) : array
    {
        $Out = [
            "whatsapp_id"       => $Whatsapp->GetId(),
            "phone"             => $Whatsapp->GetPhone(),
            "name"              => $Whatsapp->GetName(),
            "status"            => $Whatsapp->GetStatus(),
            "status_id"         => $Whatsapp->GetStatusId(),
            "is_active"         => $Whatsapp->GetIsActive(),
            "default_folder"    => null
        ];

        try
        {
            $Out["default_folder"] = FolderController::ToArray(Folder::FindById($Whatsapp->GetDefaultFolder()));
        }
        catch(Throwable $error) {}

        return $Out;
    }
}