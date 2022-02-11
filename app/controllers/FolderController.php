<?php


namespace controllers;


use classes\Validator;
use models\Authentication;
use models\dialogues\Dialog;
use models\Folder;
use models\ModelCollection;
use views\PrintJson;


class FolderController
{
    public function ActionGetAllFolders(array $Parameters)
    {
        //Обратная совместимость. Удалить как будет залит редизайн нового интерфейса
        if(!empty($Parameters['Get']['parent_folder_id']))
        {
            PrintJson::OperationSuccessful(["folders" => []]);
            return;
        }
        
        $Out = [];

        foreach(Folder::FindAllByUserId() as $Folder)
            if(empty($Folder->GetParentFolderId()))
                $Out[] = self::ToArray($Folder);

        PrintJson::OperationSuccessful(["folders" => $Out]);
    }
    

    public function ActionGetDialoguesInFolder(array $Parameters)
    {
        if(Validator::IsValid($Parameters["Get"], [
            ["Key" => "folder_id",  "Type" => "int"],
            ["Key" => "offset",     "Type" => "int", "IntMin" => 0],
            ["Key" => "limit",      "Type" => "int", "IntMin" => 1, "IntMax" => 30]
        ]))
        {
            $Folder = Folder::FindById($Parameters["Get"]["folder_id"]);
            if(!empty($Folder))
            {
                $Out = [];
                foreach($Folder->GetDialogues($Parameters["Get"]["offset"], $Parameters["Get"]["limit"]) as $Dialog)
                    $Out[] = DialogController::DialogToArray($Dialog);

                PrintJson::OperationSuccessful(["dialogues" => $Out]);
            }
            else 
                PrintJson::OperationError(FolderNotFound, NOT_FOUND);
        }
    }


    public function ActionRenameFolder(array $Parameters)
    {
        if(Validator::IsValid($Parameters["Post"], [
            ["Key" => "folder_id",  "Type" => "int"],
            ["Key" => "name"]
        ]))
        {
            $Folder = Folder::FindById($Parameters["Post"]["folder_id"]);
            if(!empty($Folder))
            {
                if(!$Folder->GetIsDefault() && $Folder->GetEditingPossible())
                {
                    $Folder->SetName($Parameters["Post"]["name"]);
                    $Folder->Save();
                    PrintJson::OperationSuccessful();
                }
                else
                    PrintJson::OperationError(FolderEditingProhibited, ACCESS_DENIED);
            }
            else 
                PrintJson::OperationError(FolderNotFound, NOT_FOUND);
        }
    }


    public function ActionCreateFolder(array $Parameters)
    {
        if(Validator::IsValid($Parameters["Post"], [
            ["Key" => "name"],
            ["Key" => "dialog_ids", "Type" => "array", "IsNull" => true],
            ["Key" => "tags", "Type" => "array", "IsNull" => true],
            ["Key" => "parent_folder_id", "Type" => "int", "IsNull" => true]
        ]))
        {
            // if(Folder::FindByNameAndUserId($Parameters["Post"]["name"]) == null)
            // {
                $Folder = new Folder();
                $Folder->SetName($Parameters["Post"]["name"]);
                $Folder->SetUserId((Authentication::GetAuthUser())->GetId());

                $ParentFolder = null;
                if(!empty($Parameters["Post"]["parent_folder_id"]))
                {
                    $ParentFolder = Folder::FindById((int)$Parameters["Post"]["parent_folder_id"]);
                    if(empty($ParentFolder) || $ParentFolder->GetIsDefault())
                    {
                        PrintJson::OperationError(FolderNotFound, NOT_FOUND);
                        return;
                    }
                    $Folder->SetParentFolderId((int)$Parameters["Post"]["parent_folder_id"]);
                }

                if(is_array($Parameters["Post"]["tags"]))
                    $Folder->SetTags($Parameters["Post"]["tags"]);

                $Folder->Save();

                if(is_array($Parameters["Post"]["dialog_ids"]))
                {
                    $Folder = Folder::FindByNameAndUserId($Parameters["Post"]["name"]);
                    foreach ($Parameters["Post"]["dialog_ids"] as $DialogId)
                    {
                        $Dialog = Dialog::FindById($DialogId);
                        if(!empty($Dialog))
                        {
                            $Dialog->SetFolderId($Folder->GetId());
                            $Dialog->Save();
                        }
                    }
                }

                //Перемещаем все диалоги в из основной папки, в дочернюю
                if(!empty($ParentFolder) && count(Folder::FindAllByParentFolderIdAndUserId($ParentFolder->GetId())) == 1)
                {
                    $ModelCollection = new ModelCollection();
                    foreach($ParentFolder->GetDialogues() as $Dialog)
                    {
                        $Dialog->SetFolderId($Folder->GetId());
                        $ModelCollection->Add($Dialog);
                    }
                    $ModelCollection->SaveModels();
                }

                PrintJson::OperationSuccessful();
            // }
            // else
            //     PrintJson::OperationError(FolderIsExist, IS_EXISTS);
        }
    }


    public function ActionDeleteFolder(array $Parameters)
    {
        if(Validator::IsValid($Parameters["Post"], [
            ["Key" => "folder_ids", "Type" => "array"]
        ]))
        {
            foreach($Parameters["Post"]["folder_ids"] as $FolderId)
            {
                $Folder = Folder::FindById($FolderId);
                if(!empty($Folder))
                {
                    if(!$Folder->GetIsDefault() && $Folder->GetEditingPossible())
                        $Folder->Delete();
                    else
                    {
                        PrintJson::OperationError(FolderEditingProhibited, ACCESS_DENIED);
                        return;
                    }
                }
            }

            PrintJson::OperationSuccessful();
        }
    }

    
    static public function ToArray(Folder $Folder) : array
    {
        $Out = [];

        $Unread = $Folder->GetCountUnreadDialogues();
        $Out = [
            "name"              => $Folder->GetName(),
            "unread"            => $Unread,
            "is_read"           => $Unread <= 0,
            "is_default"        => $Folder->GetIsDefault(),
            "folder_id"         => $Folder->GetId(),
            "parent_folder_id"  => $Folder->GetParentFolderId(),
            "editing_possible"  => $Folder->GetEditingPossible(),
            "dialogues_count"   => $Folder->CountDialogues(),
            "is_isolated"       => $Folder->GetIsIsolated(),
            "properties"        => $Folder->GetAllProperties(),
            "folders"           => null
        ];

        $Find = Folder::FindAllByParentFolderIdAndUserId($Folder->GetId());
        if(!empty($Find))
        {
            $Out['folders'] = [];
            foreach($Find as $obj)
                $Out['folders'][] = self::ToArray($obj);
        }

        return $Out;
    }
}