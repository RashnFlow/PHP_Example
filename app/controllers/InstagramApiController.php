<?php


namespace controllers;

use classes\Http;
use classes\Log;
use classes\Logger;
use classes\Tools;
use classes\Validator;
use Exception;
use factories\UserFactory;
use models\Authentication;
use models\dialogues\InstagramApiDialog;
use models\DynamicResource;
use models\Facebook;
use models\Folder;
use models\InstagramApi;
use models\Message;
use models\ModelCollection;
use models\User;
use sdk\php\facebook\FacebookSDK;
use Throwable;
use views\PrintJson;


class InstagramApiController
{
    static public function CheckAccess(?string $Token) : bool
    {
        if(md5($Token) == INSTAGRAM_API_WEBHOOKS_KEY)
            return true;
        else
            throw new Exception("Access is denied", ACCESS_DENIED);
    }


    public function ActionGetAccounts(array $Parameters)
    {
        if(Validator::IsValid($Parameters["Get"], [
            ["Key" => "facebook_id", "Type" => "int"]
        ]))
        {
            $Facebook = Facebook::FindById((int)$Parameters["Get"]["facebook_id"]);
            if(empty($Facebook) || !$Facebook->IsActive)
            {
                PrintJson::OperationError(FacebookNotFound, 404);
                return;
            }

            $FacebookSDK = new FacebookSDK($Facebook);

            try
            {
                $InstagramAccounts = [];
                foreach($FacebookSDK->get('/me/accounts')->getDecodedBody()['data'] as $obj)
                {
                    $Response = ($FacebookSDK->get('/'. $obj['id'] . '?fields=instagram_business_account'))->getDecodedBody();
                    if(empty($Response['instagram_business_account']))
                        continue;
                    
                    $Response = ($FacebookSDK->get('/'. $Response['instagram_business_account']['id'] . '?fields=username,profile_picture_url'))->getDecodedBody();
                    
                    $InstagramApi = InstagramApi::FindByInstagramUserId((int)$Response['id']);
                    if(empty($InstagramApi))
                    {
                        $InstagramApi                   = new InstagramApi();
                        $InstagramApi->UserName         = $Response['username'];
                        $InstagramApi->PageId           = (int)$obj['id'];
                        $InstagramApi->InstagramUserId  = (int)$Response['id'];
                        $InstagramApi->PictureUrl       = empty($Response['profile_picture_url']) ? (DOMAIN_API_URL . 'get/static/resource?filename=' . USER_UNKNOWN_AVATAR) : $Response['profile_picture_url'];
                        $InstagramApi->FacebookId       = $Facebook->FacebookId;

                        $InstagramApi->Save();
                    }

                    if($InstagramApi->IsActive)
                        continue;
                    
                    $InstagramAccounts[] = $this->InstagramToArray($InstagramApi);
                }

                if(!empty($InstagramAccounts))
                    PrintJson::OperationSuccessful(["instagram_accounts" => $InstagramAccounts]);
                else
                    PrintJson::OperationError(InstagramApiNoAccountsToConnect, 404);
            }
            catch(Exception $error)
            {
                Logger::Log(Log::TYPE_FATAL_ERROR, "Ошибка при получении Instagram аккаунтов", (string)$error);
                PrintJson::OperationError(OperationError, 500);
            }
        }
    }


    public function ActionConnectAccount(array $Parameters)
    {
        if(Validator::IsValid($Parameters["Post"], [
            ["Key" => "facebook_id", "Type" => "int"],
            ["Key" => "instagram_api_id", "Type" => "int"],
            ["Key" => "default_folder", "Type" => "int", "IsNull" => true],
            ["Key" => "comment_tracking", "Type" => "bool"],
        ]))
        {
            $Facebook = Facebook::FindById((int)$Parameters["Post"]["facebook_id"]);
            if(empty($Facebook) || !$Facebook->IsActive)
            {
                PrintJson::OperationError(FacebookNotFound, 404);
                return;
            }

            $InstagramApi = InstagramApi::FindById((int)$Parameters["Post"]["instagram_api_id"]);
            if(empty($InstagramApi) || $InstagramApi->IsActive)
            {
                PrintJson::OperationError(empty($InstagramApi) ? InstagramApiNotFound : InstagramApiIsActive, 404);
                return;
            }

            if(!empty((int)$Parameters["Post"]["default_folder"]))
                $InstagramApi->DefaultFolder = (int)$Parameters["Post"]["default_folder"];
            $InstagramApi->CommentTracking = (bool)$Parameters["Post"]["comment_tracking"];
            $InstagramApi->IsActive = true;
            $InstagramApi->Save();

            try { $this->SyncDialogues($InstagramApi); }  catch(Throwable $error) {}

            PrintJson::OperationSuccessful();
        }
    }


    public function ActionEditInstagramApi(array $Parameters)
    {
        if(Validator::IsValid($Parameters["Post"], [
            ["Key" => "instagram_api_id", "Type" => "int"],
            ["Key" => "default_folder", "Type" => "int", "IsNull" => true],
            ["Key" => "comment_tracking", "Type" => "bool", "IsNull" => true],
        ]))
        {
            $InstagramApi = InstagramApi::FindById((int)$Parameters["Post"]["instagram_api_id"]);
            if(empty($InstagramApi))
            {
                PrintJson::OperationError(InstagramApiNotFound, NOT_FOUND);
                return;
            }

            if(isset($Parameters["Post"]["default_folder"]))
                $InstagramApi->DefaultFolder = $Parameters["Post"]["default_folder"];

            if(isset($Parameters["Post"]["comment_tracking"]))
                $InstagramApi->CommentTracking = (bool)$Parameters["Post"]["comment_tracking"];
            $InstagramApi->Save();

            PrintJson::OperationSuccessful();
        }
    }


    private function SyncDialogues(InstagramApi $InstagramApi)
    {
        //Выгрузка диалогов
        set_time_limit(0);
        $FacebookSDK = new FacebookSDK(Facebook::FindById($InstagramApi->FacebookId));
        try
        {
            $ModelCollection = new ModelCollection();
            foreach(InstagramApiDialog::FindAllByUserId() as $Dialog)
                $ModelCollection->Add($Dialog);

            $ModelCollection->DeleteModels();
            $ModelCollection->Clear();

            foreach($FacebookSDK->get($InstagramApi->PageId . '/conversations?fields=messages.limit(1){created_time,from,id,is_unsupported,message,sticker,story,tags,to,attachments{id,image_data,mime_type,name,size,video_data,file_url},reactions,shares{description,id,link,name,template}},participants&platform=instagram&limit=50', null, $InstagramApi->PageId)->getDecodedBody()['data'] as $obj)
            {
                $User = $FacebookSDK->get($obj['participants']['data'][1]['id'], null, $InstagramApi->PageId)->getDecodedBody();

                $Dialog = new InstagramApiDialog();
                $Dialog->SetInstagramApiUserId((int)$User['id']);
                $Dialog->SetFacebookDialogUid($obj['id']);
                $Dialog->SetInstagramApiUserName($obj['participants']['data'][1]['username']);
                $Dialog->SetInstagramApiId($InstagramApi->InstagramApiId);
                $Dialog->SetName(empty($User['name']) ? $obj['participants']['data'][1]['username'] : $User['name']);

                try { $Dialog->SetAvatar((new Http())->SendGet($User['profile_pic'])); } catch(Exception $error) {}

                if(!empty($obj['messages']['data'][0]))
                    $Dialog->AddMessage($this->CreateMessage($obj['messages']['data'][0], $Dialog));

                $ModelCollection->Add($Dialog);
            }
            $ModelCollection->SaveModels();
        }
        catch(Exception $error)
        {
            Logger::Log(Log::TYPE_FATAL_ERROR, "Ошибка при выгрузке диалогов с InstagramApi", (string)$error);
        }
    }


    public function SyncMessages(InstagramApiDialog &$Dialog)
    {
        if($Dialog->GetMessagesIsLoaded())
            return;

        if(empty($Dialog->GetFacebookDialogUid()))
            throw new Exception('Facebook Dialog Uid is empty!');

        $InstagramApi = InstagramApi::FindById($Dialog->GetInstagramApiId());
        $FacebookSDK = new FacebookSDK(Facebook::FindById($InstagramApi->FacebookId));
        $Messages = $Dialog->GetMessages();
        foreach(array_reverse($FacebookSDK->get($Dialog->GetFacebookDialogUid() . '?fields=messages.limit(100){created_time,from,id,is_unsupported,message,sticker,story,tags,to,attachments{id,image_data,mime_type,name,size,video_data,file_url},reactions,shares{description,id,link,name,template}}', null, $InstagramApi->PageId)->getDecodedBody()['messages']['data']) as $obj)
        {
            $Message = $this->CreateMessage($obj, $Dialog);
            $IsUnique = true;
            foreach($Messages as $mess)
                if($mess->GetUid() == $Message->GetUid())
                    $IsUnique = false;

            if($IsUnique)
                $Dialog->AddMessage($Message);
        }
        $Dialog->SetMessagesIsLoaded(true);
        $Dialog->Save();
    }


    public function ActionGetAllModels(array $Parameters)
    {
        $Out = [];
        try
        {
            foreach(Facebook::FindAllByUserId() as $Facebook)
                foreach(InstagramApi::FindAllActiveByFacebookId($Facebook->FacebookId) as $InstagramApi)
                    $Out[] = self::InstagramToArray($InstagramApi);
        }
        catch(Throwable $error) {}
        PrintJson::OperationSuccessful(["instagrams_api" => $Out]);
    }


    public function SendMessage(InstagramApi $InstagramApi, int $UserId, Message $Message) : ?string
    {
        $FacebookSDK = new FacebookSDK(Facebook::FindById($InstagramApi->FacebookId));
        if(empty($FacebookSDK))
            throw new Exception('Facebook not found', NOT_FOUND);

        $Send = [
            "recipient" => [
                "id" => (string)$UserId
            ],
            "message" => [],
            "tag" => "HUMAN_AGENT"
        ];
        switch($Message->GetType())
        {
            case Message::MESSAGE_TYPE_TEXT:
                $Send['message']['text'] = $Message->GetText();
            break;

            case Message::MESSAGE_TYPE_IMG:
            case Message::MESSAGE_TYPE_VIDEO:
                $DynamicResourceUid = null;

                if($Message->GetType() == Message::MESSAGE_TYPE_IMG)
                {
                    $DynamicResourceUid = $Message->GetImg();
                    $Send['message']['attachment']['type'] = 'image';
                }
                else
                {
                    $DynamicResourceUid = $Message->GetVideo();
                    $Send['message']['attachment']['type'] = 'video';
                }

                $Send['message']['attachment']['payload']['url'] = DOMAIN_API_URL . "/get/dynamic/resource?download=true&uid=" . $DynamicResourceUid . "&user-id=" . Authentication::GetAuthUser()->GetId() . "&token=" . Tools::GenerateStringBySeed(100, Tools::ConvertStringToSeed($DynamicResourceUid));
            break;

            default:
                throw new Exception($Message->GetType() . ' type is not supported');
            break;
        }
        return $FacebookSDK->post('/me/messages', $Send, null, $InstagramApi->PageId)->getDecodedBody()['message_id'];
    }


    public function ActionDeleteInstagramApi(array $Parameters)
    {
        if(Validator::IsValid($Parameters["Post"], [
            ["Key" => "instagram_api_id", "Type" => "int"]
        ]))
        {
            $InstagramApi = InstagramApi::FindById((int)$Parameters["Post"]['instagram_api_id']);
            if(!empty($InstagramApi))
            {
                $InstagramApi->Delete();
                PrintJson::OperationSuccessful();
            }
            else
                PrintJson::OperationError(InstagramApiNotFound, NOT_FOUND);
        }
    }


    public function ActionWebhooks(array $Parameters)
    {
        Authentication::SetAuthUser(UserFactory::GetInstagramApiUser(), SYSTEM_SESSION);
        if($Parameters['Get']['hub_mode'] == 'subscribe')
        {
            echo $Parameters['Get']['hub_challenge'];
            return;
        }

        if($Parameters['Post']['object'] != 'instagram')
        {
            Logger::Log(Log::TYPE_ERROR, 'Запрос не поддерживается в ' . __FILE__ . ':' . __LINE__, $Parameters['Post']);
            return;
        }

        foreach($Parameters['Post']['entry'] as $Entry)
        {
            if(!empty($Entry['messaging']))
                $this->OnMessage($Entry);
            else
            {
                Logger::Log(Log::TYPE_ERROR, 'Событие не поддерживается ' . __FILE__ . ':' . __LINE__, $Parameters['Post']);
                return;
            }
        }


        PrintJson::OperationSuccessful();
    }


    private function OnMessage(array $Event)
    {
        foreach($Event['messaging'] as $MessageObj)
        {
            $User = [];
            $Avatar = null;
            $UserId = (int)($Event['id'] == $MessageObj['sender']['id'] ? (int)$MessageObj['recipient']['id'] : (int)$MessageObj['sender']['id']);
            $InstagramApi = InstagramApi::FindByInstagramUserId((int)$Event['id']);
            if(empty($InstagramApi) || !$InstagramApi->IsActive)
                continue;

            $Facebook = Facebook::FindById($InstagramApi->FacebookId);
            if(empty($Facebook) || !$Facebook->IsActive)
                continue;
            Authentication::SetAuthUser(User::FindById($Facebook->UserId), SYSTEM_SESSION);

            $Dialog = InstagramApiDialog::FindByInstagramApiUserIdAndInstagramApiId($UserId, $InstagramApi->InstagramApiId);
            if(empty($Dialog))
            {
                $Dialog = new InstagramApiDialog();
                $Dialog->SetInstagramApiId((int)$Event['id']);
                $Dialog->SetInstagramApiUserId($UserId);

                
                $FacebookSDK = new FacebookSDK($Facebook);
                $User = $FacebookSDK->get($UserId, null, $InstagramApi->PageId)->getDecodedBody();
                try { $Avatar = (new Http())->SendGet($User['profile_pic']); } catch(Exception $error) {}
            }

            $Message = new Message();
            $Message->SetStatusId(Message::MESSAGE_STATUS_SENT);
            $Message->SetUid($MessageObj['message']['mid']);
            $Message->SetIsMe($Event['id'] == $MessageObj['sender']['id']);
            $Message->SetTime(($MessageObj['timestamp'] / 1000));

            if(!empty($MessageObj['message']['attachments']))
            {
                foreach($MessageObj['message']['attachments'] as $MessageData)
                {
                    $Http = new Http();
                    $DynamicResource = new DynamicResource();
                    $DynamicResource->SetUserId(Authentication::GetAuthUser()->GetId());
                    $DynamicResource->SetResource($Http->SendGet($MessageData['payload']['url']));
                    $DynamicResource->SetType($Http->GetHeaders()['content-type']);
                    $DynamicResource->Save();
                    switch($MessageData['type'])
                    {
                        case 'image':
                            $Message->SetImg($DynamicResource->GetUid());
                            break;

                        case 'video':
                            $Message->SetVideo($DynamicResource->GetUid());
                            break;

                        default:
                            $Message->SetText('Нераспознанное сообщение');
                            break;
                    }
                }
            }
            else
                $Message->SetText($MessageObj['message']['text']);

            if($Message->GetIsMe())
                $Dialog->SetIsActive(true);

            (new MessageController)->OnMessage(
                $Dialog,
                $User['name'],
                $Avatar,
                $Message->GetIsMe(),
                true,
                $Message
            );
        }
    }


    static public function InstagramToArray(InstagramApi $InstagramApi) : array
    {
        $Folder = null;
        try
        {
            $Folder = Folder::FindById($InstagramApi->DefaultFolder);
        }
        catch(Throwable $error) {}

        return Validator::ArrayKeyPascalCaseToSnakeCase(array_merge($InstagramApi->ToArray([
            'UserName',
            'StatusId',
            'IsActive',
            'Status',
            'PictureUrl',
            'InstagramUserId',
            'InstagramApiId',
            'CommentTracking'
        ]), ['Type' => $InstagramApi->GetClassName(), 'DefaultFolder' => !empty($Folder) ? FolderController::ToArray($Folder) : null]));
    }



    private function CreateMessage(array $MessageArray, InstagramApiDialog $InstagramApiDialog) : Message
    {
        $Message = new Message();
        $Message->SetTime(strtotime($MessageArray['created_time']));
        $Message->SetIsMe($InstagramApiDialog->GetInstagramApiUserId() != $MessageArray['from']['id']);
        $Message->SetUid($MessageArray['id']);
        $Message->SetStatusId(Message::MESSAGE_STATUS_READ);
        
        $Http = new Http();
        $DynamicResource = new DynamicResource();
        $DynamicResource->SetUserId(Authentication::GetAuthUser()->GetId());
        switch(array_keys($MessageArray['attachments']['data'][0])[0])
        {
            case 'video_data':
                $DynamicResource->SetResource($Http->SendGet($MessageArray['attachments']['data'][0]['video_data']['url']));
                $DynamicResource->SetType($Http->GetHeaders()['content-type']);
                $DynamicResource->Save();
                $Message->SetVideo($DynamicResource->GetUid());
                break;

            case 'image_data':
                $DynamicResource->SetResource($Http->SendGet($MessageArray['attachments']['data'][0]['image_data']['url']));
                $DynamicResource->SetType($Http->GetHeaders()['content-type']);
                $DynamicResource->Save();
                $Message->SetImg($DynamicResource->GetUid());
                break;

            default:
                if(!empty($MessageArray['message']))
                    $Message->SetText($MessageArray['message']);
                else
                    $Message->SetText('Нераспознанное сообщение');
            break;
        }

        return $Message;
    }
}
