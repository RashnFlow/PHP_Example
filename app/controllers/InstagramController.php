<?php


namespace controllers;


use classes\Validator;
use Exception;
use models\Authentication;
use models\Folder;
use models\Instagram;
use models\Proxy;
use Throwable;
use views\PrintJson;


class InstagramController
{
    public function ActionGetAllInstagrams(array $Parameters)
    {
        $Out = [];
        foreach(Instagram::FindAllByUserId() as $Instagram)
            $Out[] = self::InstagramToArray($Instagram);

        PrintJson::OperationSuccessful(["instagrams" => $Out]);
    }


    public function ActionCreateInstagram(array $Parameters)
    {
        if(Validator::IsValid($Parameters["Post"], [
            ["Key" => "login"],
            ["Key" => "password"],
            ["Key" => "default_folder", "Type" => "int", "IsNull" => true],
        ]))
        {
            if(empty(Instagram::FindByLogin($Parameters["Post"]["login"], false)))
            {
                $Instagram = new Instagram();
                $Instagram->SetUserId(Authentication::GetAuthUser()->GetId());
                $Instagram->SetLogin($Parameters["Post"]["login"]);
                $Instagram->SetPassword($Parameters["Post"]["password"]);

                if(!empty((int)$Parameters["Post"]["default_folder"]))
                    $Instagram->SetDefaultFolder((int)$Parameters["Post"]["default_folder"]);

                try
                {
                    $Count = 0;
                    while(true)
                    {
                        $Proxy = Proxy::Next();
                        if($Proxy->UrlCheck("edge-chat.instagram.com/chat"))
                        {
                            $Instagram->SetProxyId($Proxy->ProxyId);
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
                    PrintJson::OperationError(AuthError, REQUEST_FAILED);
                    return;
                }

                $Instagram->Save();

                try
                {
                    (new InstagramSdkController())->InitInstagram($Instagram);
                    PrintJson::OperationSuccessful(["two_factor" => false]);
                }
                catch(Exception $error)
                {
                    if($error->getCode() != 1102)
                    {
                        $Instagram->Delete();
                        PrintJson::OperationError(AuthError, REQUEST_FAILED);
                    }
                    else
                        PrintJson::OperationSuccessful(["two_factor" => true, "instagram_id" => $Instagram->GetId()]);
                }
            }
            else
                PrintJson::OperationError(InstagramIsExists, IS_EXISTS);
        }
    }


    public function ActionTwoFactor(array $Parameters)
    {
        if(Validator::IsValid($Parameters["Post"], [
            ["Key" => "instagram_id", "Type" => "int"],
            ["Key" => "code"],
        ]))
        {
            $Instagram = Instagram::FindById((int)$Parameters["Post"]["instagram_id"]);
            if(!empty($Instagram))
            {
                try
                {
                    (new InstagramSdkController())->TwoFactorCode($Instagram, $Parameters["Post"]["code"]);
                    PrintJson::OperationSuccessful();
                }
                catch(Exception $error)
                {
                    PrintJson::OperationError(InstagramInvalidCode, REQUEST_FAILED);
                }
            }
            else
                PrintJson::OperationError(InstagramNotFound, NOT_FOUND);
        }
    }


    public function ActionActivateInstagram(array $Parameters)
    {
        if(Validator::IsValid($Parameters["Post"], [
            ["Key" => "instagram_id", "Type" => "int"]
        ]))
        {
            $Instagram = Instagram::FindById((int)$Parameters["Post"]["instagram_id"]);
            if(empty($Instagram))
            {
                PrintJson::OperationError(InstagramNotFound, NOT_FOUND);
                return;
            }

            try
            {
                (new InstagramSdkController())->InitInstagram($Instagram);
                PrintJson::OperationSuccessful(["two_factor" => false]);
            }
            catch(Exception $error)
            {
                if($error->getCode() == 1102)
                    PrintJson::OperationSuccessful(["two_factor" => true, "instagram_id" => $Instagram->GetId()]);
                else
                    PrintJson::OperationError(OperationError, REQUEST_FAILED);
            }
        }
    }


    public function ActionUpdateInstagram(array $Parameters)
    {
        if(Validator::IsValid($Parameters["Post"], [
            ["Key" => "instagram_id", "Type" => "int"],
            ["Key" => "login", "IsNull" => true],
            ["Key" => "subscriber_tracking", "Type" => "bool", "IsNull" => true],
            ["Key" => "comment_tracking", "Type" => "bool", "IsNull" => true],
            ["Key" => "password", "IsNull" => true],
            ["Key" => "default_folder", "Type" => "int", "IsNull" => true],
        ]))
        {
            $Instagram = Instagram::FindById((int)$Parameters["Post"]["instagram_id"]);
            if(!empty($Instagram))
            {
                try
                {
                    (new InstagramSdkController())->CloseInstagram($Instagram);
                }
                catch(Exception $error) {}

                if(!empty($Parameters["Post"]["login"]))
                    $Instagram->SetLogin($Parameters["Post"]["login"]);

                if(!empty($Parameters["Post"]["password"]))
                    $Instagram->SetPassword($Parameters["Post"]["password"]);

                if(isset($Parameters["Post"]["subscriber_tracking"]))
                    $Instagram->SetSubscriberTracking((bool)$Parameters["Post"]["subscriber_tracking"]);

                if(isset($Parameters["Post"]["comment_tracking"]))
                    $Instagram->SetCommentTracking((bool)$Parameters["Post"]["comment_tracking"]);

                if(!empty((int)$Parameters["Post"]["default_folder"]))
                    $Instagram->SetDefaultFolder((int)$Parameters["Post"]["default_folder"]);

                $Instagram->Save();

                try
                {
                    (new InstagramSdkController())->InitInstagram($Instagram);
                    PrintJson::OperationSuccessful(["two_factor" => false]);
                }
                catch(Exception $error)
                {
                    if($error->getCode() != 1102)
                    {
                        try
                        {
                            (new InstagramSdkController())->CloseInstagram($Instagram);
                        }
                        catch(Exception $error) {}
                        PrintJson::OperationError(AuthError, REQUEST_FAILED);
                    }
                    else
                        PrintJson::OperationSuccessful(["two_factor" => true, "instagram_id" => $Instagram->GetId()]);
                }
            }
            else
                PrintJson::OperationError(InstagramNotFound, NOT_FOUND);
        }
    }


    public function ActionDeleteInstagram(array $Parameters)
    {
        if(Validator::IsValid($Parameters["Post"], [
            ["Key" => "instagram_ids", "Type" => "array"]
        ]))
        {
            foreach($Parameters["Post"]["instagram_ids"] as $InstagramId)
            {
                
                $Instagram = Instagram::FindById($InstagramId);
                if(empty($Instagram))
                    return;

                try
                {
                    (new InstagramSdkController())->CloseInstagram($Instagram);
                }
                catch(Exception $error) {}
                    
                $Instagram->Delete();
                
            }

            PrintJson::OperationSuccessful();
        }
    }
    


    static public function InstagramToArray(Instagram $Instagram) : array
    {
        $Out = [
            "instagram_id"          => $Instagram->GetId(),
            "login"                 => $Instagram->GetLogin(),
            "status"                => $Instagram->GetStatus(),
            "status_id"             => $Instagram->GetStatusId(),
            "is_active"             => $Instagram->GetIsActive(),
            "comment_tracking"      => $Instagram->GetCommentTracking(),
            "subscriber_tracking"   => $Instagram->GetSubscriberTracking(),
            "default_folder"        => null
        ];

        try
        {
            $Out["default_folder"] = FolderController::ToArray(Folder::FindById($Instagram->GetDefaultFolder()));
        }
        catch(Throwable $error) {}

        return $Out;
    }
}