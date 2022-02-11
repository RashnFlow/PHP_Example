<?php


namespace models;


class Device extends CRUD
{
    private ?int     $Id             = null;
    private ?string  $Status         = null;
    private bool     $IsActive       = false;
    private ?string  $Uid            = null;


    /**
     * Do not send parameters when calling the constructor.
     * They are needed for the methods "Find". Instead, use
     * the "Set..()" methods to set values and "Get..()" to read them.
     */
    public function __construct(
        int     $Id             = null,
        string  $Status         = null,
        bool    $IsActive       = false,
        string  $Uid            = null,
        bool    $CheckAccess    = true)
    {
        if($CheckAccess) Authorization::IsAccess(Authentication::GetAuthUser(), "GetDevice");
        if(isset($Id))
        {
            $this->Id                 = $Id;
            $this->Status             = $Status;
            $this->IsActive           = $IsActive;
            $this->Uid                = $Uid;
        }
    }


    public function Save(bool $CheckAccess = true) : int
    {
        if($CheckAccess) Authorization::IsAccess(Authentication::GetAuthUser(), "SetDevice");
        return $this->Id =
        parent::Save(   DEVICE_TABLE,
                        "device_id",
                        $this->Id,
                        [
                            'status',
                            'is_active',
                            'device_uid',
                        ],
                        "$1,$2,$3",
                        [
                            $this->Status,
                            $this->IsActive,
                            $this->Uid
                        ]
                    );
    }


    public function Delete(bool $CheckAccess = true)
    {
        if($CheckAccess) Authorization::IsAccess(Authentication::GetAuthUser(), "SetDevice");
        parent::Delete(DEVICE_TABLE, "device_id", $this->Id);
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


    public function GetIsActive() : bool
    {
        return $this->IsActive;
    }


    public function GetStatus() : ?string
    {
        return $this->Status;
    }


    public function SetStatus(string $Status)
    {
        $this->Status = $Status;
    }


    public function SetUid(string $Uid)
    {
        $this->Uid = $Uid;
    }


    public function SetIsActive(bool $IsActive)
    {
        $this->IsActive = $IsActive;
    }





    static public function FindById(int $DeviceId, bool $CheckAccess = true) : ?Device
    {
        $Find = (self::CreateClassObjs(parent::Find(DEVICE_TABLE, "device_id", "device_id = $1", [$DeviceId], null, 1), $CheckAccess))[0];
        return empty($Find) ? null : $Find;
    }


    static public function FindByUid(string $DeviceUid, bool $CheckAccess = true) : ?Device
    {
        $Find = (self::CreateClassObjs(parent::Find(DEVICE_TABLE, "device_uid", "device_uid = $1", [$DeviceUid], null, 1), $CheckAccess))[0];
        return empty($Find) ? null : $Find;
    }


    static public function FindAll(string $Where = null, array $Parameters = [], int $Offset = null, int $Limit = null, bool $CheckAccess = true) : array
    {
        return self::CreateClassObjs(parent::Find(DEVICE_TABLE, "device_id", $Where, $Parameters, $Offset, $Limit), $CheckAccess);
    }


    /**
     * @return array Device Classes
     */
    static public function CreateClassObjs(array $Obj, bool $CheckAccess = true) : array
    {
        $Out = [];
        foreach($Obj as $TempObj)
        {
            $Out[] = new Device(
                $TempObj->device_id,
                $TempObj->status,
                $TempObj->is_active,
                $TempObj->device_uid,
                $CheckAccess
            );
        }
        return $Out;
    }
}