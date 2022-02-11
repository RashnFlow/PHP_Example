<?php


namespace controllers;

use classes\Http;
use classes\Image;
use classes\Validator;
use Exception;
use factories\UserFactory;
use models\Authentication;
use models\Autoresponder;
use models\DynamicResource;
use models\Instagram;
use models\dialogues\InstagramDialog;
use models\Message;
use models\ModelCollection;
use models\Proxy;
use models\User;
use Throwable;
use views\PrintJson;
use views\View;


class InstagramSdkController
{
    static public function CheckAccess(?string $Token) : bool
    {
        if(md5($Token) == INSTAGRAM_SDK_API_KEY)
            return true;
        else
            throw new Exception("Access is denied", ACCESS_DENIED);
    }


    private function InstagramToArray(Instagram $Instagram) : array
    {
        if(!empty($Instagram->GetProxyId()))
            $Proxy = Proxy::FindById($Instagram->GetProxyId());
        return array_merge(InstagramController::InstagramToArray($Instagram), [
            "password"              => $Instagram->GetPassword(),
            "user_id"               => $Instagram->GetUserId(),
            "comment_tracking"      => $Instagram->GetCommentTracking(),
            "subscriber_tracking"   => $Instagram->GetSubscriberTracking(),
            "session"               => $Instagram->GetSession(),
            "proxy"                 => !empty($Proxy) ? Validator::ArrayKeyPascalCaseToSnakeCase($Proxy->ToArray()) : null
        ]);
    }
    

    public function ActionGetInstagrams(array $Parameters)
    {
        Authentication::SetAuthUser(UserFactory::GetInstagramUser(), SYSTEM_SESSION);
        $Out = ["instagrams" => []];

        foreach(Instagram::FindAllActive() as $obj)
            if(!$obj->GetIsBanned())
                $Out["instagrams"][] = $this->InstagramToArray($obj);

        PrintJson::OperationSuccessful($Out);
    }


    public function ActionOnNewSubscriber(array $Parameters)
    {
        if(Validator::IsValid($Parameters["Post"], [
            ["Key" => "user_id", "Type" => "int"],
            ["Key" => "instagram_id", "Type" => "int"],
            ["Key" => "username"],
            ["Key" => "full_name"],
            ["Key" => "hd_profile_pic_url_info_url", "IsNull" => true],
        ]))
        {
            Authentication::SetAuthUser(User::FindById((int)$Parameters["Post"]["user_id"]), SYSTEM_SESSION);
            if(empty(InstagramDialog::FindByLoginAndInstagramId($Parameters["Post"]["username"], (int)$Parameters["Post"]["instagram_id"])))
            {
                $InstagramDialog = new InstagramDialog();
                $InstagramDialog->SetLogin($Parameters["Post"]["username"]);
                $InstagramDialog->SetInstagramId((int)$Parameters["Post"]["instagram_id"]);
                $InstagramDialog->SetName($Parameters["Post"]["full_name"]);

                try
                {
                    if(!empty($Parameters["Post"]["hd_profile_pic_url_info_url"]))
                        $InstagramDialog->SetAvatar($this->GetAvatar($Parameters["Post"]["hd_profile_pic_url_info_url"]));
                }
                catch(Exception $error) {}
                
                $InstagramDialog->Save();

                AutoresponderController::RunEvent(Autoresponder::AUTORESPONDER_EVENT_ON_NEW_SUBSCRIBER, $InstagramDialog);
            }
            PrintJson::OperationSuccessful();
        }
    }


    public function ActionOnComment(array $Parameters)
    {
        if(Validator::IsValid($Parameters["Post"], [
            ["Key" => "user_id", "Type" => "int"],
            ["Key" => "instagram_id", "Type" => "int"],
            ["Key" => "username"],
            ["Key" => "full_name"],
            ["Key" => "comment"],
            ["Key" => "post_url"],
            ["Key" => "hd_profile_pic_url_info_url", "IsNull" => true],
        ]))
        {
            Authentication::SetAuthUser(User::FindById((int)$Parameters["Post"]["user_id"]), SYSTEM_SESSION);

            $InstagramDialog = InstagramDialog::FindByLoginAndInstagramId($Parameters["Post"]["username"], (int)$Parameters["Post"]["instagram_id"]);
            if(empty($InstagramDialog))
            {
                $InstagramDialog = new InstagramDialog();
                $InstagramDialog->SetLogin($Parameters["Post"]["username"]);
                $InstagramDialog->SetInstagramId((int)$Parameters["Post"]["instagram_id"]);
                $InstagramDialog->SetName($Parameters["Post"]["full_name"]);

                try
                {
                    if(!empty($Parameters["Post"]["hd_profile_pic_url_info_url"]))
                        $InstagramDialog->SetAvatar($this->GetAvatar($Parameters["Post"]["hd_profile_pic_url_info_url"]));
                }
                catch(Exception $error) {}
            }

            $Message = new Message();
            $Message->SetText('[Оставленный комментарий под постом: ' . $Parameters["Post"]["post_url"] . "]\n\n" . $Parameters["Post"]["comment"] . '');
            
            $InstagramDialog->AddMessage($Message);
            $InstagramDialog->Save();

            AutoresponderController::RunEvent(Autoresponder::AUTORESPONDER_EVENT_ON_COMMENT, $InstagramDialog);
            PrintJson::OperationSuccessful();
        }
    }


    public function ActionEventStatus(array $Parameters)
    {
        if(Validator::IsValid($Parameters["Post"], [
            ["Key" => "event"],
            ["Key" => "instagram_id", "Type" => "int"],
        ]))
        {
            Authentication::SetAuthUser(UserFactory::GetInstagramUser(), SYSTEM_SESSION);
            $Instagram = Instagram::FindById((int)$Parameters["Post"]["instagram_id"]);
            if(empty($Instagram)) return;

            $Statuses = [
                "SessionInvalid" => 2,
                "Connected" => 1,
                "TwoFactor" => 3,
                "NotActive" => 0
            ];

            $Instagram->SetStatusId($Statuses[$Parameters["Post"]["event"]]);
            $Instagram->Save();
            PrintJson::OperationSuccessful();
        }
    }


    public function InitInstagram(Instagram $Instagram) : bool
    {
        set_time_limit(0);
        $Response = self::SendToInstagramSdk([
            "command"   => "InstagramInit",
            "instagram"  => $this->InstagramToArray($Instagram)
        ], 0);

        if($Response["status"] != "ok")
        {
            throw new Exception("Error initialization " . $Response["error"], (int)$Response["code"]);
        }

        if($Response["InstagramInit"] == "TwoFactorWait")
            throw new Exception("Two Factor Login", 1102);

        return true;
    }


    public function TwoFactorCode(Instagram $Instagram, string $Code) : bool
    {
        $Response = self::SendToInstagramSdk([
            "command"       => "TwoFactorCode",
            "instagram_id"  => $Instagram->GetId(),
            "code"          => $Code
        ], 0);

        if($Response["status"] != "ok")
            throw new Exception("Error two factor " . $Response["error"], (int)$Response["code"]);

        return true;
    }


    public function ActionUpdateSession(array $Parameters)
    {
        if(Validator::IsValid($Parameters["Post"], [
            ["Key" => "instagram_id", "Type" => "int"],
            ["Key" => "session"]
        ]))
        {
            Authentication::SetAuthUser(UserFactory::GetInstagramUser(), SYSTEM_SESSION);
            $Instagram = Instagram::FindById((int)$Parameters["Post"]["instagram_id"]);
            if(!empty($Instagram))
            {
                $Instagram->SetSession($Parameters["Post"]["session"]);
                $Instagram->Save();

                PrintJson::OperationSuccessful();
            }
            else
                PrintJson::OperationError(UpdateSessionInstagramError, SERVER_ERROR);
        }
    }


    public function ActionSyncDialogues(array $Parameters)
    {
        if(Validator::IsValid($Parameters["Post"], [
            ["Key" => "user_id", "Type" => "int"],
            ["Key" => "instagram_id", "Type" => "int"],
            ["Key" => "items", "Type" => "array"]
        ]))
        {
            set_time_limit(0);
            Authentication::SetAuthUser(User::FindById((int)$Parameters["Post"]["user_id"]), SYSTEM_SESSION);
            
            $ModelCollection = new ModelCollection();
            foreach($Parameters["Post"]["items"] as $item)
            {
                try
                {
                    if(empty($item["users"]))
                        continue;

                    if(!empty(InstagramDialog::FindByLoginAndInstagramId($item["users"][0]["username"], (int)$Parameters["Post"]["instagram_id"])))
                        continue;

                    $Dialog = new InstagramDialog();
                    $Dialog->SetName($item["users"][0]["full_name"]);
                    $Dialog->SetInstagramId((int)$Parameters["Post"]["instagram_id"]);
                    $Dialog->SetLogin($item["users"][0]["username"]);
                    $Dialog->SetIsNew(false);
                    try
                    {
                        $Dialog->SetAvatar($this->GetAvatar($item["users"][0]["profile_pic_url"]));
                    }
                    catch(Exception $error) {}

                    foreach(array_reverse($item["items"]) as $obj)
                    {
                        $Message = $this->CreateMessage($obj);
                        $Message->SetStatusId(Message::MESSAGE_STATUS_READ);
                        $Dialog->AddMessage($Message);
                    }

                    $ModelCollection->Add($Dialog);
                }
                catch(Exception $error) {}
            }
            $ModelCollection->SaveModels();
            PrintJson::OperationSuccessful();
        }
    }


    public function CloseInstagram(Instagram $Instagram) : bool
    {
        $Response = self::SendToInstagramSdk([
            "command"       => "InstagramClose",
            "instagram_id"  => $Instagram->GetId()
        ], 0);

        if($Response["status"] != "ok")
            throw new Exception("Close Error: " . $Response["error"], (int)$Response["code"]);

        return true;
    }


    public function ActionOnMessage(array $Parameters)
    {
        if(Validator::IsValid($Parameters["Post"], [
            ["Key" => "login"],
            ["Key" => "instagram_id", "Type" => "int"],
            ["Key" => "user_id", "Type" => "int"],
            ["Key" => "message"],
            ["Key" => "hd_profile_pic_url_info_url", "IsNull" => true],
            ["Key" => "full_name"]
        ]))
        {
            Authentication::SetAuthUser(User::FindById((int)$Parameters["Post"]["user_id"]), SYSTEM_SESSION);

            $Message = $this->CreateMessage($Parameters["Post"]["message"]["message"]);

            $Avatar = null;
            try
            {
                if(!empty($Parameters["Post"]["hd_profile_pic_url_info_url"]))
                    $Avatar = $this->GetAvatar($Parameters["Post"]["hd_profile_pic_url_info_url"]);
            }
            catch(Exception $error) {}

            //FixDialogues
            $Dialog = InstagramDialog::FindByLoginAndInstagramId($Parameters["Post"]["login"], (int)$Parameters["Post"]["instagram_id"]);
            if(empty($Dialog))
            {
                $Dialog = new InstagramDialog();
                $Dialog->SetInstagramId((int)$Parameters["Post"]["instagram_id"]);
                $Dialog->SetLogin($Parameters["Post"]["login"]);
            }

            (new MessageController())->OnMessage(
                $Dialog,
                $Parameters["Post"]["full_name"],
                $Avatar,
                false,
                true,
                $Message
            );
        }
    }


    public function CreateMessage(array $Obj) : Message
    {
        $Message = new Message();
        $Message->SetUid($Obj["item_id"]);
        $Message->SetIsMe($Obj["is_me"]);
        $Message->SetTime((int)$Obj["timestamp"]);

        switch($Obj["item_type"])
        {
            case 'media':
                $Http = new Http();
                $Resource = new DynamicResource();
                $Resource->SetUserId(Authentication::GetAuthUser()->GetId());

                switch($Obj["media"]["media_type"])
                {
                    case 1:
                        $Resource->SetResource($Http->SendGet($Obj["media"]["image_versions2"]["candidates"][0]["url"]));
                        break;

                    case 2:
                        $Resource->SetResource($Http->SendGet($Obj["media"]["video_versions"][0]["url"]));
                        break;
                }

                $Resource->SetType($Http->GetHeaders()["content-type"]);
                $Resource->Save();


                switch($Obj["media"]["media_type"])
                {
                    case 1:
                        $Message->SetImg($Resource->GetUid());
                        break;

                    case 2:
                        $Message->SetVideo($Resource->GetUid());
                        break;
                }
                break;

            case 'animated_media':
                if($Obj["animated_media"]["is_sticker"])
                    $Message->SetText("В разработке: [Стикер]");
                else
                    $Message->SetText("В разработке: [Медиа]");
                break;

            case 'text':
                $Message->SetText($Obj["text"]);
                break;

            case 'voice_media':
                $Message->SetText("В разработке: [Голосовое сообщение]");
                break;

            case 'raven_media':
                $Message->SetText("В разработке: [Фото]");
                break;

            case 'media_share':
                $Message->SetText("В разработке: [Поделились медиа]");
                break;

            case 'story_share':
                $Message->SetText("В разработке: [Поделились сторис]");
                break;

            default:
                $Message->SetText("[Нераспознанное сообщение]");
                break;
        }

        return $Message;
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


    public function ExportMessages(Instagram $Instagram)
    {
        set_time_limit(0);
        
    }


    public function GetIsOnline(InstagramDialog $InstagramDialog) : bool
    {
        $Instagram = Instagram::FindById($InstagramDialog->GetInstagramId());
        $Response = self::SendToInstagramSdk([
            "command"           => "GetThreadStatus",
            "instagram_id"      => $Instagram->GetId(),
            "thread_login"      => $InstagramDialog->GetLogin()
        ]);

        if($Response["status"] == "ok")
            return (bool)$Response["GetThreadStatus"]["is_online"];
        throw new Exception("Error: " . $Response["error"], (int)$Response["code"]);
    }


    public function SendMessage(Instagram $Instagram, string $Login, Message $Message) : ?string
    {
        $Message->SetIsMe(true);

        $Data = "";
        switch($Message->GetType())
        {
            case Message::MESSAGE_TYPE_TEXT:
                $Data = $Message->GetText();
            break;

            case Message::MESSAGE_TYPE_IMG:
                $DynamicResource = DynamicResource::FindByUid($Message->GetImg());
                if(empty($DynamicResource))
                    throw new Exception('DynamicResource not found');

                $Image = new Image($DynamicResource->GetPath());
                $Data = ["caption" => $Message->GetCaption(), "img" => base64_encode($Image->GetImageJpeg())];
            break;

            case Message::MESSAGE_TYPE_VIDEO:
                $DynamicResource = DynamicResource::FindByUid($Message->GetVideo());
                if(empty($DynamicResource))
                    throw new Exception('DynamicResource not found');

                $Data = base64_encode($DynamicResource->GetResource());
            break;

            default:
                throw new Exception('Message type not supported');
        }

        $Response = self::SendToInstagramSdk([
            "command"       => "SendMessage",
            "instagram_id"  => $Instagram->GetId(),
            "tologin"       => $Login,
            "type"          => $Message->GetType(),
            "data"          => $Data
        ]);

        if($Response["status"] == "ok")
            return (string)$Response["SendMessage"]["item_id"];
        else
            throw new Exception("Failed to send message. Error: " . $Response["error"], (int)$Response["code"]);
    }


    public function ActionGetResouce(array $Parameters)
    {
        Authentication::SetAuthUser(UserFactory::GetVenomUser(), SYSTEM_SESSION);
        
        if(Validator::IsValid($Parameters["Get"], [
            ["Key" => "resouce_uid"]
        ]))
        {
            $Resources = DynamicResource::FindByUid($Parameters["Get"]["resouce_uid"]);
            if(!empty($Resources))
            {
                if($Parameters["Get"]["image"] == true)
                    View::Print("Image", ["Type" => "jpeg", "Image" => $Resources->GetResource()]);
                else
                    PrintJson::OperationSuccessful(["data" => base64_encode($Resources->GetResource())]);
            }
            else
                PrintJson::OperationError(ResourceNotFound, NOT_FOUND);
        }
    }


    static private function SendToInstagramSdk($Data, int $TimeOut = 40) : array
    {
        ob_start();

        $Curl = curl_init();
        curl_setopt_array($Curl, array(
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_URL => "http://" . INSTAGRAM_SDK_IP . ":" . INSTAGRAM_SDK_PORT,

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