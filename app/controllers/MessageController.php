<?php


namespace controllers;


use classes\Freeze;
use classes\Http;
use classes\Log;
use classes\Logger;
use classes\Validator;
use Exception;
use models\Authentication;
use models\Autoresponder;
use models\dialogues\Dialog;
use models\dialogues\InstagramApiDialog;
use models\Instagram;
use models\dialogues\InstagramDialog;
use models\dialogues\LocalDialog;
use models\Message;
use models\Whatsapp;
use models\dialogues\WhatsappDialog;
use models\DynamicResource;
use models\IgnoreList;
use models\InstagramApi;
use Throwable;
use views\PrintJson;


class MessageController
{
    public function ActionSendMessage(array $Parameters)
    {
        if(Validator::IsValid($Parameters["Post"], [
            ["Key" => "dialog_id", "Type" => "int"],
            ["Key" => "type"],
            ["Key" => "data"],
            ["Key" => "caption", "IsNull" => true]
        ]))
        {
            $Dialog = Dialog::FindById($Parameters["Post"]["dialog_id"]);
            if(!empty($Dialog))
            {
                $Message = new Message();
                try
                {
                    $Message->SetContent($Parameters["Post"]["type"], $Parameters["Post"]["data"], $Parameters["Post"]["caption"]);
                }
                catch(Exception $error)
                {
                    PrintJson::OperationError(TypeNotSupported, REQUEST_FAILED);
                    return;
                }

                $Message->SetTime(time());
                Freeze::SetProgress("SendMessage_Dialog:" . $Dialog->GetId());
                try
                {
                    PrintJson::OperationSuccessful(["message_uid" => $this->SendMessage($Dialog, $Message)]);
                }
                catch(Exception $error)
                {
                    Logger::Log(Log::TYPE_ERROR, "Ошибка при отправки сообщения", (string)$error);
                    PrintJson::OperationError(MessageSendingError, SERVER_ERROR);
                }
                Freeze::DeleteProgress("SendMessage_Dialog:" . $Dialog->GetId());
                
            }
            else
                PrintJson::OperationError(DialogNotFound, NOT_FOUND);
        }
    }


    public function ActionSendTestMessage(array $Parameters)
    {
        if(Validator::IsValid($Parameters["Post"], [
            ["Key" => "phone"],
            ["Key" => "type"],
            ["Key" => "data"],
            ["Key" => "caption", "IsNull" => true],
            ["Key" => "whatsapp_id", "IsNull" => true],
            ["Key" => "instagram_id", "IsNull" => true]
        ]))
        {
            if(!empty($Parameters["Post"]["whatsapp_id"]))
            {
                $Whatsapp = Whatsapp::FindById($Parameters["Post"]["whatsapp_id"]);
                $Dialog = WhatsappDialog::FindByPhoneAndWhatsappId(Validator::NormalizePhone($Parameters["Post"]["phone"]), $Whatsapp->GetId());
                if(!empty($Whatsapp))
                {
                    if(empty($Dialog))
                        $Dialog = new WhatsappDialog();
                    $Dialog->SetWhatsappId($Whatsapp->GetId());
                    $Dialog->SetPhone(Validator::NormalizePhone($Parameters["Post"]["phone"]));
                }
                else
                    PrintJson::OperationError(WhatsappNotFound, NOT_FOUND);                        
            } 
            else if(!empty($Parameters["Post"]["instagram_id"]))
            {
                $Instagram = Instagram::FindById($Parameters["Post"]["instagram_id"]);
                $Dialog = InstagramDialog::FindByLoginAndInstagramId($Instagram->GetLogin(), $Instagram->GetId());
                if (!empty($Instagram))
                {
                    if(empty($Dialog))
                        $Dialog = new InstagramDialog();
                    $Dialog->SetLogin($Instagram->GetLogin());
                    $Dialog->SetInstagramId($Instagram->GetId());
                }
                else
                    PrintJson::OperationError(InstagramNotFound, NOT_FOUND);  
            }
            if(!empty($Dialog))
            {
                $Message = new Message();
                try
                {
                    $Message->SetContent($Parameters["Post"]["type"], $Parameters["Post"]["data"], $Parameters["Post"]["caption"]);
                }
                catch(Exception $error)
                {
                    PrintJson::OperationError(TypeNotSupported, REQUEST_FAILED);
                    return;
                }

                $Message->SetTime(time());
                try
                {
                    PrintJson::OperationSuccessful(["message_uid" => $this->SendMessage($Dialog, $Message)]);
                }
                catch(Exception $error)
                {
                    Logger::Log(Log::TYPE_ERROR, "Ошибка при отправки сообщения", (string)$error);
                    PrintJson::OperationError(MessageSendingError, SERVER_ERROR);
                }
                
            }
            else
                PrintJson::OperationError(DialogNotFound, NOT_FOUND);
        }
    }


    //FixDialogues
    public function SendMessage(Dialog $Dialog, Message $Message) : ?string
    {
        if(!$Dialog->GetIsActive())
            throw new Exception("Dialog invalid");

        if($Dialog instanceof WhatsappDialog)
        {
            $Whatsapp = Whatsapp::FindById($Dialog->GetWhatsappId());
            if(!empty($Whatsapp))
            {
                if(!empty($Source = $Message->GetSource()))
                {
                    if(!empty($IgnoreList = IgnoreList::FindByUserId($Whatsapp->GetUserId())))
                    {
                        if(in_array($Dialog->GetPhone(), $IgnoreList->IgnoreParameters[$Source]))
                            return null;
                    }
                }
                if($Whatsapp->GetIsActive())
                    return (new VenomBotController())->SendMessage($Whatsapp, $Dialog->GetPhone(), $Message);
                else
                    throw new Exception("Non-active Whatsapp");
                    
            }
            else
                throw new Exception("Whatsapp not found");
        }
        else if($Dialog instanceof InstagramDialog)
        {
            $Instagram = Instagram::FindById($Dialog->GetInstagramId());
            if(!empty($Instagram))
            {
                if($Instagram->GetIsActive())
                    return (new InstagramSdkController())->SendMessage($Instagram, $Dialog->GetLogin(), $Message);
                else
                    throw new Exception("Non-active Instagram");
                    
            }
            else
                throw new Exception("Instagram not found");
        }
        else if($Dialog instanceof LocalDialog)
            return (new LocalDialogController())->SendMessage($Dialog, $Message);
        else if($Dialog instanceof InstagramApiDialog)
        {
            $InstagramApi = InstagramApi::FindById($Dialog->GetInstagramApiId());
            if(!empty($InstagramApi))
            {
                if(!$InstagramApi->IsActive)
                    throw new Exception("Non-active Instagram");

                return (new InstagramApiController())->SendMessage($InstagramApi, $Dialog->GetInstagramApiUserId(), $Message);
            }
            else
                throw new Exception("Instagram not found");
        }
        else
            throw new Exception("Dialog invalid");
    }


    public function MessageStatusUpdate(Dialog $Dialog, string $MessageUid, int $StatusId)
    {
        Freeze::SetProgress("MessageStatusUpdate_" . $Dialog->GetId());
        $MessageFind = null;
        foreach($Dialog->GetMessages() as $Message)
        {
            if($Message->GetUid() == $MessageUid)
            {
                $Message->SetStatusId($StatusId);
                $Message->Save();
                $MessageFind = $Message;
                break;
            }
        }

        if(empty($MessageFind))
        {
            Freeze::DeleteProgress("MessageStatusUpdate_" . $Dialog->GetId());
            throw new Exception("Message not found");
        }

        Freeze::DeleteProgress("MessageStatusUpdate_" . $Dialog->GetId());

        try 
        {
            (new Http())->SendPost(MESSAGE_SOCKET,[
                "UserId" => Authentication::GetAuthUser()->GetId(),
                "Data" => [
                        "command" => "MessageStatusUpdate",
                        "message" => $this->MessageToArray($MessageFind),
                        "dialog_id" => $Dialog->GetId()
                    ]
            ]);
        }
        catch (Exception $error) {}

        switch($StatusId)
        {
            case Message::MESSAGE_STATUS_READ:
                try
                {
                    (new \controllers\DynamicMassSendingController())->OnRead($Dialog);
                }
                catch(Exception $error) {}
                break;
        }
    }


    public function OnMessage(Dialog $Dialog, ?string $Name, $Avatar, ?bool $IsRead, ?bool $IsOnline, Message $Message)
    {
        if(!empty($Message->GetUid()))
            foreach($Dialog->GetMessages() as $TempMessage)
                if($TempMessage->GetUid() == $Message->GetUid())
                    return;
        
        if(!empty($Name))           $Dialog->SetName($Name);
        if(!empty($Avatar))         $Dialog->SetAvatar($Avatar);
        if(isset($IsOnline))        $Dialog->SetIsOnline($IsOnline);

        if($Message->GetIsMe())
            $Dialog->Read();

        $Message->SetStatusId(Message::MESSAGE_STATUS_SENT);

        $Dialog->AddMessage($Message);
        $Dialog->Save();

        try
        {
            $UserId = null;
            if($Dialog instanceof LocalDialog)
                $UserId = $Dialog->GetUserId();
                
            $this->SendNewMessage($Dialog, $UserId);
        }
        catch(Exception $error) {}

        try
        {
            (new \controllers\AmoCRMIntegrationController())->OnMessage($Dialog);
        }
        catch(Throwable $error) {}

        if(!$Dialog->GetLastMessage()->GetIsMe())
            $this->RunEvents($Dialog);
    }


    private function RunEvents(Dialog $Dialog)
    {
        try
        {
            (new \controllers\MassSendingController())->OnMessage($Dialog);
        }
        catch(Throwable $error) {}

        try
        {
            if($Dialog->IsNew())
                (new \controllers\AutoresponderController())->RunEvent(Autoresponder::AUTORESPONDER_EVENT_ON_NEW_DIALOG, $Dialog);
        }
        catch(Throwable $error) {}

        try
        {
            if(!$Dialog->IsNew())
                (new \controllers\AutoresponderController())->RunEvent(Autoresponder::AUTORESPONDER_EVENT_ON_MESSAGE, $Dialog);
        }
        catch(Throwable $error) {}

        try
        {
            (new \controllers\DynamicMassSendingController())->OnMessage($Dialog);
        }
        catch(Throwable $error) {}

        try
        {
            (new \controllers\BitrixIntegrationController())->OnMessage($Dialog);
        }
        catch(Throwable $error) {}
    }


    public function ExportMessages(Dialog $Dialog, string $Name, $Avatar, bool $IsRead, bool $IsOnline, array $Messages)
    {
        if(!is_array($Messages)) throw new Exception("Data of type array was expected");

        if(!empty($Dialog->GetId()))
            $Dialog->Delete();
        
        if(!empty($Name))           $Dialog->SetName($Name);
        if(!empty($Avatar))         $Dialog->SetAvatar($Avatar);
        if($IsRead)                 $Dialog->Read();
        if(isset($IsOnline))        $Dialog->SetIsOnline((bool)$IsOnline);

        foreach($Messages as $Message)
            $Dialog->AddMessage($Message);

        $Dialog->Save();
    }


    private function SendNewMessage(Dialog $Dialog, int $UserId = null)
    {
        try 
        {
            if(empty($UserId))
                $UserId = Authentication::GetAuthUser()->GetId();

            (new Http())->SendPost(MESSAGE_SOCKET,[
                "UserId" => $UserId,
                "Data" => array_merge(DialogController::DialogToArray($Dialog), ["command" => "NewMessage"])
            ]);
        }
        catch (Throwable $error) {}
    }


    static public function MessageToArray(Message $Message) : array
    {
        $Out = [
            "message_id"    => $Message->GetId(),
            "message_uid"   => $Message->GetUid(),
            "type"          => $Message->GetType(),
            "is_me"         => $Message->GetIsMe(),
            "is_read"       => $Message->IsRead(),
            "is_sent"       => $Message->IsSent(),
            "status_id"     => $Message->GetStatusId(),
            "time"          => $Message->GetTime(),
            "caption"       => $Message->GetCaption()
        ];

        $DynamicResource = null;
        switch($Message->GetType())
        {
            case Message::MESSAGE_TYPE_TEXT:
                $Out["message"] = $Message->GetText();
                break;

            case Message::MESSAGE_TYPE_IMG:
                $DynamicResource = DynamicResource::FindByUid($Message->GetImg());
                $Out["img"] = DOMAIN_API_URL . "/get/dynamic/resource?uid=" . $Message->GetImg();
                break;

            case Message::MESSAGE_TYPE_VIDEO:
                $DynamicResource = DynamicResource::FindByUid($Message->GetVideo());
                $Out["video"] = DOMAIN_API_URL . "/get/dynamic/resource?uid=" . $Message->GetVideo();
                break;

            case Message::MESSAGE_TYPE_DOCUMENT:
                $DynamicResource = DynamicResource::FindByUid($Message->GetDocument());
                $Out["document"] = DOMAIN_API_URL . "/get/dynamic/resource?uid=" . $Message->GetDocument();
                break;
        }

        switch($Message->GetType())
        {
            case Message::MESSAGE_TYPE_IMG:
            case Message::MESSAGE_TYPE_VIDEO:
            case Message::MESSAGE_TYPE_DOCUMENT:
                if(!empty($DynamicResource))
                {
                    $Out["file_name"] = $DynamicResource->GetName();
                    $Out["size"] = $DynamicResource->GetSize();
                    $Out["file_uid"] = $DynamicResource->GetUid();
                }
                break;
        }

        return $Out;
    }
}