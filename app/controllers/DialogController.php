<?php


namespace controllers;


use classes\StaticResources;
use classes\Validator;
use Exception;
use models\Authentication;
use models\dialogues\Dialog;
use models\dialogues\InstagramApiDialog;
use models\Folder;
use models\Instagram;
use models\dialogues\InstagramDialog;
use models\dialogues\LocalDialog;
use models\Whatsapp;
use models\dialogues\WhatsappDialog;
use models\InstagramApi;
use views\PrintJson;
use views\View;


class DialogController
{
    public function ActionGetAvatar(array $Parameters)
    {
        if(Validator::IsValid($Parameters["Uri"], [
            ["Key" => 0, "Type" => "int"]
        ]))
        {
            $Avatar = null;

            $Dialog = Dialog::FindById((int)$Parameters["Uri"][0]);
            if(!empty($Dialog))
                $Avatar = $Dialog->GetAvatar();
            
            if(empty($Avatar) || $Avatar == "null")
                $Avatar = StaticResources::GetImage(DIALOG_UNKNOWN_AVATAR)->GetResource();

            View::Print("Image", ["Type" => "jpeg", "Image" => $Avatar]);
        }
    }


    public function ActionDeleteDialog(array $Parameters)
    {
        if(Validator::IsValid($Parameters["Post"], [
            ["Key" => "dialog_ids", "Type" => "array"]
        ]))
        {
            foreach($Parameters["Post"]["dialog_ids"] as $DialogId)
            {
                $Dialog = Dialog::FindById($DialogId);
                if(!empty($Dialog))
                    $Dialog->Delete();
            }

            PrintJson::OperationSuccessful();
        }
    }


    public function ActionDischargeFolderDialog(array $Parameters)
    {
        if(Validator::IsValid($Parameters["Post"], [
            ["Key" => "dialog_ids", "Type" => "array"]
        ]))
        {
            foreach($Parameters["Post"]["dialog_ids"] as $DialogId)
            {
                $Dialog = Dialog::FindById($DialogId);
                if(!empty($Dialog))
                {
                    
                    $Folder = Folder::FindById($Dialog->GetFolderId());
                    if(!empty($Folder) && $Folder->IsIsolatedRecursively())
                    {
                        PrintJson::OperationError(OperationError, ACCESS_DENIED);
                        return;
                    }
                    
                    $Dialog->SetFolderId(Folder::FindDefault(Authentication::GetAuthUser()->GetId())->GetId());
                    $Dialog->Save();
                }
            }

            PrintJson::OperationSuccessful();
        }
    }


    public function ActionImportBase(array $Parameters)
    {
        if(Validator::IsValid($Parameters["Post"], [
            ["Key" => "file_uid"],
            ["Key" => "type"],
            ["Key" => "folder_id",      "Type" => "int",    "IsNull" => true],
            ["Key" => "whatsapp_id",    "Type" => "int",    "IsNull" => true],
            ["Key" => "instagram_id",    "Type" => "int",    "IsNull" => true],
        ]))
        {
            if(!empty($Parameters["Post"]["folder_id"]))
            {
                $Folder = Folder::FindById((int)$Parameters["Post"]["folder_id"]);
                if(empty($Folder))
                {
                    PrintJson::OperationError(FolderNotFound, NOT_FOUND);
                    return;
                }
            }
            try
            {
                //FixDialogues
                switch($Parameters["Post"]["type"])
                {
                    case 'WhatsappDialog':
                        $Whatsapp = Whatsapp::FindById((int)$Parameters["Post"]["whatsapp_id"]);
                        if(empty($Whatsapp))
                        {
                            PrintJson::OperationError(WhatsappNotFound, NOT_FOUND);
                            return;
                        }
                        WhatsappDialog::ImportBase($Parameters["Post"]["file_uid"], (int)$Parameters["Post"]["whatsapp_id"], (int)$Parameters["Post"]["folder_id"]);
                        break;

                    case 'InstagramDialog':
                        $Instagram = Instagram::FindById((int)$Parameters["Post"]["instagram_id"]);
                        if(empty($Instagram))
                        {
                            PrintJson::OperationError(InstagramNotFound, NOT_FOUND);
                            return;
                        }
                        InstagramDialog::ImportBase($Parameters["Post"]["file_uid"], (int)$Parameters["Post"]["instagram_id"], (int)$Parameters["Post"]["folder_id"]);
                        break;

                    default:
                        PrintJson::OperationError(OperationError, REQUEST_FAILED);
                        return;
                    break;
                }
                
                PrintJson::OperationSuccessful();
            }
            catch(Exception $error)
            {
                PrintJson::OperationError(FileFormatInvalid, REQUEST_FAILED);
            }
        }
    }


    public function ActionCreateDialog(array $Parameters)
    {
        if(Validator::IsValid($Parameters["Post"], [
            ["Key" => "phone",                              "IsNull" => true],
            ["Key" => "name",                               "IsNull" => true],
            ["Key" => "type"],
            ["Key" => "login",                              "IsNull" => true],
            ["Key" => "whatsapp_id",    "Type" => "int",    "IsNull" => true],
            ["Key" => "instagram_id",   "Type" => "int",    "IsNull" => true],
            ["Key" => "folder_id",      "Type" => "int",    "IsNull" => true]
        ]))
        {
            //FixDialogues
            if(empty((int)$Parameters["Post"]["whatsapp_id"]) && empty((int)$Parameters["Post"]["instagram_id"]))
            {
                PrintJson::OperationError(OperationError, REQUEST_FAILED);
                return;
            }

            $Dialog = null;

            switch($Parameters["Post"]["type"])
            {
                case 'WhatsappDialog':
                    try
                    {
                        if(Whatsapp::FindById((int)$Parameters["Post"]["whatsapp_id"]) == null)
                        {
                            PrintJson::OperationError(WhatsappNotFound, NOT_FOUND);
                            return;
                        }
                    }
                    catch(Exception $erro)
                    {
                        PrintJson::OperationError(AccessIsDenied, ACCESS_DENIED);
                        return;
                    }

                    $Phone = Validator::NormalizePhone($Parameters["Post"]["phone"]);
                    if(WhatsappDialog::FindByPhoneAndWhatsappId($Phone, (int)$Parameters["Post"]["whatsapp_id"]) == null)
                    {
                        $Dialog = new WhatsappDialog();
                        $Dialog->SetPhone($Phone);
                        $Dialog->SetWhatsappId((int)$Parameters["Post"]["whatsapp_id"]);
                        $Dialog->SetName(empty($Parameters["Post"]["name"]) ? $Parameters["Post"]["phone"] : $Parameters["Post"]["name"]);
                    }

                    break;
                    
                case 'InstagramDialog':
                    try
                    {
                        if(Instagram::FindById((int)$Parameters["Post"]["instagram_id"]) == null)
                        {
                            PrintJson::OperationError(InstagramNotFound, NOT_FOUND);
                            return;
                        }
                    }
                    catch(Exception $erro)
                    {
                        PrintJson::OperationError(AccessIsDenied, ACCESS_DENIED);
                        return;
                    }

                    if(InstagramDialog::FindByLoginAndInstagramId($Parameters["Post"]["login"], (int)$Parameters["Post"]["instagram_id"]) == null)
                    {
                        $Dialog = new InstagramDialog();
                        $Dialog->SetLogin($Parameters["Post"]["login"]);
                        $Dialog->SetInstagramId((int)$Parameters["Post"]["instagram_id"]);
                        $Dialog->SetName(empty($Parameters["Post"]["name"]) ? $Parameters["Post"]["login"] : $Parameters["Post"]["name"]);
                    }
                    break;

                default:
                    PrintJson::OperationError(OperationError, REQUEST_FAILED);
                    return;
                break;
            }

            if(empty($Dialog))
            {
                PrintJson::OperationError(DialogExists, REQUEST_FAILED);
                return;
            }

            if(!empty($Parameters["Post"]["folder_id"]))
            {
                $Folder = Folder::FindById((int)$Parameters["Post"]["folder_id"]);
                if(!empty($Folder))
                    $Dialog->SetFolderId($Folder->GetId());
                else
                {
                    PrintJson::OperationError(FolderNotFound, NOT_FOUND);
                    return;
                }
            }

            $Dialog->Save();
            PrintJson::OperationSuccessful();
        }
    }

    
    //FixDialogues
    private function GetAllDialogues(?int $UserId, ?int $Offset = null, ?int $Limit = null, bool $CheckAccess = true) : array
    {
        //Неверная пагинация из-за разных моделей
        return array_merge(
            WhatsappDialog::FindAllByUserId($UserId, $Offset, $Limit, $CheckAccess),
            InstagramDialog::FindAllByUserId($UserId, $Offset, $Limit, $CheckAccess),
            InstagramApiDialog::FindAllByUserId($UserId, $Offset, $Limit, $CheckAccess),
            LocalDialog::FindAllByUserId($UserId, $Offset, $Limit, $CheckAccess)
        );
    }


    public function ActionSearchDialog(array $Parameters)
    {
        if(Validator::IsValid($Parameters["Get"], [
            ["Key" => "offset",     "Type" => "int", "IntMin" => 0],
            ["Key" => "limit",      "Type" => "int", "IntMin" => 1, "IntMax" => 30],
            ["Key" => "phone",      "IsNull" => true],
            ["Key" => "login",      "IsNull" => true],
            ["Key" => "name",       "IsNull" => true],
            ["Key" => "messages",   "IsNull" => true],
            ["Key" => "search",     "IsNull" => true]
        ]))
        {
            $Out = [];
            if(empty($Parameters["Get"]["search"]))
                foreach($this->GetAllDialogues(null, (int)$Parameters["Get"]["offset"], (int)$Parameters["Get"]["limit"]) as $Dialog)
                    $Out[] = array_merge(self::DialogToArray($Dialog), ["folder" => $this->GetFolderArray($Dialog->GetFolderId())]);
            else
            {
                foreach (Dialog::FindAllBySearchQuery($Parameters["Get"]["search"], $Parameters["Get"]["phone"] == "true", $Parameters["Get"]["login"] == "true", $Parameters["Get"]["name"] == "true", $Parameters["Get"]["messages"] == "true", (int)$Parameters["Get"]["offset"], (int)$Parameters["Get"]["limit"]) as $Dialog)
                    $Out[] = array_merge(self::DialogToArray($Dialog), ["folder" => $this->GetFolderArray($Dialog->GetFolderId())]);
            }

            PrintJson::OperationSuccessful(["dialogues" => $Out]);
        }
    }


    private function GetFolderArray(int $FolderId) : array
    {
        try
        {
            return FolderController::ToArray(Folder::FindById((int)$FolderId));
        }
        catch(Exception $error){}
        return [];
    }


    public function ActionDialogSetRead(array $Parameters)
    {
        if(Validator::IsValid($Parameters["Post"], [
            ["Key" => "dialog_ids", "Type" => "array"]
        ]))
        {
            foreach($Parameters["Post"]["dialog_ids"] as $DialogId)
            {
                $Dialog = Dialog::FindById($DialogId);
                if(!empty($Dialog))
                {
                    $Dialog->Read();
                    $Dialog->Save();
                }
            }

            PrintJson::OperationSuccessful();
        }
    }


    public function ActionGetDialogStatus(array $Parameters)
    {
        if(Validator::IsValid($Parameters["Post"], [
            ["Key" => "dialog_id", "Type" => "int"]
        ]))
        {
            //FixDialogues
            $Dialog = Dialog::FindById((int)$Parameters["Post"]["dialog_id"]);
            if(!empty($Dialog))
            {
                $IsOnline = false;
                try
                {
                    switch($Dialog->GetType())
                    {
                        case WhatsappDialog::class:
                            $IsOnline = (new VenomBotController())->GetIsOnline($Dialog);
                            break;

                        case InstagramDialog::class:
                            $IsOnline = (new InstagramSdkController())->GetIsOnline($Dialog);
                            break;
                    }
                }
                catch(Exception $error) {}
                
                $Dialog->SetIsOnline($IsOnline);
                $Dialog->Save();
                PrintJson::OperationSuccessful(["dialog" => $this->DialogToArray($Dialog)]);
            }
            else
                PrintJson::OperationError(DialogNotFound, NOT_FOUND);
        }
    }


    public function ActionGetDialog(array $Parameters)
    {
        if(Validator::IsValid($Parameters["Get"], [
            ["Key" => "dialog_id", "Type" => "int"]
        ]))
        {
            $Dialog = Dialog::FindById($Parameters["Get"]["dialog_id"]);
            if(!empty($Dialog))
            {
                $Dialog->Read();
                $Dialog->Save();
                $Out = [
                    "avatar"        => DOMAIN_API_URL . "/get/dialog/avatar/" . $Dialog->GetId(),
                    "name"          => $Dialog->GetName(),
                    "type"          => $Dialog->GetNameType(),
                    "dialog_id"     => $Dialog->GetId(),
                    "is_online"     => $Dialog->GetIsOnline(),
                    "folder_id"     => $Dialog->GetFolderId(),
                    "tags"          => $Dialog->GetTags(),
                    "is_read"       => $Dialog->GetIsRead(),
                    "unread"        => $Dialog->GetCountUnreadMessage(),
                    "is_active"     => $Dialog->GetIsActive(),
                    "messages"       => [],
                ];

                //FixDialogues
                if($Dialog instanceof WhatsappDialog)
                {
                    $Out["whatsapp_id"] = $Dialog->GetWhatsappId();
                    $Out["whatsapp"] = (new WhatsappController())->WhatsappToArray(Whatsapp::FindById($Dialog->GetWhatsappId()));
                }
                else if($Dialog instanceof InstagramDialog)
                {
                    $Out["instagram_id"] = $Dialog->GetInstagramId();
                    $Out["instagram"] = (new InstagramController())->InstagramToArray(Instagram::FindById($Dialog->GetInstagramId()));
                }
                else if($Dialog instanceof InstagramApiDialog)
                {
                    $Out["instagram_api_id"] = $Dialog->GetInstagramApiId();
                    $Out["instagram_api"] = (new InstagramApiController())->InstagramToArray(InstagramApi::FindById($Dialog->GetInstagramApiId()));

                    (new InstagramApiController())->SyncMessages($Dialog);
                }

                foreach($Dialog->GetMessages(0, 10, true) as $Message)
                    $Out["messages"][] = MessageController::MessageToArray($Message, $Dialog);

                PrintJson::OperationSuccessful($Out);
            }
            else
                PrintJson::OperationError(DialogNotFound, NOT_FOUND);
        }
    }


    public function ActionGetMessages(array $Parameters)
    {
        if(Validator::IsValid($Parameters["Get"], [
            ["Key" => "dialog_id", "Type" => "int"],
            ["Key" => "offset", "Type" => "int", "IntMin" => 0],
            ["Key" => "limit", "Type" => "int", "IntMin" => 1]
        ]))
        {
            $Dialog = Dialog::FindById($Parameters["Get"]["dialog_id"]);
            if(!empty($Dialog))
            {
                $Messages = [];

                foreach($Dialog->GetMessages((int)$Parameters["Get"]["offset"], (int)$Parameters["Get"]["limit"], true) as $Message)
                    $Messages[] = MessageController::MessageToArray($Message, $Dialog);

                PrintJson::OperationSuccessful(['messages' => $Messages]);
            }
            else
                PrintJson::OperationError(DialogNotFound, NOT_FOUND);
        }
    }


    private static function WhatsappToArray(int $WhatsappId) : array
    {
        try
        {
            return WhatsappController::WhatsappToArray(Whatsapp::FindById($WhatsappId));
        }
        catch(Exception $error) {}
        return [];
    }


    public function ActionMoveDialog(array $Parameters)
    {
        if(Validator::IsValid($Parameters["Post"], [
            ["Key" => "dialog_ids", "Type" => "array"],
            ["Key" => "folder_id", "Type" => "int"]
        ]))
        {
            foreach($Parameters["Post"]["dialog_ids"] as $DialogId)
            {
                $Dialog = Dialog::FindById($DialogId);
                if(!empty($Dialog))
                {
                    
                    $Folder = Folder::FindById((int)$Parameters["Post"]["folder_id"]);
                    $FolderOld = Folder::FindById($Dialog->GetFolderId());

                    if($FolderOld->IsIsolatedRecursively() && !$FolderOld->IsExistsInFolderRecursively($Folder))
                    {
                        PrintJson::OperationError(OperationError, ACCESS_DENIED);
                        return;
                    }

                    if($Folder->IsIsolatedRecursively() && !$Folder->IsExistsInFolderRecursively($FolderOld))
                    {
                        PrintJson::OperationError(OperationError, ACCESS_DENIED);
                        return;
                    }

                    $Dialog->SetFolderId($Folder->GetId());
                    $Dialog->Save();
                }
            }
            PrintJson::OperationSuccessful();
        }
    }


    static public function DialogToArray(Dialog $Dialog) : array
    {
        //FixDialogues
        $Out = [
            "avatar"        => DOMAIN_API_URL . "/get/dialog/avatar/" . $Dialog->GetId(),
            "type"          => $Dialog->GetNameType(),
            "name"          => $Dialog->GetName(),
            "dialog_id"     => $Dialog->GetId(),
            "is_online"     => $Dialog->GetIsOnline(),
            "folder_id"     => $Dialog->GetFolderId(),
            "tags"          => $Dialog->GetTags(),
            "is_read"       => $Dialog->GetIsRead(),
            "is_active"     => $Dialog->GetIsActive(),
            "unread"        => $Dialog->GetCountUnreadMessage(),
            "last_message"  => null,
        ];

        if(!empty($Dialog->GetLastMessage()))
        {
            try
            {
                $Out["last_message"] = MessageController::MessageToArray($Dialog->GetLastMessage(), $Dialog);
            }
            catch(Exception $error) {}
        }

        if($Dialog instanceof WhatsappDialog)
            $Out["whatsapp_id"] = $Dialog->GetWhatsappId();
        else if($Dialog instanceof InstagramDialog)
            $Out["instagram_id"] = $Dialog->GetInstagramId();
        else if($Dialog instanceof InstagramApiDialog)
            $Out["instagram_api_id"] = $Dialog->GetInstagramApiId();
            
        return $Out;
    }
}