<?php


namespace models;


use Exception;
use models\dialogues\Dialog;

class Folder extends CRUD
{
    private ?int     $Id                = null;
    private ?int     $UserId            = null;
    private ?int     $ParentFolderId    = null;
    private array    $Tags              = [];
    private ?string  $Name              = null;
    private bool     $IsDefault         = false;
    private bool     $EditingPossible   = true;
    private bool     $IsIsolated        = false;
    private array    $Properties        = [];


    /**
     * Do not send parameters when calling the constructor.
     * They are needed for the methods "Find". Instead, use
     * the "Set..()" methods to set values and "Get..()" to read them.
     */
    public function __construct(int $Id = null, int $UserId = null, int $ParentFolderId = null, string $Name = null, array $Tags = [], array $Properties = [], bool $IsDefault = false, bool $EditingPossible = false, bool $IsIsolated = false, bool $CheckAccess = true)
    {
        if($CheckAccess) Authorization::IsAccess(Authentication::GetAuthUser(), "GetFolder");
        if(isset($Id))
        {
            if($CheckAccess)
                Authorization::IsAccessFolder(Authentication::GetAuthUser(), $Id);

            $this->Id               = $Id;
            $this->UserId           = $UserId;
            $this->ParentFolderId   = $ParentFolderId;
            $this->Name             = $Name;
            $this->Tags             = $Tags;
            $this->IsDefault        = $IsDefault;
            $this->EditingPossible  = $EditingPossible;
            $this->IsIsolated       = $IsIsolated;
            $this->Properties       = $Properties;
        }
    }

    /**
     * Creates or updates
     */
    public function Save(bool $CheckAccess = true) : int
    {
        if($CheckAccess) Authorization::IsAccess(Authentication::GetAuthUser(), "SetFolder");
        return $this->Id =
        parent::Save(   FOLDER_TABLE,
                        "folder_id",
                        $this->Id,
                        [
                            'name',
                            'user_id',
                            'tags',
                            'is_default',
                            'parent_folder_id',
                            'editing_possible',
                            'is_isolated',
                            'properties'
                        ],
                        "$1,$2,$3,$4,$5,$6,$7,$8",
                        [
                            $this->Name,
                            $this->UserId,
                            $this->Tags,
                            $this->IsDefault,
                            $this->ParentFolderId,
                            $this->EditingPossible,
                            $this->IsIsolated,
                            $this->Properties,
                        ]
                    );
    }


    /**
     * Removes
     */
    public function Delete(bool $CheckAccess = true)
    {
        if($CheckAccess) Authorization::IsAccess(Authentication::GetAuthUser(), "SetFolder");
        // $FolderDefault = Folder::FindDefault(Authentication::GetAuthUser()->GetId());

        // foreach($this->GetDialogues() as $Dialog)
        // {
        //     $Dialog->SetFolderId($FolderDefault->GetId());
        //     $Dialog->Save();
        // }

        foreach($this->FindAllByParentFolderIdAndUserId($this->GetId()) as $Folder)
            $Folder->Delete();
        
        parent::Delete(FOLDER_TABLE, "folder_id", $this->Id);
        $this->Id = null;
    }


    public function IsIsolatedRecursively() : bool
    {
        if($this->IsIsolated)
            return true;
        
        if($this->ParentFolderId > 0)
            return Folder::FindById($this->ParentFolderId)->IsIsolatedRecursively();

        return false;
    }


    public function IsExistsInFolderRecursively(Folder $Folder) : bool
    {
        if($this->Id == $Folder->GetParentFolderId())
            return true;

        if($Folder->GetParentFolderId() > 0)
            return $this->IsExistsInFolderRecursively(Folder::FindById($Folder->GetParentFolderId()));

        return false;
    }


    public function GetDialogues(int $Offset = 0, int $Limit = null) : array
    {
        return Dialog::CreateClassObjs((new Query('SELECT "' . DIALOG_TABLE . '".* FROM "' . DIALOG_TABLE . '" LEFT JOIN (select DISTINCT ON ("dialog_id") * from "' . MESSAGE_TABLE . '" ORDER BY "dialog_id", "message_id" DESC) "' . MESSAGE_TABLE . '" on "' . DIALOG_TABLE . '"."dialog_id" = "' . MESSAGE_TABLE . '"."dialog_id" WHERE "' . DIALOG_TABLE . '"."folder_id" = $1 ORDER BY messages is null, case when "' . MESSAGE_TABLE . '"."status_id" >= 3 or "' . MESSAGE_TABLE . '"."is_me" = true then 1 else 0 end ASC, "' . MESSAGE_TABLE . '"."time" DESC, "' . DIALOG_TABLE . '"."dialog_id" OFFSET $2' . (!empty($Limit) ? ' LIMIT $3' : ''), 'Find', [$this->GetId(), $Offset, $Limit]))->Run());
    }


    public function CountDialogues() : int
    {
        return (int)QueryCreator::Count(DIALOG_TABLE, "folder_id = $1", [$this->GetId()])->Run()[0]->count;
    }


    public function GetTags() : array
    {
        return $this->Tags;
    }


    public function GetIsDefault() : bool
    {
        return $this->IsDefault;
    }


    public function GetIsIsolated() : bool
    {
        return $this->IsIsolated;
    }

    public function GetProperty(string $key)
    {
        return $this->Properties[$key];
    }


    public function GetAllProperties() : array
    {
        return $this->Properties;
    }


    public function GetEditingPossible() : bool
    {
        return $this->EditingPossible;
    }


    public function CheckTag(string $Tag) : bool
    {
        return array_search($Tag, $this->Tags) !== false;
    }


    public function GetName() : ?string
    {
        return $this->Name;
    }


    public function GetUserId() : ?string
    {
        return $this->UserId;
    }


    public function GetId() : ?int
    {
        return $this->Id;
    }


    public function GetParentFolderId() : ?int
    {
        return $this->ParentFolderId;
    }


    public function GetCountUnreadDialogues() : int
    {
        return count((new Query('SELECT DISTINCT ON ("' . DIALOG_TABLE . '"."dialog_id") "' . DIALOG_TABLE . '"."dialog_id" from "' . DIALOG_TABLE . '" INNER JOIN "' . MESSAGE_TABLE . '" ON "' . MESSAGE_TABLE . '"."dialog_id" = "' . DIALOG_TABLE . '"."dialog_id" AND "' . MESSAGE_TABLE . '"."status_id" < 3 AND "' . MESSAGE_TABLE . '"."is_me" = false WHERE "' . DIALOG_TABLE . '"."folder_id" = $1', 'Find', [$this->GetId()]))->Run());
    }


    public function SetName(string $Name)
    {
        $this->Name = $Name;
    }


    public function SetUserId(int $UserId)
    {
        $this->UserId = $UserId;
    }


    public function SetProperty(string $key, $val)
    {
        $this->Properties[$key] = $val;
    }


    public function SetParentFolderId(int $ParentFolderId)
    {
        $this->ParentFolderId = $ParentFolderId;
    }


    public function SetIsDefault(bool $IsDefault)
    {
        $this->IsDefault = $IsDefault;
    }


    public function SetIsIsolated(bool $IsIsolated)
    {
        $this->IsIsolated = $IsIsolated;
    }


    public function SetEditingPossible(bool $EditingPossible)
    {
        $this->EditingPossible = $EditingPossible;
    }


    public function SetTags(array $Tags)
    {
        foreach($Tags as $Tag)
            $this->AddTag($Tag);
    }


    public function AddTag(string $Tag)
    {
        if(array_search($Tag, $this->Tags) === false)
            $this->Tags[] = $Tag;
        else
            throw new Exception("Tag exists");
    }


    public function RemoveTag(string $Tag)
    {
        unset($this->Tags[array_search($Tag, $this->Tags)]);
    }


    static public function FindByNameAndUserId(string $Name, int $UserId = null, int $ParentFolderId = null, bool $CheckAccess = true) : ?Folder
    {
        if(empty($UserId)) $UserId = Authentication::GetAuthUser()->GetId();
        $Find = (self::CreateClassObjs(parent::Find(FOLDER_TABLE, "folder_id", "name = $1 and user_id = $2 " . ($ParentFolderId == null ? "" : "and parent_folder_id = $3"), [$Name, $UserId, $ParentFolderId], null, 1), $CheckAccess))[0];
        return empty($Find) ? null : $Find;
    }


    static public function FindById(int $FolderId, bool $CheckAccess = true) : ?Folder
    {
        $Find = (self::CreateClassObjs(parent::Find(FOLDER_TABLE, "folder_id", "folder_id = $1", [$FolderId], null, 1), $CheckAccess))[0];
        return empty($Find) ? null : $Find;
    }


    static public function FindByFolderIdAndUserId(int $FolderId, int $UserId = null, bool $CheckAccess = true) : ?Folder
    {
        if(empty($UserId)) $UserId = Authentication::GetAuthUser()->GetId();
        $Find = (self::CreateClassObjs(parent::Find(FOLDER_TABLE, "folder_id", "folder_id = $1 and user_id = $2", [$FolderId, $UserId], null, 1), $CheckAccess))[0];
        return empty($Find) ? null : $Find;
    }


    static public function FindDefault(int $UserId = null, bool $CheckAccess = true) : ?Folder
    {
        if(empty($UserId)) $UserId = Authentication::GetAuthUser()->GetId();
        $Find = (self::CreateClassObjs(parent::Find(FOLDER_TABLE, "folder_id", "user_id = $1 and is_default = true", [$UserId], null, 1), $CheckAccess))[0];
        return empty($Find) ? null : $Find;
    }


    static public function FindAll(string $Where = null, array $Parameters = [], int $Offset = null, int $Limit = null, bool $CheckAccess = true) : array
    {
        return self::CreateClassObjs(parent::Find(FOLDER_TABLE, "folder_id", $Where, $Parameters, $Offset, $Limit), $CheckAccess);
    }


    static public function FindAllByUserId(int $UserId = null, bool $CheckAccess = true) : array
    {
        if(empty($UserId)) $UserId = Authentication::GetAuthUser()->GetId();
        return self::CreateClassObjs(parent::Find(FOLDER_TABLE, "folder_id", "user_id = $1", [$UserId]), $CheckAccess);
    }


    static public function FindAllByParentFolderIdAndUserId(int $ParentFolderId, int $UserId = null, bool $CheckAccess = true) : array
    {
        if(empty($UserId)) $UserId = Authentication::GetAuthUser()->GetId();
        return self::CreateClassObjs(parent::Find(FOLDER_TABLE, "folder_id", "user_id = $1 and parent_folder_id = $2", [$UserId, $ParentFolderId]), $CheckAccess);
    }


    /**
     * @return array Folder Classes
     */
    static public function CreateClassObjs(array $Obj, $CheckAccess = true) : array
    {
        $Out = [];
        foreach($Obj as $TempObj)
        {
            $Out[] =    new Folder(
                $TempObj->folder_id,
                $TempObj->user_id,
                $TempObj->parent_folder_id,
                $TempObj->name,
                json_decode($TempObj->tags, true),
                json_decode($TempObj->properties, true),
                $TempObj->is_default,
                $TempObj->editing_possible,
                $TempObj->is_isolated,
                $CheckAccess
            );
        }
        return $Out;
    }
}