<?php


namespace controllers;


use classes\Validator;
use Exception;
use models\Event;
use models\dialogues\Dialog;
use models\MassSending;
use models\Message;
use views\PrintJson;


class MassSendingController
{
    public function ActionCreateMassSending(array $Parameters)
    {
        if(Validator::IsValid($Parameters["Post"], [
            ["Key" => "name"],
            ["Key" => "interval",       "Type" => "int"],
            ["Key" => "message",        "Type" => "array"],
            ["Key" => "send",           "Type" => "array"],
            ["Key" => "time_start",     "Type" => "int",    "IsNull" => true],
            ["Key" => "range_work",     "Type" => "array",  "IsNull" => true],
            ["Key" => "send_day",       "Type" => "int",    "IsNull" => true],
            ["Key" => "on_message",     "Type" => "array",  "IsNull" => true],
        ]))
        {
            if(Validator::IsValid($Parameters["Post"]["send"], [
                ["Key" => "folder_ids", "Type" => "array", "IsNull" => true],
                ["Key" => "dialog_ids", "Type" => "array", "IsNull" => true]
            ]))
            {
                if(empty($Parameters["Post"]["send"]["folder_ids"]) && empty($Parameters["Post"]["send"]["dialog_ids"]))
                {
                    PrintJson::OperationError(ParameterIsNull . ' "folder_ids and dialog_ids"', REQUEST_FAILED);
                    return;
                }

                if(empty(MassSending::FindByMassSendingNameAndUserId($Parameters["Post"]["name"])))
                {
                    if(empty($Parameters["Post"]["time_start"])) $Parameters["Post"]["time_start"] = time();
                    try
                    {
                        $MassSending = $this->EditMassSending($Parameters);
                        $MassSending->Status = "Готово к запуску";
                        $MassSending->Save();
                        PrintJson::OperationSuccessful();
                    }
                    catch(Exception $error) {}
                }
            }
            else
                PrintJson::OperationError(MassSendingIsExist, REQUEST_FAILED);
        }
    }


    private function EditMassSending(array $Parameters) : MassSending
    {
        if(!empty($Parameters["Post"]["mass_sending_id"]))
        {
            $MassSending = MassSending::FindById($Parameters["Post"]["mass_sending_id"]);
            if(empty($MassSending))
            {
                PrintJson::OperationError(MassSendingNotFound, NOT_FOUND);
                throw new Exception("MassSending not found", NOT_FOUND);
            }
        }
        else
            $MassSending = new MassSending();

        if(!empty($Parameters["Post"]["name"]) && $Parameters["Post"]["name"] != $MassSending->Name)
        {
            if(empty(MassSending::FindByMassSendingNameAndUserId($Parameters["Post"]["name"])))
                $MassSending->Name = $Parameters["Post"]["name"];
            else
            {
                PrintJson::OperationError(MassSendingIsExist, REQUEST_FAILED);
                throw new Exception("MassSending is exist", IS_EXISTS);
            }
        }
        if(isset($Parameters["Post"]["time_start"]))   $MassSending->TimeStart = (int)$Parameters["Post"]["time_start"];
        if(isset($Parameters["Post"]["interval"]))     $MassSending->Interval = ($Parameters["Post"]["interval"]);
        if(isset($Parameters["Post"]["send"]))         $MassSending->Send = Validator::ArrayKeySnakeCaseToPascalCase($Parameters["Post"]["send"]);
        if(isset($Parameters["Post"]["send_day"]))     $MassSending->SendDay = (int)$Parameters["Post"]["send_day"];

        if(is_array($Parameters["Post"]["range_work"]))
            $MassSending->RangeWork = Validator::ArrayKeySnakeCaseToPascalCase($Parameters["Post"]["range_work"]);

        if(is_array($Parameters["Post"]["on_message"]))
        {
            if(Validator::IsValid($Parameters["Post"]["on_message"], [
                ["Key" => "action_type"],
                ["Key" => "action_data"]
            ]))
            {
                switch($Parameters["Post"]["on_message"]["action_type"])
                {
                    case Event::ACTION_MOVE_TO_FOLDER:
                        if(Validator::IsValid(["action_data" => $Parameters["Post"]["on_message"]["action_data"]], [
                            ["Key" => "action_data", "Type" => "int"]
                        ]))
                        {
                            $Event = new Event();
                            $Event->SetEvent(MassSending::EVENT_ON_MESSAGE);
                            $Event->SetActionType(Event::ACTION_MOVE_TO_FOLDER);
                            $Event->SetActionData((int)$Parameters["Post"]["on_message"]["action_data"]);

                            $MassSending->OnMessage = $Event;
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

            $MassSending->Message = $Message;
        }

        return $MassSending;
    }


    public function ActionUpdateMassSending(array $Parameters)
    {
        if(Validator::IsValid($Parameters["Post"], [
            ["Key" => "name",                                       "IsNull" => true],
            ["Key" => "interval",           "Type" => "int",        "IsNull" => true],
            ["Key" => "message",            "Type" => "array",      "IsNull" => true],
            ["Key" => "send",               "Type" => "array",      "IsNull" => true],
            ["Key" => "time_start",         "Type" => "int",        "IsNull" => true],
            ["Key" => "range_work",         "Type" => "array",      "IsNull" => true],
            ["Key" => "send_day",           "Type" => "int",        "IsNull" => true],
            ["Key" => "on_message",         "Type" => "array",      "IsNull" => true],
            ["Key" => "mass_sending_id",    "Type" => "int"]
        ]))
        {
            if(!empty($Parameters["Post"]["send"]))
                if(!Validator::IsValid($Parameters["Post"]["send"], [
                    ["Key" => "folder_ids", "Type" => "array", "IsNull" => true],
                    ["Key" => "dialog_ids", "Type" => "array", "IsNull" => true]
                ]))
                    return;

            try
            {
                $MassSending = $this->EditMassSending($Parameters);
                $MassSending->Save();
                PrintJson::OperationSuccessful();
            }
            catch(Exception $error) {}
        }
    }


    public function ActionStopMassSending(array $Parameters)
    {
        if(Validator::IsValid($Parameters["Post"], [
            ["Key" => "mass_sending_ids", "Type" => "array"]
        ]))
        {
            foreach($Parameters["Post"]["mass_sending_ids"] as $MassSendingId)
            {
                $MassSending = MassSending::FindById($MassSendingId);
                if(!empty($MassSending))
                {
                    $MassSending->IsEnable = false;
                    $MassSending->Status = "Отключён";
                    $MassSending->Save();
                }
            }
            PrintJson::OperationSuccessful();
        }
    }


    public function ActionGetMassSending(array $Parameters)
    {
        if(Validator::IsValid($Parameters["Get"], [
            ["Key" => "mass_sending_id", "Type" => "int"]
        ]))
        {
            $MassSending = MassSending::FindById($Parameters["Get"]["mass_sending_id"]);
            if(!empty($MassSending))
            {
                $Out = Validator::ArrayKeyPascalCaseToSnakeCase($MassSending->ToArray(
                    [
                        'MassSendingId',
                        'Name',
                        'Status',
                        'SentCount',
                        'Send',
                        'UpdatedAt',
                        'CreatedAt',
                        'Interval',
                        'TimeStart',
                        'RangeWork',
                        'SendDay',
                    ]
                ));
                $Out['message'] = MessageController::MessageToArray($MassSending->Message);
                $Out['is_enabled'] = $MassSending->IsEnable;

                if(!empty($MassSending->OnMessage))
                    $Out["event"] = EventController::EventToArray($MassSending->OnMessage);

                PrintJson::OperationSuccessful($Out);
            }
            else
                PrintJson::OperationError(MassSendingNotFound, NOT_FOUND);
        }
    }


    public function ActionDeleteMassSending(array $Parameters)
    {
        if(Validator::IsValid($Parameters["Post"], [
            ["Key" => "mass_sending_ids", "Type" => "array"]
        ]))
        {
            foreach($Parameters["Post"]["mass_sending_ids"] as $MassSendingId)
            {
                $MassSending = MassSending::FindById($MassSendingId);
                if(!empty($MassSending))
                    $MassSending->Delete();
            }

            PrintJson::OperationSuccessful();
        }
    }


    public function ActionStartMassSending(array $Parameters)
    {
        if(Validator::IsValid($Parameters["Post"], [
            ["Key" => "mass_sending_ids", "Type" => "array"]
        ]))
        {
            foreach($Parameters["Post"]["mass_sending_ids"] as $MassSendingId)
            {
                $MassSending = MassSending::FindById($MassSendingId);
                if(!empty($MassSending))
                {
                    $MassSending->IsEnable = true;
                    $MassSending->Status = "Работает";
                    $MassSending->Save();
                }
            }
            PrintJson::OperationSuccessful();
        }
    }


    public function ActionGetAllMassSendings(array $Parameters)
    {
        
        $Out = [];
        foreach(MassSending::FindByUserId() as $MassSending)
        {
            $Out[] = array_merge(Validator::ArrayKeyPascalCaseToSnakeCase($MassSending->ToArray(
                [
                    'MassSendingId',
                    'Name',
                    'Status',
                    'SentCount',
                    'UpdatedAt',
                    'CreatedAt',
                ]
            )), ['is_enabled' => $MassSending->IsEnable]);
        }

        PrintJson::OperationSuccessful(["mass_sendings" => $Out]);
    }


    public function OnMessage(Dialog &$Dialog)
    {
        if($Dialog->GetLastMessage()->GetIsMe()) return;

        foreach(MassSending::FindByUserId() as $MassSending)
            if($MassSending->SentCount > 0)
                $MassSending->RunOnMessage($Dialog);
    }
}