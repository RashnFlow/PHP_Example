<?php


namespace models;


use classes\File;
use classes\Directory;
use classes\Tools;
use Exception;
use interfaces\IResource;


class DynamicResource extends CRUD implements IResource
{
    private const ROOT_DYNAMIC = ROOT . "/resources/dynamic";


    private ?int     $Id            = null;
    private ?string  $Name          = null;
    private ?string  $Uid           = null;
    private ?string  $Extension     = null;
    private ?string  $Type          = null;
    private ?int     $UserId        = null;



    /**
     * Do not send parameters when calling the constructor.
     * They are needed for the methods "Find". Instead, use
     * the "Set..()" methods to set values and "Get..()" to read them.
     */
    public function __construct(
        int     $Id          = null,
        string  $Uid         = null,
        string  $Name        = null,
        string  $Extension   = null,
        string  $Type        = null,
        int     $UserId      = null,
        bool    $CheckAccess = true)
    {
        if($CheckAccess) Authorization::IsAccess(Authentication::GetAuthUser(), "GetDynamicResources");
        if(isset($Id))
        {
            if($CheckAccess)
                Authorization::IsAccessDynamicResource(Authentication::GetAuthUser(), $Id);

            $this->Id           = $Id;
            $this->UserId       = $UserId;
            $this->Name         = $Name;
            $this->Extension    = $Extension;
            $this->Type         = $Type;
            $this->Uid          = $Uid;
        }
    }

    /**
     * Creates or updates
     */
    public function Save(bool $CheckAccess = true) : int
    {
        if($CheckAccess) Authorization::IsAccess(Authentication::GetAuthUser(), "SetDynamicResources");
        return $this->Id =
        parent::Save(   DYNAMIC_RESOURCE_TABLE,
                        "dynamic_resource_id",
                        $this->Id,
                        [
                            'dynamic_resource_uid',
                            'user_id',
                            'type',
                            'extension',
                            'name'
                        ],
                        "$1,$2,$3,$4,$5",
                        [
                            $this->Uid,
                            $this->UserId,
                            $this->Type,
                            $this->Extension,
                            $this->Name
                        ]
                    );
    }


    /**
     * Removes
     */
    public function Delete(bool $CheckAccess = true)
    {
        if($CheckAccess) Authorization::IsAccess(Authentication::GetAuthUser(), "SetDynamicResources");
        if(!empty($this->Uid))
            File::Delete(self::ROOT_DYNAMIC . $this->Uid);
        parent::Delete(DYNAMIC_RESOURCE_TABLE, "dynamic_resource_id", $this->Id);
        $this->Id = null;
    }


    public function GetId() : ?int
    {
        return $this->Id;
    }


    public function GetUid() : ?string
    {
        return $this->Uid;
    }


    public function GetName() : ?string
    {
        return $this->Name;
    }


    public function GetSize() : ?int
    {
        return filesize($this->GetPath());
    }


    public function GetPath() : string
    {
        if(empty($this->Uid))
            throw new Exception("File is empty");

        return self::ROOT_DYNAMIC . "/" . $this->Uid;
    }


    public function GetType() : ?string
    {
        return $this->Type;
    }


    public function GetExtension() : ?string
    {
        return $this->Extension;
    }


    public function GetUserId() : ?int
    {
        return $this->UserId;
    }


    public function GetResource() : string
    {
        if(empty($this->Uid)) throw new Exception("Resource is empty");
        return File::ReadAllText($this->GetPath());
    }


    public function GetAllLinesResource() : array
    {
        if(empty($this->Uid)) throw new Exception("Resource is empty");
        return File::ReadAllLines($this->GetPath());
    }


    public function SetUserId(int $UserId)
    {
        $this->UserId = $UserId;
    }


    public function SetUid(string $Uid)
    {
        $this->Uid = $Uid;
    }


    public function SetName(string $Name)
    {
        $this->Name = $Name;
    }


    public function SetType(string $Type)
    {
        $this->Type = $Type;
    }


    public function SetExtension(string $Extension)
    {
        $this->Extension = $Extension;
    }


    public function SetResource($Data)
    {
        if(!Directory::Exists(self::ROOT_DYNAMIC))
            Directory::Create(self::ROOT_DYNAMIC);

        $this->Uid = $this->GenerateUid();
        File::WriteAllText($this->GetPath(), $Data);
    }


    public function SetResourceByFile($FileName)
    {
        $this->SetResource(File::ReadAllText($FileName));
    }


    private function GenerateUid(int $Length = 20) : string
    {
        while(true)
        {
            $Uid = Tools::GenerateString($Length);

            if(!self::FindByUidAndUserId($Uid))
                return $Uid;
        }
    }



    static public function CheckByUid(string $DynamicResourceUid) : bool
    {
        return QueryCreator::Count(DYNAMIC_RESOURCE_TABLE, "dynamic_resource_uid = $1", [$DynamicResourceUid])->Run()[0]->count > 0;
    }


    static public function FindById(int $DynamicResourceId, bool $CheckAccess = true) : ?DynamicResource
    {
        $Find = (self::CreateClassObjs(parent::Find(DYNAMIC_RESOURCE_TABLE, "dynamic_resource_id", "dynamic_resource_id = $1", [$DynamicResourceId], null, 1), $CheckAccess))[0];
        return empty($Find) ? null : $Find;
    }


    static public function FindByUidAndUserId(string $DynamicResourceUid, int $UserId = null, $CheckAccess = true) : ?DynamicResource
    {
        if(empty($UserId)) $UserId = Authentication::GetAuthUser()->GetId();
        $Find = (self::CreateClassObjs(parent::Find(DYNAMIC_RESOURCE_TABLE, "dynamic_resource_id", "dynamic_resource_uid = $1 and user_id = $2", [$DynamicResourceUid, $UserId], null, 1), $CheckAccess))[0];
        return empty($Find) ? null : $Find;
    }


    static public function FindByUid(string $DynamicResourceUid, $CheckAccess = true) : ?DynamicResource
    {
        $Find = (self::CreateClassObjs(parent::Find(DYNAMIC_RESOURCE_TABLE, "dynamic_resource_id", "dynamic_resource_uid = $1", [$DynamicResourceUid], null, 1), $CheckAccess))[0];
        return empty($Find) ? null : $Find;
    }


    static public function FindAll(string $Where = null, array $Parameters = [], int $Offset = null, int $Limit = null, bool $CheckAccess = true) : array
    {
        return self::CreateClassObjs(parent::Find(DYNAMIC_RESOURCE_TABLE, "dynamic_resource_id", $Where, $Parameters, $Offset, $Limit), $CheckAccess);
    }


    static public function FindAllByUserId(int $UserId = null, bool $CheckAccess = true) : array
    {
        if(empty($UserId)) $UserId = Authentication::GetAuthUser()->GetId();
        return self::CreateClassObjs(parent::Find(DYNAMIC_RESOURCE_TABLE, "dynamic_resource_id", "user_id = $1", [$UserId]), $CheckAccess);
    }


    /**
     * @return array DynamicResource Classes
     */
    static public function CreateClassObjs(array $Obj, $CheckAccess = true) : array
    {
        $Out = [];
        foreach($Obj as $TempObj)
        {
            $Out[] =    new DynamicResource(
                $TempObj->dynamic_resource_id,
                $TempObj->dynamic_resource_uid,
                $TempObj->name,
                $TempObj->extension,
                $TempObj->type,
                $TempObj->user_id,
                $CheckAccess
            );
        }
        return $Out;
    }
}