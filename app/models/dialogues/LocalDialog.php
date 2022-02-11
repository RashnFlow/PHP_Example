<?php


namespace models\dialogues;


use classes\Cache;
use Exception;
use models\Authentication;
use models\Authorization;
use models\Folder;


class LocalDialog extends Dialog
{
    protected function OnCreate(bool $CheckAccess)
    {
        if($CheckAccess) Authorization::IsAccess(Authentication::GetAuthUser(), "GetLocalDialog");
    }


    public function Save(bool $CheckAccess = true) : int
    {
        if($CheckAccess) Authorization::IsAccess(Authentication::GetAuthUser(), "SetLocalDialog");
        return $this->Id = parent::Save($CheckAccess);
    }


    public function Delete(bool $CheckAccess = true)
    {
        if($CheckAccess) Authorization::IsAccess(Authentication::GetAuthUser(), "SetLocalDialog");
        parent::Delete($CheckAccess);
        $this->Id = null;
    }


    static public function ImportBase(string $FileUid, int $WhatsappId, int $FolderId = null) { throw new Exception("Method not implemented"); }


    public function GetUserId() : ?int
    {
        return $this->GetProperty("UserId");
    }


    public function GetToUserId() : ?int
    {
        return $this->GetProperty("ToUserId");
    }


    public function SetUserId(int $UserId)
    {
        $this->SetProperty("UserId", $UserId);

        if(empty($this->GetFolderId()))
        {
            $FolderId = Cache::Get("FolderDefaultByUserId_" . $UserId);
            if(empty($FolderId))
            {
                $Folder = Folder::FindDefault($UserId);
                if(empty($Folder))
                    throw new Exception("Invalid FolderDefault");
                
                $FolderId = $Folder->GetId();
                Cache::Set("FolderDefaultByUserId_" . $UserId, $FolderId);
            }

            $this->SetFolderId($FolderId);
        }
    }


    public function SetToUserId(int $ToUserId)
    {
        $this->SetProperty("ToUserId", $ToUserId);
    }




    static public function FindByUserIdAndToUserId(int $UserId, int $ToUserId, bool $CheckAccess = true) : ?LocalDialog
    {
        $Find = (self::CreateClassObjs(parent::Find(DIALOG_TABLE, "dialog_id", "(properties->>'UserId')::int = $1 and (properties->'ToUserId')::int = $2", [$UserId, $ToUserId], null, 1), $CheckAccess))[0];
        return empty($Find) ? null : $Find;
    }


    static public function FindAllByUserId(int $UserId = null, int $Offset = null, int $Limit = null, bool $CheckAccess = true) : array
    {
        if(empty($UserId)) $UserId = Authentication::GetAuthUser()->GetId();
        return self::CreateClassObjs(parent::Find(DIALOG_TABLE, "dialog_id", "(properties->'UserId')::int in ($1)", [$UserId], $Offset, $Limit), $CheckAccess);
    }


    static public function FindByDialogIdAndUserId(int $DialogId, int $UserId = null, bool $CheckAccess = true) : ?LocalDialog
    {
        if(empty($UserId)) $UserId = Authentication::GetAuthUser()->GetId();
        $Find = (self::CreateClassObjs(parent::Find(DIALOG_TABLE, "dialog_id", "dialog_id = $1 and (properties->'UserId')::int in ($2)", [$DialogId, $UserId], null, 1), $CheckAccess))[0];
        return empty($Find) ? null : $Find;
    }
}