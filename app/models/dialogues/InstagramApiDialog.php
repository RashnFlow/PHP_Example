<?php


namespace models\dialogues;


use classes\Cache;
use Exception;
use models\Authentication;
use models\Authorization;
use models\Facebook;
use models\Folder;
use models\InstagramApi;
use models\QueryCreator;


class InstagramApiDialog extends Dialog
{
    private ?int    $InstagramApiId         = null;
    private ?string $InstagramApiUserName   = null;
    private ?int    $InstagramApiUserId     = null;


    protected function OnCreate(bool $CheckAccess)
    {
        if($CheckAccess) Authorization::IsAccess(Authentication::GetAuthUser(), "GetInstagramApiDialog");

        $this->InstagramApiId       = $this->GetProperty("InstagramApiId");
        $this->InstagramApiUserName = $this->GetProperty("InstagramApiUserName");
        $this->InstagramApiUserId   = $this->GetProperty("InstagramApiUserId");

        //По истечении 23 часа поле последнего сообщения собеседника, мы отключаем диалог
        $this->DialogDisableCheck();
    }


    private function DialogDisableCheck()
    {
        if($this->GetIsActive())
        {
            $Offset = 0;
            while(true)
            {
                $Messages = $this->GetMessages($Offset, 10);
                if(empty($Messages))
                    return;

                foreach($Messages as $Message)
                {
                    if(!$Message->GetIsMe() && $Message->GetTime() < (time() - 82800))
                    {
                        $this->SetIsActive(false);
                        $this->Save();
                        return;
                    }
                }
                $Offset += 10;
            }
        }
    }


    public function Save(bool $CheckAccess = true) : int
    {
        if($CheckAccess) Authorization::IsAccess(Authentication::GetAuthUser(), "SetInstagramApiDialog");
        return $this->Id = parent::Save($CheckAccess);
    }


    public function Delete(bool $CheckAccess = true)
    {
        if($CheckAccess) Authorization::IsAccess(Authentication::GetAuthUser(), "SetInstagramApiDialog");
        parent::Delete($CheckAccess);
        $this->Id = null;
    }


    static public function ImportBase(string $FileUid, int $WhatsappId, int $FolderId = null) { throw new Exception("Method not implemented"); }


    public function GetInstagramApiId() : ?int
    {
        return $this->InstagramApiId;
    }


    public function GetInstagramApiUserId() : ?int
    {
        return $this->InstagramApiUserId;
    }


    public function GetInstagramApiUserName() : ?string
    {
        return $this->InstagramApiUserName;
    }


    public function GetFacebookDialogUid() : ?string
    {
        return $this->GetProperty("FacebookDialogUid");
    }


    public function GetMessagesIsLoaded() : ?bool
    {
        return $this->GetProperty("MessagesIsLoaded");
    }


    public function SetInstagramApiId(int $InstagramApiId)
    {
        $this->InstagramApiId = $InstagramApiId;
        $this->SetProperty("InstagramApiId", $this->InstagramApiId);

        if(empty($this->GetFolderId()))
        {
            $FolderId = Cache::Get("FolderDefaultByInstagramApiId_" . $this->InstagramApiId);
            if(empty($FolderId))
            {
                $InstagramApi = InstagramApi::FindById($this->InstagramApiId);
                if(empty($InstagramApi))
                    throw new Exception("Invalid InstagramApiId");
                
                $Folder = Folder::FindById($InstagramApi->DefaultFolder);
                if(empty($Folder))
                    throw new Exception("Invalid FolderDefault");
                
                $FolderId = $Folder->GetId();
                Cache::Set("FolderDefaultByInstagramApiId_" . $this->InstagramApiId, $FolderId);
            }

            $this->SetFolderId($FolderId);
        }
    }


    public function SetInstagramApiUserName(string $InstagramApiUserName)
    {
        $this->InstagramApiUserName = $InstagramApiUserName;
        $this->SetProperty("InstagramApiUserName", $this->InstagramApiUserName);
    }


    public function SetInstagramApiUserId(int $InstagramApiUserId)
    {
        $this->InstagramApiUserId = $InstagramApiUserId;
        $this->SetProperty("InstagramApiUserId", $this->InstagramApiUserId);
    }


    public function SetMessagesIsLoaded(bool $MessagesIsLoaded)
    {
        $this->SetProperty("MessagesIsLoaded", $MessagesIsLoaded);
    }


    public function SetFacebookDialogUid(string $FacebookDialogUid)
    {
        $this->SetProperty("FacebookDialogUid", $FacebookDialogUid);
    }




    static public function FindByInstagramApiUserIdAndInstagramApiId(int $InstagramApiUserId, int $InstagramApiId, bool $CheckAccess = true) : ?InstagramApiDialog
    {
        $Find = (self::CreateClassObjs(parent::Find(DIALOG_TABLE, "dialog_id", "(properties->>'InstagramApiUserId')::text = $1 and (properties->'InstagramApiId')::int = $2", [(string)$InstagramApiUserId, $InstagramApiId], null, 1), $CheckAccess))[0];
        return empty($Find) ? null : $Find;
    }


    static public function FindAllByUserId(int $UserId = null, int $Offset = null, int $Limit = null, bool $CheckAccess = true) : array
    {
        if(empty($UserId)) $UserId = Authentication::GetAuthUser()->GetId();
        return self::CreateClassObjs(parent::Find(DIALOG_TABLE, "dialog_id", "(properties->'InstagramApiId')::int in ($1)", [QueryCreator::Find(InstagramApi::$Table, '"' . InstagramApi::$PrimaryKey . '"', '"' . Facebook::$PrimaryKey . '" in ($1)', [QueryCreator::Find(Facebook::$Table, '"' . Facebook::$PrimaryKey . '"', '"UserId" = $1', [$UserId])])], $Offset, $Limit), $CheckAccess);
    }


    static public function FindAllByInstagramApiId(int $InstagramApiId, int $Offset = null, int $Limit = null, bool $CheckAccess = true) : array
    {
        if(empty($UserId)) $UserId = Authentication::GetAuthUser()->GetId();
        return self::CreateClassObjs(parent::Find(DIALOG_TABLE, "dialog_id", "(properties->'InstagramApiId')::int = $1", [$InstagramApiId], $Offset, $Limit), $CheckAccess);
    }


    static public function FindByDialogIdAndUserId(int $DialogId, int $UserId = null, bool $CheckAccess = true) : ?InstagramApiDialog
    {
        if(empty($UserId)) $UserId = Authentication::GetAuthUser()->GetId();
        $Find = (self::CreateClassObjs(parent::Find(DIALOG_TABLE, "dialog_id", "dialog_id = $1 and (properties->'InstagramApiId')::int in ($2)", [$DialogId, QueryCreator::Find(InstagramApi::$Table, '"' . InstagramApi::$PrimaryKey . '"', '"' . Facebook::$PrimaryKey . '" in ($1)', [QueryCreator::Find(Facebook::$Table, '"' . Facebook::$PrimaryKey . '"', '"UserId" = $1', [$UserId])])], null, 1), $CheckAccess))[0];
        return empty($Find) ? null : $Find;
    }
}