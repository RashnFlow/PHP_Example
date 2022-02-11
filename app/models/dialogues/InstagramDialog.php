<?php


namespace models\dialogues;


use classes\Cache;
use Exception;
use models\Authentication;
use models\Authorization;
use models\Folder;
use models\Instagram;
use models\ModelCollection;
use models\QueryCreator;

class InstagramDialog extends Dialog
{
    private ?int    $InstagramId    = null;
    private ?string $Login          = null;


    protected function OnCreate(bool $CheckAccess)
    {
        if($CheckAccess) Authorization::IsAccess(Authentication::GetAuthUser(), "GetInstagramDialog");

        $this->InstagramId   = $this->GetProperty("InstagramId");
        $this->Login         = $this->GetProperty("Login");
    }


    public function Save(bool $CheckAccess = true) : int
    {
        if($CheckAccess) Authorization::IsAccess(Authentication::GetAuthUser(), "SetInstagramDialog");
        return $this->Id = parent::Save($CheckAccess);
    }


    public function Delete(bool $CheckAccess = true)
    {
        if($CheckAccess) Authorization::IsAccess(Authentication::GetAuthUser(), "SetInstagramDialog");
        parent::Delete($CheckAccess);
        $this->Id = null;
    }


    static public function ImportBase(string $FileUid, int $InstagramId, int $FolderId = null)
    {
        set_time_limit(0);
        $Base = parent::LoadImportFile($FileUid);

        $ExistingLogins = array_column(QueryCreator::Find(DIALOG_TABLE, "properties->>'Login'", "(properties->'InstagramId')::int = $1", [$InstagramId])->Run(), "?column?");
        
        $ModelCollection = new ModelCollection();
        foreach($Base as $obj)
        {
            $Dialog = null;
            if(array_search($obj["Data"], $ExistingLogins) === false)
            {
                $Dialog = new static;
                $Dialog->SetLogin($obj["Data"]);
                $Dialog->SetInstagramId($InstagramId);

                if(!empty($FolderId))
                    $Dialog->SetFolderId($FolderId);

                if(!empty($obj["Name"]))
                    $Dialog->SetName($obj["Name"]);
                else
                    $Dialog->SetName($obj["Data"]);

                $ModelCollection->Add($Dialog);
            }
            else if(!empty($FolderId))
            {
                $Dialog = InstagramDialog::FindByLoginAndInstagramId($obj["Data"], $InstagramId);
                $Dialog->SetFolderId($FolderId);
            }

            if(!empty($Dialog))
                $ModelCollection->Add($Dialog);
        }
        $ModelCollection->SaveModels();
    }


    public function GetInstagramId() : ?int
    {
        return $this->InstagramId;
    }


    public function GetLogin() : ?string
    {
        return $this->Login;
    }


    public function SetInstagramId(int $InstagramId)
    {
        $this->InstagramId = $InstagramId;
        $this->SetProperty("InstagramId", $this->InstagramId);

        if(empty($this->GetFolderId()))
        {
            $FolderId = Cache::Get("FolderDefaultByInstagramId_" . $this->InstagramId);
            if(empty($FolderId))
            {
                $Instagram = Instagram::FindById($this->InstagramId);
                if(empty($Instagram))
                    throw new Exception("Invalid InstagramId");
                
                $Folder = Folder::FindById($Instagram->GetDefaultFolder());
                if(empty($Folder))
                    throw new Exception("Invalid FolderDefault");
                
                $FolderId = $Folder->GetId();
                Cache::Set("FolderDefaultByInstagramId_" . $this->InstagramId, $FolderId);
            }

            $this->SetFolderId($FolderId);
        }
    }


    public function SetLogin(string $Login)
    {
        $this->Login = $Login;
        $this->SetProperty("Login", $this->Login);
    }




    static public function FindByLoginAndInstagramId(string $Login, int $InstagramId, bool $CheckAccess = true) : ?InstagramDialog
    {
        $Find = (self::CreateClassObjs(parent::Find(DIALOG_TABLE, "dialog_id", "properties->>'Login' = $1 and (properties->'InstagramId')::int = $2", [$Login, $InstagramId], null, 1), $CheckAccess))[0];
        return empty($Find) ? null : $Find;
    }


    static public function FindAllByUserId(int $UserId = null, int $Offset = null, int $Limit = null, bool $CheckAccess = true) : array
    {
        if(empty($UserId)) $UserId = Authentication::GetAuthUser()->GetId();
        return self::CreateClassObjs(parent::Find(DIALOG_TABLE, "dialog_id", "(properties->'InstagramId')::int in ($1)", [QueryCreator::Find(INSTAGRAM_TABLE, "instagram_id", "user_id = $1", [$UserId])], $Offset, $Limit), $CheckAccess);
    }


    static public function FindAllByInstagramId(int $InstagramId, int $Offset = null, int $Limit = null, bool $CheckAccess = true) : array
    {
        if(empty($UserId)) $UserId = Authentication::GetAuthUser()->GetId();
        return self::CreateClassObjs(parent::Find(DIALOG_TABLE, "dialog_id", "(properties->'InstagramId')::int = $1", [$InstagramId], $Offset, $Limit), $CheckAccess);
    }


    static public function FindByDialogIdAndUserId(int $DialogId, int $UserId = null, bool $CheckAccess = true) : ?InstagramDialog
    {
        if(empty($UserId)) $UserId = Authentication::GetAuthUser()->GetId();
        $Find = (self::CreateClassObjs(parent::Find(DIALOG_TABLE, "dialog_id", "dialog_id = $1 and (properties->'InstagramId')::int in ($2)", [$DialogId, QueryCreator::Find(INSTAGRAM_TABLE, "instagram_id", "user_id = $1", [$UserId])], null, 1), $CheckAccess))[0];
        return empty($Find) ? null : $Find;
    }
}