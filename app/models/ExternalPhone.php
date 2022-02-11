<?php


namespace models;


use Exception;


class ExternalPhone extends CRUD
{
    private ?int    $Id     = null;
    private ?string $Phone  = null;
    private array   $IdsUse = [];



    /**
     * Do not send parameters when calling the constructor.
     * They are needed for the methods "Find". Instead, use
     * the "Set..()" methods to set values and "Get..()" to read them.
     */
    public function __construct(
        int     $Id     = null,
        string  $Phone  = null,
        array   $IdsUse = [],
        bool    $CheckAccess = true
    )
    {
        if($CheckAccess) Authorization::IsAccess(Authentication::GetAuthUser(), "GetExternalPhone");
        if(isset($Id))
        {
            $this->Id       = $Id;
            $this->Phone    = $Phone;
            $this->IdsUse   = $IdsUse;
        }
    }

    /**
     * Creates or updates
     */
    public function Save(bool $CheckAccess = true) : int
    {
        if($CheckAccess) Authorization::IsAccess(Authentication::GetAuthUser(), "SetExternalPhone");
        return $this->Id =
        parent::Save(   EXTERNAL_PHONE,
                        "external_phone_id",
                        $this->Id,
                        [
                            'phone',
                            'ids_use'
                        ],
                        "$1,$2",
                        [
                            $this->Phone,
                            $this->IdsUse
                        ]
                    );
    }


    /**
     * Removes
     */
    public function Delete(bool $CheckAccess = true)
    {
        if($CheckAccess) Authorization::IsAccess(Authentication::GetAuthUser(), "SetExternalPhone");
        parent::Delete(EXTERNAL_PHONE, "external_phone_id", $this->Id);
        $this->Id = null;
    }


    public function GetPhone() : ?string
    {
        return $this->Phone;
    }


    public function GetId() : ?int
    {
        return $this->Id;
    }


    public function GetIdsUse() : array
    {
        return $this->IdsUse;
    }


    public function SetPhone(string $Phone)
    {
        $this->Phone = $Phone;
    }


    public function SetIdsUse(array $IdsUse)
    {
        $this->IdsUse = $IdsUse;
    }


    public function AddIdUse(int $IdsUse)
    {
        if(array_search($IdsUse, $this->IdsUse) !== false)
            new Exception("Is exists");
        $this->IdsUse[] = $IdsUse;
    }


    public function RemoveIdUse(int $IdsUse)
    {
        $Find = array_search($IdsUse, $this->IdsUse);
        if($Find !== false)
            new Exception("Not exists");

        unset($this->IdsUse[$Find]);
        sort($this->IdsUse);
    }





    static public function FindById(int $Id, bool $CheckAccess = true) : ?ExternalPhone
    {
        $Find = (self::CreateClassObjs(parent::Find(EXTERNAL_PHONE, "external_phone_id", "external_phone_id = $1", [$Id], null, 1), $CheckAccess))[0];
        return empty($Find) ? null : $Find;
    }


    static public function FindByPhone(string $Phone, bool $CheckAccess = true) : ?ExternalPhone
    {
        $Find = (self::CreateClassObjs(parent::Find(EXTERNAL_PHONE, "external_phone_id", "phone = $1", [$Phone], null, 1), $CheckAccess))[0];
        return empty($Find) ? null : $Find;
    }


    static public function FindAll(string $Where = null, array $Parameters = [], int $Offset = null, int $Limit = null, bool $CheckAccess = true) : array
    {
        return self::CreateClassObjs(parent::Find(EXTERNAL_PHONE, "external_phone_id", $Where, $Parameters, $Offset, $Limit), $CheckAccess);
    }




    /**
     * @return array ExternalPhone Classes
     */
    static private function CreateClassObjs(array $Obj, bool $CheckAccess = true) : array
    {
        $Out = [];
        foreach($Obj as $TempObj)
        {
            try
            {
                $Out[] = new ExternalPhone(
                    $TempObj->external_phone_id,
                    $TempObj->phone,
                    json_decode($TempObj->ids_use, true),
                    $CheckAccess
                );
            }
            catch(Exception $error){}
        }
        return $Out;
    }
}