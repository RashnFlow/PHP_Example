<?php


namespace controllers;

use classes\Log;
use classes\Logger;
use classes\Validator;
use Exception;
use models\Autoresponder;
use models\dialogues\Dialog;
use models\Folder;
use models\Message;
use Throwable;
use views\PrintJson;

class AutoresponderController
{
    static public function ActionCreate(array $Parameters)
    {
        if(Validator::IsValid($Parameters['Post'], [
            ['Key' => 'name', 'StrMax' => 100],
            ['Key' => 'event', 'StrMax' => 100],
            ['Key' => 'message', 'Type' => 'array'],
            ['Key' => 'folder_ids', 'Type' => 'array'],
            ['Key' => 'range_work', 'Type' => 'array', 'IsNull' => true],
        ]))
        {
            if(!empty(Autoresponder::FindByNameAndUserId($Parameters['Post']['name'])))
            {
                PrintJson::OperationError(AutoresponderIsExist, REQUEST_FAILED);
                return;
            }

            try
            {
                $Autoresponder = new Autoresponder();
                $Autoresponder->Name = $Parameters['Post']['name'];
                self::Edit($Autoresponder, Validator::ArrayKeySnakeCaseToPascalCase($Parameters['Post']));
                $Autoresponder->Save();
                PrintJson::OperationSuccessful();
            }
            catch(Exception $error)
            {
                if($error->getCode() == REQUEST_FAILED)
                    PrintJson::OperationError($error->getMessage(), REQUEST_FAILED);
                else
                    throw $error;
            }
        }
    }


    static public function ActionEdit(array $Parameters)
    {
        if(Validator::IsValid($Parameters['Post'], [
            ['Key' => 'name', 'StrMax' => 100, 'IsNull' => true],
            ['Key' => 'autoresponder_id', 'Type' => 'int'],
            ['Key' => 'event', 'StrMax' => 100, 'IsNull' => true],
            ['Key' => 'message', 'Type' => 'array', 'IsNull' => true],
            ['Key' => 'folder_ids', 'Type' => 'array', 'IsNull' => true],
            ['Key' => 'range_work', 'Type' => 'array', 'IsNull' => true],
        ]))
        {
            try
            {
                $Autoresponder = Autoresponder::FindById((int)$Parameters['Post']['autoresponder_id']);

                if(empty($Autoresponder))
                {
                    PrintJson::OperationError(AutoresponderNotFound, NOT_FOUND);
                    return;
                }

                if(!empty($Parameters['Post']['name']))
                {
                    $Temp = Autoresponder::FindByNameAndUserId($Parameters['Post']['name']);
                    if(!empty($Temp) && $Temp->AutoresponderId != $Autoresponder->AutoresponderId)
                    {
                        PrintJson::OperationError(AutoresponderIsExist, REQUEST_FAILED);
                        return;
                    }
                    else
                        $Autoresponder->Name = $Parameters['Post']['name'];
                }

                self::Edit($Autoresponder, Validator::ArrayKeySnakeCaseToPascalCase($Parameters['Post']));
                $Autoresponder->Save();
                PrintJson::OperationSuccessful();
            }
            catch(Exception $error)
            {
                if($error->getCode() == REQUEST_FAILED)
                    PrintJson::OperationError($error->getMessage(), REQUEST_FAILED);
                else
                    throw $error;
            }
        }
    }


    static private function Edit(Autoresponder &$Autoresponder, array $Parameters)
    {
        if(empty($Parameters['Event']) && empty($Autoresponder->Event))
            throw new Exception('Event is empty', REQUEST_FAILED);

        if(!empty($Parameters['Event']))
        {
            if(!in_array($Parameters['Event'], Autoresponder::SUPPORTED_EVENTS))
                throw new Exception('Invalid Event', REQUEST_FAILED);

            if($Parameters['Event'] == Autoresponder::AUTORESPONDER_EVENT_ON_MOVED && empty((int)$Parameters['FolderIds']))
                throw new Exception('FolderId is empty', REQUEST_FAILED);

            $Autoresponder->Event = $Parameters['Event'];
        }



        if(empty($Parameters['Message']) && empty($Autoresponder->Message))
            throw new Exception('Message is empty', REQUEST_FAILED);

        if(!empty($Parameters['Message']))
        {
            try
            {
                $Message = new Message();
                $Message->SetContent($Parameters['Message']['Type'], $Parameters['Message']['Data'], $Parameters['Message']['Caption']);
            }
            catch(Throwable $error)
            {
                throw new Exception('Invalid Message', REQUEST_FAILED);
            }

            $Autoresponder->Message = $Message;
        }



        if(empty($Parameters['FolderIds']) && empty($Autoresponder->FolderIds))
            throw new Exception('FolderIds is empty', REQUEST_FAILED);

        if(!empty($Parameters['FolderIds']))
        {
            foreach($Parameters['FolderIds'] as $id)
                if(empty(Folder::FindById((int)$id)))
                    throw new Exception('Invalid FolderId', REQUEST_FAILED);

            $Autoresponder->FolderIds = $Parameters['FolderIds'];
        }



        if(!empty($Parameters['RangeWork']))
        {
            if(!Validator::IsValid($Parameters['RangeWork'], [
                ['Key' => 'Stop'],
                ['Key' => 'Start'],
            ], false))
                throw new Exception('Invalid RangeWork', REQUEST_FAILED);

            if(!preg_match("/^\d+:\d+$/", $Parameters['RangeWork']['Stop']) || strtotime($Parameters['RangeWork']['Stop']) === false)
                throw new Exception('Invalid RangeWork => Stop', REQUEST_FAILED);

            if(!preg_match("/^\d+:\d+$/", $Parameters['RangeWork']['Start']) || strtotime($Parameters['RangeWork']['Start']) === false)
                throw new Exception('Invalid RangeWork => Start', REQUEST_FAILED);

            $Autoresponder->RangeWork = $Parameters['RangeWork'];
        }
    }


    static public function ActionDelete(array $Parameters)
    {
        if(Validator::IsValid($Parameters['Post'], [
            ['Key' => 'autoresponder_id', 'Type' => 'int']
        ]))
        {
            $Autoresponder = Autoresponder::FindById((int)$Parameters['Post']['autoresponder_id']);

            if(empty($Autoresponder))
            {
                PrintJson::OperationError(AutoresponderNotFound, NOT_FOUND);
                return;
            }

            $Autoresponder->Delete();
            PrintJson::OperationSuccessful();
        }
    }


    static public function ActionStop(array $Parameters)
    {
        if(Validator::IsValid($Parameters['Post'], [
            ['Key' => 'autoresponder_id', 'Type' => 'int']
        ]))
        {
            $Autoresponder = Autoresponder::FindById((int)$Parameters['Post']['autoresponder_id']);

            if(empty($Autoresponder))
            {
                PrintJson::OperationError(AutoresponderNotFound, NOT_FOUND);
                return;
            }

            $Autoresponder->IsEnable = false;
            $Autoresponder->Save();
            PrintJson::OperationSuccessful();
        }
    }


    static public function ActionStart(array $Parameters)
    {
        if(Validator::IsValid($Parameters['Post'], [
            ['Key' => 'autoresponder_id', 'Type' => 'int']
        ]))
        {
            $Autoresponder = Autoresponder::FindById((int)$Parameters['Post']['autoresponder_id']);

            if(empty($Autoresponder))
            {
                PrintJson::OperationError(AutoresponderNotFound, NOT_FOUND);
                return;
            }

            $Autoresponder->IsEnable = true;
            $Autoresponder->Save();
            PrintJson::OperationSuccessful();
        }
    }


    static public function ActionGetAll(array $Parameters)
    {
        $Out = [];
        foreach(Autoresponder::FindAllByUserId() as $Autoresponder)
            $Out[] = self::AutoresponderToArray($Autoresponder);

        PrintJson::OperationSuccessful(['autoresponders' => $Out]);
    }


    static public function ActionGet(array $Parameters)
    {
        if(Validator::IsValid($Parameters['Get'], [
            ['Key' => 'autoresponder_id', 'Type' => 'int']
        ]))
        {
            $Autoresponder = Autoresponder::FindById((int)$Parameters['Get']['autoresponder_id']);

            if(empty($Autoresponder))
            {
                PrintJson::OperationError(AutoresponderNotFound, NOT_FOUND);
                return;
            }

            $Out = $Autoresponder->ToArray([
                'AutoresponderId',
                'Name',
                'Event',
                'Status',
                'FolderIds',
                'UserId',
                'IsEnable',
                'RangeWork',
                'CountSent'
            ]);

            $Out['Message'] = $Autoresponder->Message->ToArray();
            PrintJson::OperationSuccessful(Validator::ArrayKeyPascalCaseToSnakeCase($Out));
        }
    }


    static public function AutoresponderToArray(Autoresponder $Autoresponder) : array
    {
        $Out = $Autoresponder->ToArray([
            'AutoresponderId',
            'Name',
            'Status',
            'UserId',
            'IsEnable',
            'CountSent',
            'Event',
            'RangeWork',
            'FolderIds',
        ]);

        $Out['Message'] = $Autoresponder->Message->ToArray();
        return Validator::ArrayKeyPascalCaseToSnakeCase($Out);
    }



    static public function RunEvent(string $Event, Dialog $Dialog)
    {
        foreach(Autoresponder::FindAllActiveByUserId() as $Autoresponder)
        {
            try
            {
                self::RunAutoresponder($Autoresponder, $Event, $Dialog);
            }
            catch(Throwable $error)
            {
                Logger::Log(Log::TYPE_ERROR, 'Error RunEvent Autoresponder', (string)$error);
            }
        }
    }


    static private function RunAutoresponder(Autoresponder $Autoresponder, string $Event, Dialog $Dialog)
    {
        if(!$Autoresponder->IsEnable)
            return;

        if($Autoresponder->Event != $Event)
            return;

        if(!in_array($Dialog->GetFolderId(), $Autoresponder->FolderIds))
        {
            $Folder = Folder::FindById($Dialog->GetFolderId());
            if(empty($Folder) || empty($Folder->GetParentFolderId()))
                return;
            
            $Folder = Folder::FindById($Folder->GetParentFolderId());
            if(empty($Folder) || !in_array($Folder->GetId(), $Autoresponder->FolderIds))
                return;
        }

        if(empty($Dialog->GetId()))
            return;

        if(in_array($Dialog->GetId(), $Autoresponder->Sent))
            return;

        if(count($Autoresponder->RangeWork) > 0 && !(strtotime(date("G:i")) >= strtotime($Autoresponder->RangeWork["Start"]) && strtotime(date("G:i")) < strtotime($Autoresponder->RangeWork["Stop"])))
            return;

        switch($Event)
        {
            case Autoresponder::AUTORESPONDER_EVENT_ON_MESSAGE:
                if($Dialog->IsNew())
                    return;
                break;

            case Autoresponder::AUTORESPONDER_EVENT_ON_NEW_DIALOG:
            case Autoresponder::AUTORESPONDER_EVENT_ON_AFTER_HOURS:
                    if(!$Dialog->IsNew())
                        return;
                break;
        }

        (new MessageController)->SendMessage($Dialog, $Autoresponder->Message);
        //$Autoresponder->Sent[] = $Dialog->GetId();
        $Autoresponder->Save();
    }
}