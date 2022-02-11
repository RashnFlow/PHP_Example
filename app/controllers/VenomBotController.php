<?php


namespace controllers;


use classes\Image;
use classes\Validator;
use models\Whatsapp;
use views\View;
use controllers\MessageController;
use Exception;
use factories\UserFactory;
use models\Authentication;
use models\DynamicResource;
use models\Message;
use models\User;
use models\dialogues\WhatsappDialog;
use models\Proxy;
use views\PrintJson;


class VenomBotController
{
    static public function CheckAccess(?string $Token) : bool
    {
        if(md5($Token) == VENOM_BOT_API_KEY)
            return true;
        else
            throw new Exception("Access is denied", ACCESS_DENIED);
    }


    private function WhatsappToArray(Whatsapp $Whatsapp) : array
    {
        if(!empty($Whatsapp->GetProxyId()))
            $Proxy = Proxy::FindById($Whatsapp->GetProxyId());
        return [
            "Phone"         => $Whatsapp->GetPhone(),
            "VenomSession"  => $Whatsapp->GetVenomSessions(),
            "UserId"        => $Whatsapp->GetUserId(),
            "WhatsappId"    => $Whatsapp->GetId(),
            "Proxy"         => !empty($Proxy) ? $Proxy->ToArray() : null,
            "Avatar"        => DOMAIN_API_URL . "/get/whatsapp/avatar/" . $Whatsapp->GetId() . ".jpg"
        ];
    }
    

    public function ActionGetWhatsapps(array $Parameters)
    {
        Authentication::SetAuthUser(UserFactory::GetVenomUser(), SYSTEM_SESSION);
        $Out = ["Whatsapps" => []];

        foreach(Whatsapp::FindAllActive() as $obj)
            if(!$obj->GetIsDynamic() && !$obj->GetIsBanned())
                $Out["Whatsapps"][] = $this->WhatsappToArray($obj);

        PrintJson::OperationSuccessful($Out);
    }


    public function ActionGetResouce(array $Parameters)
    {
        Authentication::SetAuthUser(UserFactory::GetVenomUser(), SYSTEM_SESSION);
        
        if(Validator::IsValid($Parameters["Get"], [
            ["Key" => "ResouceUid"]
        ]))
        {
            $Resources = DynamicResource::FindByUid($Parameters["Get"]["ResouceUid"]);
            if(!empty($Resources))
            {
                if($Parameters["Get"]["image"] == true)
                    View::Print("Image", ["Type" => "jpeg", "Image" => $Resources->GetResource()]);
                else
                    PrintJson::OperationSuccessful(["Data" => base64_encode($Resources->GetResource())]);
            }
            else
                PrintJson::OperationError(ResourceNotFound, NOT_FOUND);
        }
    }


    public function EventWhatsappSessionInvalid(array $Parameters)
    {
        Authentication::SetAuthUser(UserFactory::GetVenomUser(), SYSTEM_SESSION);
        
        if(Validator::IsValid($Parameters["Post"], [
            ["Key" => "WhatsappId", "Type" => "int"]
        ]))
        {
            $Whatsapp = Whatsapp::FindById((int)$Parameters["Post"]["WhatsappId"]);
            if(!empty($Whatsapp))
            {
                $Whatsapp->SetStatusId(3);
                $Whatsapp->SetIsActive(false);
                $Whatsapp->Save();
                PrintJson::OperationSuccessful();
            }
            else
                PrintJson::OperationError(WhatsappNotFound, NOT_FOUND);
        }
    }


    public function GetQR(Whatsapp $Whatsapp) : ?string
    {
        $QR = self::SendToVenomBot([
            "Command"       => "GetQRCode",
            "WhatsappId"   => $Whatsapp->GetId()
        ], 5);

        if($QR["Status"] == "ok") return explode("base64,", $QR["GetQRCode"])[1];
        throw new Exception("Error get QR code " . $QR["Error"], $QR["Code"]);
    }


    public function EventWhatsappDisconnect(array $Parameters)
    {
        Authentication::SetAuthUser(UserFactory::GetVenomUser(), SYSTEM_SESSION);
        
        if(Validator::IsValid($Parameters["Post"], [
            ["Key" => "WhatsappId", "Type" => "int"]
        ]))
        {
            $Whatsapp = Whatsapp::FindById((int)$Parameters["Post"]["WhatsappId"]);
            if(!empty($Whatsapp))
            {
                $Whatsapp->SetStatusId(2);
                $Whatsapp->Save();
                PrintJson::OperationSuccessful();
            }
            else
                PrintJson::OperationError(WhatsappNotFound, NOT_FOUND);
        }
    }


    public function EventMessageStatusUpdate(array $Parameters)
    {
        if(Validator::IsValid($Parameters["Post"], [
            ["Key" => "Phone", "Type" => "int"],
            ["Key" => "WhatsappId", "Type" => "int"],
            ["Key" => "MessageUid"],
            ["Key" => "Ack", "Type" => "int"],
            ["Key" => "UserId", "Type" => "int"]
        ]))
        {
            Authentication::SetAuthUser(User::FindById((int)$Parameters["Post"]["UserId"]), SYSTEM_SESSION);
            $Dialog = WhatsappDialog::FindByPhoneAndWhatsappId($Parameters["Post"]["Phone"], (int)$Parameters["Post"]["WhatsappId"]);
            if(!empty($Dialog))
            {
                try
                {
                    (new MessageController)->MessageStatusUpdate($Dialog, $Parameters["Post"]["MessageUid"], (int)$Parameters["Post"]["Ack"]);
                    PrintJson::OperationSuccessful();
                }
                catch(Exception $error)
                {
                    PrintJson::OperationError(OperationError, NOT_FOUND);
                }
            }
            else
                PrintJson::OperationError(DialogNotFound, NOT_FOUND);
        }
    }


    public function EventWhatsappConnected(array $Parameters)
    {
        Authentication::SetAuthUser(UserFactory::GetVenomUser(), SYSTEM_SESSION);
        
        if(Validator::IsValid($Parameters["Post"], [
            ["Key" => "WhatsappId", "Type" => "int"]
        ]))
        {
            $Whatsapp = Whatsapp::FindById((int)$Parameters["Post"]["WhatsappId"]);
            if(!empty($Whatsapp))
            {
                $Whatsapp->SetIsActive(true);
                $Whatsapp->SetStatusId(1);
                $Whatsapp->Save();
                PrintJson::OperationSuccessful();
            }
            else
                PrintJson::OperationError(WhatsappNotFound, NOT_FOUND);
        }
    }


    public function GetIsOnline(WhatsappDialog $WhatsappDialog) : bool
    {
        $Whatsapp = Whatsapp::FindById($WhatsappDialog->GetWhatsappId());
        if(!empty($Whatsapp) && $Whatsapp->GetIsActive())
        {
            $Response = self::SendToVenomBot([
                "Command"       => "GetContactStatus",
                "WhatsappId"   => $WhatsappDialog->GetWhatsappId(),
                "Phone"         => $WhatsappDialog->GetPhone()
            ]);

            if($Response["Status"] == "ok")
                return (bool)$Response["GetContactStatus"]["is_online"];
            throw new Exception("Error: " . $Response["Error"], $Response["Code"]);
        }

        return false;
    }


    public function ActionUpdateSessionWhatsapp(array $Parameters)
    {
        if(Validator::IsValid($Parameters["Post"], [
            ["Key" => "WhatsappId", "Type" => "int"],
            ["Key" => "VenomSession"]
        ]))
        {
            Authentication::SetAuthUser(UserFactory::GetVenomUser(), SYSTEM_SESSION);
            $Whatsapp = Whatsapp::FindById((int)$Parameters["Post"]["WhatsappId"]);
            if(!empty($Whatsapp))
            {
                $Whatsapp->SetVenomSessions($Parameters["Post"]["VenomSession"]);
                $Whatsapp->Save();

                PrintJson::OperationSuccessful();
            }
            else
                PrintJson::OperationError(UpdateSessionWhatsappError, SERVER_ERROR);
        }
    }


    /**
     * @return binary|Exception Image
     */
    private function GetAvatar(string $Url)
    {
        ob_start();
        
        $Curl = curl_init();
        curl_setopt_array($Curl, array(
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_URL => $Url,

            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_CONNECTTIMEOUT => 10
        ));
        $Res = curl_exec($Curl);
        curl_close($Curl);

        ob_end_clean();

        $Image = new Image($Res);
        $Image->Resize(300, 300);
        return $Image->GetImageJpeg();
    }


    public function ActivateWhatsapp(Whatsapp $Whatsapp)
    {
        set_time_limit(0);
        $this->InitWhatsapp($Whatsapp);
    }


    public function InitWhatsapp(Whatsapp &$Whatsapp) : bool
    {
        $Response = self::SendToVenomBot([
            "Command"   => "VenomInit",
            "Whatsapp"  => $this->WhatsappToArray($Whatsapp)
        ], 0);

        if($Response["Status"] != "ok")
        {
            if($Response["Code"] == 700)
                $this->EventWhatsappIsBanned($Whatsapp);
            
            throw new Exception("Error initialization " . $Response["Error"], $Response["Code"]);
        }

        if(empty($Whatsapp->GetPhone()))
        {
            if(!Whatsapp::CheckPhone(Validator::NormalizePhone($Response["Phone"])))
                throw new Exception("Phone duplicate", IS_EXISTS);
            $Whatsapp = Whatsapp::FindById($Whatsapp->GetId());
            $Whatsapp->SetPhone(Validator::NormalizePhone($Response["Phone"]));
            $Whatsapp->Save();
        }

        return true;
    }


    public function EventWhatsappIsBanned(Whatsapp $Whatsapp)
    {
        //Fix
        $Whatsapp = Whatsapp::FindById($Whatsapp->GetId());
        $Whatsapp->SetIsBanned(true);
        $Whatsapp->SetIsActive(false);
        $Whatsapp->Save();
    }


    public function CloseWhatsapp(Whatsapp $Whatsapp)
    {
        $Response = self::SendToVenomBot(array_merge([
            "Command"       => "VenomClose"
        ], $this->WhatsappToArray($Whatsapp)));

        if($Response["Status"] != "ok")
            throw new Exception("Error VenomClose " . $Response["Error"], $Response["Code"]);
    }


    public function RestartWhatsapp(Whatsapp $Whatsapp)
    {
        $Response = self::SendToVenomBot([
            "Command"       => "VenomRestart",
            "Whatsapp"      => $this->WhatsappToArray($Whatsapp)
        ], 0);

        if($Response["Status"] != "ok")
            throw new Exception("Error VenomRestart " . $Response["Error"], $Response["Code"]);
    }


    public function SetActivityWhatsappBusiness(Whatsapp $Whatsapp) : bool
    {
        if(!$Whatsapp->GetIsBusiness())
            throw new Exception("Whatsapp is not business");

        $Response = self::SendToVenomBot(array_merge([
            "Command"       => "EditActivityWhatsappBusiness"
        ], $this->WhatsappToArray($Whatsapp)));

        if($Response["Status"] != "ok")
        {
            if($Response["Code"] == 700)
                $this->EventWhatsappIsBanned($Whatsapp);
        }

        return true;
    }


    public function UpdateSettingsWhatsappBusiness(Whatsapp $Whatsapp) : bool
    {
        if(!$Whatsapp->GetIsBusiness())
            throw new Exception("Whatsapp is not business");

        $Response = self::SendToVenomBot(array_merge([
            "Command"       => "UpdateSettingsWhatsappBusiness"
        ], $this->WhatsappToArray($Whatsapp)), 0);

        if($Response["Status"] != "ok")
        {
            if($Response["Code"] == 700)
                $this->EventWhatsappIsBanned($Whatsapp);
            
            throw new Exception("Error: " . $Response["Error"], $Response["Code"]);
        }

        return true;
    }


    public function SetAvatar(Whatsapp $Whatsapp) : bool
    {
        $Response = self::SendToVenomBot(array_merge([
            "Command"       => "EditAvatar"
        ], $this->WhatsappToArray($Whatsapp)));

        if($Response["Status"] != "ok")
        {
            if($Response["Code"] == 700)
                $this->EventWhatsappIsBanned($Whatsapp);
            
            throw new Exception("Error: " . $Response["Error"], $Response["Code"]);
        }

        return true;
    }


    public function SetCompanyNameWhatsappBusiness(Whatsapp $Whatsapp) : bool
    {
        if(!$Whatsapp->GetIsBusiness())
            throw new Exception("Whatsapp is not business");
            
        $Response = self::SendToVenomBot(array_merge([
            "Command"       => "EditCompanyNameWhatsappBusiness"
        ], $this->WhatsappToArray($Whatsapp)));

        if($Response["Status"] != "ok")
        {
            if($Response["Code"] == 700)
                $this->EventWhatsappIsBanned($Whatsapp);
            
            throw new Exception("Error: " . $Response["Error"], $Response["Code"]);
        }

        return true;
    }


    public function ActionAffordablePhoneCheck(array $Parameters)
    {
        if(Validator::IsValid($Parameters["Post"], [
            ["Key" => "WhatsappId", "Type" => "int"],
            ["Key" => "Phone"],
        ]))
        {
            Authentication::SetAuthUser(UserFactory::GetVenomUser(), SYSTEM_SESSION);

            $Whatsapp = Whatsapp::FindByPhone($Parameters["Post"]["Phone"]);
            if(empty($Whatsapp) || $Whatsapp->GetId() == (int)$Parameters["Post"]["WhatsappId"])
                PrintJson::OperationSuccessful();
            else
                PrintJson::OperationError(WhatsappIsExists, 400);
        }
    }


    public function ActionOnMessage(array $Parameters)
    {
        if(Validator::IsValid($Parameters["Post"], [
            ["Key" => "Phone", "Type" => "int"],
            ["Key" => "WhatsappId", "Type" => "int"],
            ["Key" => "UserId", "Type" => "int"],
            ["Key" => "Message"]
        ]))
        {
            Authentication::SetAuthUser(User::FindById($Parameters["Post"]["UserId"]), SYSTEM_SESSION);

            $Message = $Parameters["Post"]["Message"];

            $Avatar = null;
            if(!empty($Message["chat"]["contact"]["profilePicThumbObj"]["eurl"]))
            {
                try
                {
                    if(!$Message["fromMe"])
                        $Avatar = $this->GetAvatar($Message["chat"]["contact"]["profilePicThumbObj"]["eurl"]);
                }
                catch(Exception $error) {}
            }

            $LastMessage = new Message();
            $LastMessage->SetIsMe($Message["fromMe"]);
            $LastMessage->SetUid($Message["id"]);

            switch($Message["type"])
            {
                case 'chat':
                    $LastMessage->SetText((string)$Message["body"]);
                    break;

                case 'sticker':
                    $LastMessage->SetText("В разработке: [Стикер]");
                    break;

                case 'location':
                    $LastMessage->SetText("В разработке: [Геопозиция]");
                    break;
                    
                case 'ptt':
                    $LastMessage->SetText("В разработке: [Голосовое сообщение]");
                    break;

                case 'vcard':
                    $LastMessage->SetText("В разработке: [Контакт]");
                    break;

                case 'document':
                    $LastMessage->SetDocument((string)$Parameters["Post"]["ResourcesId"]);
                    break;

                case 'image':
                    $LastMessage->SetImg((string)$Parameters["Post"]["ResourcesId"]);
                    $LastMessage->SetCaption((string)$Message["caption"]);
                    break;

                case 'video':
                    if($Message["isGif"])
                        $LastMessage->SetText("В разработке: [GIF]");
                    else
                        $LastMessage->SetVideo((string)$Parameters["Post"]["ResourcesId"]);
                    break;

                default:
                    $LastMessage->SetText("[Нераспознанное сообщение]");
                    break;
            }
                
            $LastMessage->SetTime((int)$Message["t"]);

            //FixDialogues
            $Dialog = WhatsappDialog::FindByPhoneAndWhatsappId($Parameters["Post"]["Phone"], (int)$Parameters["Post"]["WhatsappId"]);
            if(empty($Dialog))
            {
                $Dialog = new WhatsappDialog();
                $Dialog->SetWhatsappId((int)$Parameters["Post"]["WhatsappId"]);
                $Dialog->SetPhone($Parameters["Post"]["Phone"]);
            }

            (new MessageController())->OnMessage(
                $Dialog,
                (!empty($Message["chat"]["contact"]["pushname"]) ? $Message["chat"]["contact"]["pushname"] : $Message["chat"]["contact"]["formattedName"]),
                $Avatar,
                false,
                true,
                $LastMessage
            );

            PrintJson::OperationSuccessful();
        }
    }


    public function ExportMessages(Whatsapp $Whatsapp)
    {
        set_time_limit(0);
        $AllChats = self::SendToVenomBot(array_merge([
            "Command"       => "GetAllChats"
        ], $this->WhatsappToArray($Whatsapp)));

        foreach($AllChats["GetAllChats"] as $Phone)
        {
            try
            {
                $Messages = self::SendToVenomBot(array_merge([
                    "Command"       => "GetChat",
                    "Phone"         => $Phone,
                    "WhatsappId"   => $Whatsapp->GetId()
                ]));

                if($Messages["Status"] == "ok")
                {
                    $Avatar = null;
                    $MessageInterlocutor = null;
                    foreach($Messages["GetChat"] as $Message)
                    {
                        if(!$Message["fromMe"])
                        {
                            $MessageInterlocutor = $Message;
                            break;
                        }
                    }

                    if(!empty($MessageInterlocutor["chat"]["contact"]["profilePicThumbObj"]["eurl"]))
                    {
                        try
                        {
                            $Avatar = $this->GetAvatar($MessageInterlocutor["chat"]["contact"]["profilePicThumbObj"]["eurl"]);
                        }
                        catch(Exception $error) {}
                    }

                    $MessageObjs = [];
                    foreach($Messages["GetChat"] as $Message)
                    {
                        $TempMessage = new Message();
                        $TempMessage->SetIsMe($Message["fromMe"]);
                        $TempMessage->SetUid($Message["id"]);

                        if($Message["isMedia"] === true)
                        {
                            $TempMessage->SetImg((string)$Message["resources_id"]);
                            $TempMessage->SetCaption((string)$Message["caption"]);
                        }
                        else
                            $TempMessage->SetText((string)$Message["body"]);
                            
                        $TempMessage->SetTime((int)$Message["t"]);

                        $MessageObjs[] = $TempMessage;
                    }

                    //FixDialogues
                    $Dialog = WhatsappDialog::FindByPhoneAndWhatsappId($Phone, $Whatsapp->GetId());
                    if(empty($Dialog))
                    {
                        $Dialog = new WhatsappDialog();
                        $Dialog->SetWhatsappId($Whatsapp->GetId());
                        $Dialog->SetPhone($Phone);
                    }

                    try
                    {
                        $Name = (!empty($MessageInterlocutor["chat"]["contact"]["pushname"]) ? $MessageInterlocutor["chat"]["contact"]["pushname"] : $MessageInterlocutor["chat"]["contact"]["formattedName"]);
                        if(!empty($Name))
                        {
                            (new MessageController())->ExportMessages(
                                $Dialog,
                                $Name,
                                $Avatar,
                                true,
                                false,
                                $MessageObjs
                            );
                        }
                    }
                    catch(Exception $error) {}
                }
            }
            catch(Exception $error){}
        }
    }


    public function SendMessage(Whatsapp $Whatsapp, string $Phone, Message $Message) : ?string
    {
        $Message->SetIsMe(true);

        $Data = "";
        switch($Message->GetType())
        {
            case Message::MESSAGE_TYPE_TEXT:
                $Data = $Message->GetText();
            break;

            case Message::MESSAGE_TYPE_IMG:
            case Message::MESSAGE_TYPE_VIDEO:
            case Message::MESSAGE_TYPE_DOCUMENT:
                $FileUid = null;
                switch($Message->GetType())
                {
                    case Message::MESSAGE_TYPE_IMG:
                        $FileUid = $Message->GetImg();
                        break;

                    case Message::MESSAGE_TYPE_VIDEO:
                        $FileUid = $Message->GetVideo();
                        break;

                    case Message::MESSAGE_TYPE_DOCUMENT:
                        $FileUid = $Message->GetDocument();
                        break;
                }
                $DynamicResource = DynamicResource::FindByUid($FileUid);
                if(empty($DynamicResource))
                    throw new Exception("File is empty");
                    
                $Data = [
                    "File"      => "data:" . $DynamicResource->GetType() . ";base64," . base64_encode($DynamicResource->GetResource()), "Img" => $Message->GetImg(),
                    "FileName" => (empty($DynamicResource->GetName()) ? ($DynamicResource->GetUid() . "." . $DynamicResource->GetExtension()) : $DynamicResource->GetName()),
                    "Caption"   => $Message->GetCaption()
                ];
            break;
        }

        
        $Response = self::SendToVenomBot([
            "Command"       => "SendMessage",
            "WhatsappId"    => $Whatsapp->GetId(),
            "Phone"         => $Phone,
            "Type"          => $Message->GetType(),
            "Data"          => $Data
        ]);

        if($Response["Status"] == "ok")
        {
            // (new MessageController())->OnMessage(
            //     $Response["WhatsappId"],
            //     $Response["Phone"],
            //     (!empty($Response["SendMessage"]["to"]["pushname"]) ? $Response["SendMessage"]["to"]["pushname"] : $Response["SendMessage"]["to"]["formattedName"]),
            //     null,
            //     true,
            //     null,
            //     $Message
            // );

            return (string)$Response["SendMessage"]["to"]["_serialized"];
        }
        else
            throw new Exception("Failed to send message. Error: " . $Response["Error"], $Response["Code"]);
    }


    static private function SendToVenomBot($Data, int $TimeOut = 40) : array
    {
        ob_start();

        $Curl = curl_init();
        curl_setopt_array($Curl, array(
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_URL => "http://" . VENOM_BOT_IP . ":" . VENOM_BOT_PORT,

            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_TIMEOUT => $TimeOut,

            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($Data)
        ));
        $Response = curl_exec($Curl);
        curl_close($Curl);

        ob_end_clean();

        if($Response === false)
            throw new Exception('Error connect');

        return empty($Response) ? [] : json_decode($Response, true);
    }
}