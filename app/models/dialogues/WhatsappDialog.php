<?php


namespace models\dialogues;


use classes\Cache;
use classes\Validator;
use Exception;
use models\Authentication;
use models\Authorization;
use models\Folder;
use models\ModelCollection;
use models\QueryCreator;
use models\Whatsapp;

class WhatsappDialog extends Dialog
{
    private ?int    $WhatsappId = null;
    private ?string $Phone      = null;


    protected function OnCreate(bool $CheckAccess)
    {
        if($CheckAccess) Authorization::IsAccess(Authentication::GetAuthUser(), "GetWhatsappDialog");

        $this->WhatsappId   = $this->GetProperty("WhatsappId");
        $this->Phone        = $this->GetProperty("Phone");
    }


    public function Save(bool $CheckAccess = true) : int
    {
        if($CheckAccess) Authorization::IsAccess(Authentication::GetAuthUser(), "SetWhatsappDialog");
        return $this->Id = parent::Save($CheckAccess);
    }


    public function Delete(bool $CheckAccess = true)
    {
        if($CheckAccess) Authorization::IsAccess(Authentication::GetAuthUser(), "SetWhatsappDialog");
        parent::Delete($CheckAccess);
        $this->Id = null;
    }


    static public function ImportBase(string $FileUid, int $WhatsappId, int $FolderId = null)
    {
        set_time_limit(0);
        $Base = parent::LoadImportFile($FileUid);

        $ExistingPhones = array_column(QueryCreator::Find(DIALOG_TABLE, "properties->>'Phone'", "(properties->'WhatsappId')::int = $1", [$WhatsappId])->Run(), "?column?");
        
        $ModelCollection = new ModelCollection();
        foreach($Base as $obj)
        {
            try
            {
                $Dialog = null;
                $Phone = Validator::NormalizePhone($obj["Data"]);
                if(array_search($Phone, $ExistingPhones) === false && !empty($Phone))
                {
                    $Dialog = new static;
                    $Dialog->SetPhone(Validator::NormalizePhone($obj["Data"]));
                    $Dialog->SetWhatsappId($WhatsappId);

                    if(!empty($FolderId))
                        $Dialog->SetFolderId($FolderId);

                    if(!empty($obj["Name"]))
                        $Dialog->SetName($obj["Name"]);
                    else
                        $Dialog->SetName($obj["Data"]);
                }
                else if(array_search($Phone, $ExistingPhones) !== false && !empty($FolderId))
                {
                    $Dialog = WhatsappDialog::FindByPhoneAndWhatsappId($Phone, $WhatsappId);
                    $Dialog->SetFolderId($FolderId);
                }

                if(!empty($Dialog))
                    $ModelCollection->Add($Dialog);
            }
            catch(Exception $error)
            {
                if($error->getCode() != 400)
                    throw $error;
            }
        }
        $ModelCollection->SaveModels();
    }


    public function GetWhatsappId() : ?int
    {
        return $this->WhatsappId;
    }


    public function GetPhone() : ?string
    {
        return $this->Phone;
    }


    public function SetWhatsappId(int $WhatsappId)
    {
        $this->WhatsappId = $WhatsappId;
        $this->SetProperty("WhatsappId", $this->WhatsappId);

        if(empty($this->GetFolderId()))
        {
            $FolderId = Cache::Get("FolderDefaultByWhatsappId_" . $this->WhatsappId);
            if(empty($FolderId))
            {
                $Whatsapp = Whatsapp::FindById($this->WhatsappId);
                if(empty($Whatsapp))
                    throw new Exception("Invalid WhatsappId");
                
                $Folder = Folder::FindById($Whatsapp->GetDefaultFolder());
                if(empty($Folder))
                    throw new Exception("Invalid FolderDefault");
                
                $FolderId = $Folder->GetId();
                Cache::Set("FolderDefaultByWhatsappId_" . $this->WhatsappId, $FolderId);
            }

            $this->SetFolderId($FolderId);
        }
    }


    public function SetPhone(string $Phone)
    {
        $Phone = Validator::NormalizePhone($Phone);
        $this->Phone = $Phone;
        $this->SetProperty("Phone", $this->Phone);
    }




    static public function FindByPhoneAndWhatsappId(string $Phone, int $WhatsappId, bool $CheckAccess = true) : ?WhatsappDialog
    {
        $Find = (self::CreateClassObjs(parent::Find(DIALOG_TABLE, "dialog_id", "properties->>'Phone' = $1 and (properties->'WhatsappId')::int = $2", [$Phone, $WhatsappId], null, 1), $CheckAccess))[0];
        return empty($Find) ? null : $Find;
    }


    static public function FindAllByUserId(int $UserId = null, int $Offset = null, int $Limit = null, bool $CheckAccess = true) : array
    {
        if(empty($UserId)) $UserId = Authentication::GetAuthUser()->GetId();
        return self::CreateClassObjs(parent::Find(DIALOG_TABLE, "dialog_id", "(properties->'WhatsappId')::int in ($1)", [QueryCreator::Find(WHATSAPP_TABLE, "whatsapp_id", "user_id = $1", [$UserId])], $Offset, $Limit), $CheckAccess);
    }


    static public function FindAllByWhatsappId(int $WhatsappId, int $Offset = null, int $Limit = null, bool $CheckAccess = true) : array
    {
        if(empty($UserId)) $UserId = Authentication::GetAuthUser()->GetId();
        return self::CreateClassObjs(parent::Find(DIALOG_TABLE, "dialog_id", "(properties->'WhatsappId')::int = $1", [$WhatsappId], $Offset, $Limit), $CheckAccess);
    }


    static public function FindByDialogIdAndUserId(int $DialogId, int $UserId = null, bool $CheckAccess = true) : ?WhatsappDialog
    {
        if(empty($UserId)) $UserId = Authentication::GetAuthUser()->GetId();
        $Find = (self::CreateClassObjs(parent::Find(DIALOG_TABLE, "dialog_id", "dialog_id = $1 and (properties->'WhatsappId')::int in ($2)", [$DialogId, QueryCreator::Find(WHATSAPP_TABLE, "whatsapp_id", "user_id = $1", [$UserId])], null, 1), $CheckAccess))[0];
        return empty($Find) ? null : $Find;
    }
}