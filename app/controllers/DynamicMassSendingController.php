<?php


namespace controllers;


use classes\StaticResources;
use classes\Validator;
use Exception;
use models\Event;
use models\Authentication;
use models\dialogues\Dialog;
use models\DynamicMassSending;
use models\DynamicResource;
use models\Message;
use models\Folder;
use models\Task;
use views\PrintJson;
use views\View;


class DynamicMassSendingController
{
    public function ActionCreateDynamicMassSending(array $Parameters)
    {
        if(Validator::IsValid($Parameters["Post"], [
            ["Key" => "name",           "StrMax" => 50],
            ["Key" => "message",        "Type" => "array"],
            ["Key" => "send_file_uid"],
            ["Key" => "company_name",   "StrMax" => 50],
            ["Key" => "activity_id",    "Type" => "int",    "IsNull" => true],
            ["Key" => "avatar_uid",                         "IsNull" => true],
            ["Key" => "time_start",     "Type" => "int",    "IsNull" => true],
            ["Key" => "range_work",     "Type" => "array",  "IsNull" => true],
            ["Key" => "send_day",       "Type" => "int",    "IsNull" => true],
            ["Key" => "on_message",     "Type" => "array",  "IsNull" => true]
        ]))
        {
            if(empty(DynamicMassSending::FindByDynamicMassSendingNameAndUserId($Parameters["Post"]["name"])))
            {
                if(empty($Parameters["Post"]["time_start"])) $Parameters["Post"]["time_start"] = time();
                try
                {
                    $DynamicMassSending = $this->EditDynamicMassSending($Parameters);

                    if(DynamicResource::CheckByUid($Parameters["Post"]["send_file_uid"]))
                    {
                        $DynamicMassSending->SetSendFileUid($Parameters["Post"]["send_file_uid"]);
                        $DynamicMassSending->SetStatus("Отключён");
                        // $DynamicMassSending->SetIsEnable(true);

                        
                        $DynamicResource = DynamicResource::FindByUid($Parameters["Post"]["avatar_uid"]);
                        if(empty($DynamicResource))
                        {
                            PrintJson::OperationError(FileFormatInvalid, REQUEST_FAILED);
                            throw new Exception("Resource invalid", REQUEST_FAILED);
                        }
                        $DynamicMassSending->SetAvatar($DynamicResource->GetResource());

                        $DynamicMassSending->SetActivityId((int)$Parameters["Post"]["activity_id"]);
                        $DynamicMassSending->SetCompanyName($Parameters["Post"]["company_name"]);

                        $Folder = new Folder();
                        $Folder->SetName("(Рас.) " . $DynamicMassSending->GetName());
                        $Folder->SetUserId($DynamicMassSending->GetUserId());
                        $Folder->SetEditingPossible(false);
                        $Folder->SetIsIsolated(true);
                        $Folder->SetProperty("FolderForDynamicMassSending", true);
                        $Folder->Save();

                        //Fix
                        $Folder = Folder::FindByNameAndUserId("(Рас.) " . $DynamicMassSending->GetName(), $DynamicMassSending->GetUserId());
                        $DynamicMassSending->SetFolderId($Folder->GetId());
                        
                        $FolderAll = new Folder();
                        $FolderAll->SetName("Основное");
                        $FolderAll->SetUserId($DynamicMassSending->GetUserId());
                        $FolderAll->SetParentFolderId($Folder->GetId());
                        $FolderAll->SetEditingPossible(false);
                        $FolderAll->Save();

                        //Fix
                        $FolderAll = Folder::FindByNameAndUserId("Основное", $DynamicMassSending->GetUserId(), $Folder->GetId());
                        $DynamicMassSending->SetDialogFolderId($FolderAll->GetId());

                        if(!empty($Parameters["Post"]["on_message"]["kostyl_folder_name"]))
                        {
                            $FolderAdd = new Folder();
                            $FolderAdd->SetName($Parameters["Post"]["on_message"]["kostyl_folder_name"]);
                            $FolderAdd->SetUserId($DynamicMassSending->GetUserId());
                            $FolderAdd->SetParentFolderId($Folder->GetId());
                            $FolderAdd->Save();

                            //Fix
                            $FolderAdd = Folder::FindByNameAndUserId($Parameters["Post"]["on_message"]["kostyl_folder_name"], $DynamicMassSending->GetUserId(), $Folder->GetId());
                            
                            $Event = $DynamicMassSending->GetOnMessage();
                            $Event->SetActionData($FolderAdd->GetId());
                            $DynamicMassSending->SetOnMessage($Event);
                        }

                        $DynamicMassSending->SetStatus("Готов к запуску");
                        $DynamicMassSending->Save();
                        PrintJson::OperationSuccessful();
                    }
                    else
                        PrintJson::OperationError(FileFormatInvalid, REQUEST_FAILED);
                }
                catch(Exception $error) {}
            }
            else
                PrintJson::OperationError(DynamicMassSendingIsExist, IS_EXISTS);
        }
    }


    private function EditDynamicMassSending(array $Parameters) : DynamicMassSending
    {
        if(!empty($Parameters["Post"]["dynamic_mass_sending_id"]))
        {
            $DynamicMassSending = DynamicMassSending::FindById($Parameters["Post"]["dynamic_mass_sending_id"]);
            if(empty($DynamicMassSending))
            {
                PrintJson::OperationError(DynamicMassSendingNotFound, NOT_FOUND);
                throw new Exception("DynamicMassSending not found", NOT_FOUND);
            }
        }
        else
            $DynamicMassSending = new DynamicMassSending();
        $DynamicMassSending->SetUserId(Authentication::GetAuthUser()->GetId());

        if(!empty($Parameters["Post"]["name"]) && $Parameters["Post"]["name"] != $DynamicMassSending->GetName())
        {
            if(empty(DynamicMassSending::FindByDynamicMassSendingNameAndUserId($Parameters["Post"]["name"])))
                $DynamicMassSending->SetName($Parameters["Post"]["name"]);
            else
            {
                PrintJson::OperationError(DynamicMassSendingIsExist, REQUEST_FAILED);
                throw new Exception("DynamicMassSending is exist", IS_EXISTS);
            }
        }

        if(isset($Parameters["Post"]["time_start"]))   $DynamicMassSending->SetTimeStart((int)$Parameters["Post"]["time_start"]);
        if(isset($Parameters["Post"]["send_day"]))     $DynamicMassSending->SetSendDay((int)$Parameters["Post"]["send_day"]);

        if(is_array($Parameters["Post"]["range_work"]))
            $DynamicMassSending->SetRangeWork(Validator::ArrayKeySnakeCaseToPascalCase($Parameters["Post"]["range_work"]));

        if(is_array($Parameters["Post"]["on_message"]))
        {
            if(Validator::IsValid($Parameters["Post"]["on_message"], [
                ["Key" => "action_type"],
                ["Key" => "action_data",        "IsNull" => true],
                ["Key" => "kostyl_folder_name", "IsNull" => true]
            ]))
            {
                if(empty($Parameters["Post"]["on_message"]["action_data"]) && empty($Parameters["Post"]["on_message"]["kostyl_folder_name"]))
                {
                    Validator::IsValid($Parameters["Post"]["on_message"], [["Key" => "action_data"]]);
                    throw new Exception("Invalid parameter");
                }

                switch($Parameters["Post"]["on_message"]["action_type"])
                {
                    case Event::ACTION_MOVE_TO_FOLDER:
                        if(Validator::IsValid(["action_data" => $Parameters["Post"]["on_message"]["action_data"]], [
                            ["Key" => "action_data", "Type" => "int", "IsNull" => true]
                        ]))
                        {
                            $Event = new Event();
                            $Event->SetEvent(DynamicMassSending::EVENT_ON_MESSAGE);
                            $Event->SetActionType(Event::ACTION_MOVE_TO_FOLDER);

                            if(empty($Parameters["Post"]["on_message"]["kostyl_folder_name"]))
                                $Event->SetActionData((int)$Parameters["Post"]["on_message"]["action_data"]);

                            $DynamicMassSending->SetOnMessage($Event);
                        }
                        else
                            throw new Exception("Invalid parameter");
                        break;
                }
            }
            else
                throw new Exception("Invalid parameter");
        }

        if(!empty($Parameters["Post"]["message"]))
        {
            $Message = new Message();
            $Message->SetIsMe(true);
            $Message->Read();
            $Message->SetTime(time());
            try
            {
                $Message->SetContent($Parameters["Post"]["message"]["type"], $Parameters["Post"]["message"]["data"]);
            }
            catch(Exception $error)
            {
                PrintJson::OperationError(TypeNotSupported, REQUEST_FAILED);
                throw new Exception("Invalid parameter");
            }

            $DynamicMassSending->SetMessage($Message);
        }

        return $DynamicMassSending;
    }


    public function ActionUpdateDynamicMassSending(array $Parameters)
    {
        if(Validator::IsValid($Parameters["Post"], [
            ["Key" => "name",                       "StrMax" => 50,        "IsNull" => true],
            ["Key" => "message",                    "Type" => "array",      "IsNull" => true],
            ["Key" => "send_file_uid",                                      "IsNull" => true],
            ["Key" => "time_start",                 "Type" => "int",        "IsNull" => true],
            ["Key" => "range_work",                 "Type" => "array",      "IsNull" => true],
            ["Key" => "send_day",                   "Type" => "int",        "IsNull" => true],
            ["Key" => "on_message",                 "Type" => "array",      "IsNull" => true],
            ["Key" => "dynamic_mass_sending_id",    "Type" => "int"]
        ]))
        {
            try
            {
                if(!empty($Parameters["Post"]["on_message"]["kostyl_folder_name"]))
                {
                    PrintJson::OperationError(FileFormatInvalid, REQUEST_FAILED);
                    throw new Exception("Invalid parameter");
                }
                
                $DynamicMassSending = $this->EditDynamicMassSending($Parameters);
                $DynamicMassSending->Save();
                PrintJson::OperationSuccessful();
            }
            catch(Exception $error) {}
        }
    }


    public function ActionStopDynamicMassSending(array $Parameters)
    {
        if(Validator::IsValid($Parameters["Post"], [
            ["Key" => "dynamic_mass_sending_ids", "Type" => "array"]
        ]))
        {
            foreach($Parameters["Post"]["dynamic_mass_sending_ids"] as $DynamicMassSendingId)
            {
                $DynamicMassSending = DynamicMassSending::FindById($DynamicMassSendingId);
                if(!empty($DynamicMassSending))
                {
                    $DynamicMassSending->SetIsEnable(false);
                    $DynamicMassSending->SetStatus("Отключён");
                    $DynamicMassSending->Save();
                }
            }
            PrintJson::OperationSuccessful();
        }
    }


    public function ActionGetDynamicMassSending(array $Parameters)
    {
        if(Validator::IsValid($Parameters["Get"], [
            ["Key" => "dynamic_mass_sending_id", "Type" => "int"]
        ]))
        {
            $DynamicMassSending = DynamicMassSending::FindById($Parameters["Get"]["dynamic_mass_sending_id"]);
            if(!empty($DynamicMassSending))
            {
                $Out = [
                    "dynamic_mass_sending_id"   => $DynamicMassSending->GetId(),
                    "name"                      => $DynamicMassSending->GetName(),
                    "status"                    => $DynamicMassSending->GetStatus(),
                    "sent_count"                => $DynamicMassSending->GetCountSent(),
                    "send_count"                => $DynamicMassSending->GetCountSend(),
                    "updated_at"                => $DynamicMassSending->GetUpdatedAt(),
                    "created_at"                => $DynamicMassSending->GetCreatedAt(),
                    "time_start"                => $DynamicMassSending->GetTimeStart(),
                    "range_work"                => Validator::ArrayKeyPascalCaseToSnakeCase($DynamicMassSending->GetRangeWork()),
                    "send_day"                  => $DynamicMassSending->GetSendDay(),
                    "sent_to_day_count"         => $DynamicMassSending->GetCountSentToDay(),
                    "company_name"              => $DynamicMassSending->GetCompanyName(),
                    "activity_id"               => $DynamicMassSending->GetActivityId(),
                    "folder_id"                 => $DynamicMassSending->GetFolderId(),
                    "dialog_folder_id"          => $DynamicMassSending->GetDialogFolderId(),
                    "avatar"                    => DOMAIN_API_URL . "/get/dynamic-mass-sending/avatar/" . $DynamicMassSending->GetId(),
                    "is_enabled"                => $DynamicMassSending->GetIsEnable(),
                    "message"                   => MessageController::MessageToArray($DynamicMassSending->GetMessage())
                ];

                if(!empty($DynamicMassSending->GetOnMessage()))
                    $Out["event"] = EventController::EventToArray($DynamicMassSending->GetOnMessage());

                PrintJson::OperationSuccessful($Out);
            }
            else
                PrintJson::OperationError(DynamicMassSendingNotFound, NOT_FOUND);
        }
    }


    public function ActionDeleteDynamicMassSending(array $Parameters)
    {
        if(Validator::IsValid($Parameters["Post"], [
            ["Key" => "dynamic_mass_sending_ids", "Type" => "array"]
        ]))
        {
            foreach($Parameters["Post"]["dynamic_mass_sending_ids"] as $DynamicMassSendingId)
            {
                $DynamicMassSending = DynamicMassSending::FindById($DynamicMassSendingId);
                if(!empty($DynamicMassSending))
                    $DynamicMassSending->Delete();
            }

            PrintJson::OperationSuccessful();
        }
    }


    public function ActionStartDynamicMassSending(array $Parameters)
    {
        if(Validator::IsValid($Parameters["Post"], [
            ["Key" => "dynamic_mass_sending_ids", "Type" => "array"]
        ]))
        {
            foreach($Parameters["Post"]["dynamic_mass_sending_ids"] as $DynamicMassSendingId)
            {
                $DynamicMassSending = DynamicMassSending::FindById($DynamicMassSendingId);
                if(!empty($DynamicMassSending))
                {
                    $DynamicMassSending->SetIsEnable(true);
                    $DynamicMassSending->SetStatus("Работает");
                    $DynamicMassSending->Save();

                    $Task = new Task();
                    $Task->SetType("StartDynamicMassSending");
                    $Task->SetData(["DynamicMassSendingId" => $DynamicMassSending->GetId()]);
                    $Task->Save();
                }
            }
            PrintJson::OperationSuccessful();
        }
    }


    public function ActionGetAllDynamicMassSendings(array $Parameters)
    {
        
        $Out = [];
        foreach(DynamicMassSending::FindByUserId() as $DynamicMassSending)
        {
            $Out[] = [
                "dynamic_mass_sending_id"   => $DynamicMassSending->GetId(),
                "name"                      => $DynamicMassSending->GetName(),
                "status"                    => $DynamicMassSending->GetStatus(),
                "sent_count"                => $DynamicMassSending->GetCountSent(),
                "updated_at"                => $DynamicMassSending->GetUpdatedAt(),
                "created_at"                => $DynamicMassSending->GetCreatedAt(),
                "is_enabled"                => $DynamicMassSending->GetIsEnable()
            ];
        }

        PrintJson::OperationSuccessful(["dynamic_mass_sendings" => $Out]);
    }


    public function ActionGetDynamicMassSendingAvatar(array $Parameters)
    {
        if(Validator::IsValid($Parameters["Uri"], [
            ["Key" => 0, "Type" => "int"]
        ]))
        {
            $DynamicMassSending = DynamicMassSending::FindById((int)$Parameters["Uri"][0]);
            if(!empty($DynamicMassSending))
            {
                $Avatar = $DynamicMassSending->GetAvatar();
                View::Print("Image", ["Type" => "jpeg", "Image" => empty($Avatar) ? StaticResources::GetImage(USER_UNKNOWN_AVATAR)->GetResource() : $Avatar]);
            }
            else
                PrintJson::OperationError(DynamicMassSendingNotFound, NOT_FOUND);
        }
    }


    public function OnMessage(Dialog &$Dialog)
    {
        if($Dialog->GetLastMessage()->GetIsMe()) return;

        foreach(DynamicMassSending::FindByUserId() as $DynamicMassSending)
            if($DynamicMassSending->GetCountSent() > 0)
                $DynamicMassSending->RunOnMessage($Dialog);
    }


    public function OnRead(Dialog &$Dialog)
    {
        if($Dialog->GetLastMessage()->GetIsMe()) return;

        foreach(DynamicMassSending::FindByUserId() as $DynamicMassSending)
            if($DynamicMassSending->GetCountSent() > 0)
                $DynamicMassSending->RunOnRead($Dialog);
    }
}