<?php


namespace models;


use Exception;


class DynamicMassSendingPhone extends CRUD
{
    private ?int     $Id                    = null;
    private ?int     $DynamicMassSendingId  = null;
    private ?int     $WhatsappId            = null;
    private ?string  $Phone                 = null;
    private ?string  $Name                  = null;
    private ?string  $Status                = null;
    private bool     $IsRead                = false;
    private bool     $IsResponse            = false;
    private bool     $IsSent                = false;
    private bool     $IsDone                = false;
    private bool     $IsBusy                = false;
    private ?int     $CreatedAt             = null;
    private ?int     $UpdatedAt             = null;



    /**
     * Do not send parameters when calling the constructor.
     * They are needed for the methods "Find". Instead, use
     * the "Set..()" methods to set values and "Get..()" to read them.
     */
    public function __construct(
        int     $Id                     = null,
        int     $DynamicMassSendingId   = null,
        int     $WhatsappId             = null,
        string  $Phone                  = null,
        string  $Name                   = null,
        string  $Status                 = null,
        bool    $IsRead                 = false,
        bool    $IsResponse             = false,
        bool    $IsSent                 = false,
        bool    $IsDone                 = false,
        bool    $IsBusy                 = false,
        int     $CreatedAt              = null,
        int     $UpdatedAt              = null,
        
        bool    $CheckAccess = true
    )
    {
        if($CheckAccess) Authorization::IsAccess(Authentication::GetAuthUser(), "GetDynamicMassSending");
        if(isset($Id))
        {
            if($CheckAccess)
                Authorization::IsAccessDynamicMassSending(Authentication::GetAuthUser(), $Id);

            $this->Id                       = $Id;
            $this->DynamicMassSendingId     = $DynamicMassSendingId;
            $this->WhatsappId               = $WhatsappId;
            $this->Phone                    = $Phone;
            $this->Name                     = $Name;
            $this->Status                   = $Status;
            $this->IsRead                   = $IsRead;
            $this->IsResponse               = $IsResponse;
            $this->IsSent                   = $IsSent;
            $this->IsDone                   = $IsDone;
            $this->IsBusy                   = $IsBusy;
            $this->CreatedAt                = $CreatedAt;
            $this->UpdatedAt                = $UpdatedAt;
        }
    }

    /**
     * Creates or updates
     */
    public function Save(bool $CheckAccess = true) : int
    {
        if($CheckAccess) Authorization::IsAccess(Authentication::GetAuthUser(), "SetDynamicMassSending");
        return $this->Id =
        parent::Save(   DYNAMIC_MASS_SENDING_PHONE,
                        "dynamic_mass_sending_phone_id",
                        $this->Id,
                        [
                            'dynamic_mass_sending_id',
                            'whatsapp_id',
                            'phone',
                            'name',
                            'status',
                            'is_read',
                            'is_response',
                            'is_sent',
                            'is_done',
                            'is_busy'
                        ],
                        "$1,$2,$3,$4,$5,$6,$7,$8,$9,$10",
                        [
                            $this->DynamicMassSendingId,
                            $this->WhatsappId,
                            $this->Phone,
                            $this->Name,
                            $this->Status,
                            $this->IsRead,
                            $this->IsResponse,
                            $this->IsSent,
                            $this->IsDone,
                            $this->IsBusy,
                        ],
                        true
                    );
    }


    /**
     * Removes
     */
    public function Delete(bool $CheckAccess = true)
    {
        if($CheckAccess) Authorization::IsAccess(Authentication::GetAuthUser(), "SetDynamicMassSending");
        parent::Delete(DYNAMIC_MASS_SENDING_PHONE, "dynamic_mass_sending_phone_id", $this->Id);
        $this->Id = null;
    }


    public function GetDynamicMassSendingId() : ?int
    {
        return $this->DynamicMassSendingId;
    }


    public function GetId() : ?int
    {
        return $this->Id;
    }


    public function GetUpdatedAt() : ?int
    {
        return $this->UpdatedAt;
    }


    public function GetCreatedAt() : ?int
    {
        return $this->CreatedAt;
    }


    public function GetWhatsappId() : ?int
    {
        return $this->WhatsappId;
    }


    public function GetIsRead() : ?bool
    {
        return $this->IsRead;
    }


    public function GetIsSent() : ?bool
    {
        return $this->IsSent;
    }


    public function GetIsResponse() : ?bool
    {
        return $this->IsResponse;
    }


    public function GetIsDone() : ?bool
    {
        return $this->IsDone;
    }


    public function GetIsBusy() : ?bool
    {
        return $this->IsBusy;
    }


    public function GetPhone() : ?string
    {
        return $this->Phone;
    }


    public function GetName() : ?string
    {
        return $this->Name;
    }


    public function GetStatus() : ?string
    {
        return $this->Status;
    }


    public function SetDynamicMassSendingId(int $DynamicMassSendingId)
    {
        $this->DynamicMassSendingId = $DynamicMassSendingId;
    }


    public function SetWhatsappId(int $WhatsappId)
    {
        $this->WhatsappId = $WhatsappId;
    }


    public function SetIsDone(bool $IsDone)
    {
        $this->IsDone = $IsDone;
    }


    public function SetIsBusy(bool $IsBusy)
    {
        $this->IsBusy = $IsBusy;
    }


    public function SetIsRead(bool $IsRead)
    {
        $this->IsRead = $IsRead;
    }


    public function SetIsResponse(bool $IsResponse)
    {
        $this->IsResponse = $IsResponse;
    }


    public function SetIsSent(bool $IsSent)
    {
        $this->IsSent = $IsSent;
    }


    public function SetPhone(string $Phone)
    {
        $this->Phone = $Phone;
    }


    public function SetName(string $Name)
    {
        $this->Name = $Name;
    }


    public function SetStatus(string $Status)
    {
        $this->Status = $Status;
    }




    static public function Next(int $DynamicMassSendingId, bool $CheckAccess = true) : ?DynamicMassSendingPhone
    {
        $Find = (self::CreateClassObjs(parent::Find(DYNAMIC_MASS_SENDING_PHONE, "dynamic_mass_sending_phone_id", "dynamic_mass_sending_id = $1 and is_busy = false and is_done = false", [$DynamicMassSendingId], null, 1), $CheckAccess))[0];
        
        if(empty($Find))
            throw new Exception("Phone not found");
        
        $Find->SetIsBusy(true);
        $Find->Save();
        
        return empty($Find) ? null : $Find;
    }


    static public function FindById(int $DynamicMassSendingPhoneId, bool $CheckAccess = true) : ?DynamicMassSendingPhone
    {
        $Find = (self::CreateClassObjs(parent::Find(DYNAMIC_MASS_SENDING_PHONE, "dynamic_mass_sending_phone_id", "dynamic_mass_sending_phone_id = $1", [$DynamicMassSendingPhoneId], null, 1), $CheckAccess))[0];
        return empty($Find) ? null : $Find;
    }


    static public function FindByPhoneAndDynamicMassSendingId(string $Phone, int $DynamicMassSendingId, bool $CheckAccess = true) : ?DynamicMassSendingPhone
    {
        $Find = (self::CreateClassObjs(parent::Find(DYNAMIC_MASS_SENDING_PHONE, "dynamic_mass_sending_phone_id", "dynamic_mass_sending_id = $1 and phone = $2", [$DynamicMassSendingId, $Phone], null, 1), $CheckAccess))[0];
        return empty($Find) ? null : $Find;
    }


    static public function FindByWhatsappId(int $WhatsappId, bool $CheckAccess = true) : ?DynamicMassSendingPhone
    {
        $Find = (self::CreateClassObjs(parent::Find(DYNAMIC_MASS_SENDING_PHONE, "dynamic_mass_sending_phone_id", "whatsapp_id = $1", [$WhatsappId], null, 1), $CheckAccess))[0];
        return empty($Find) ? null : $Find;
    }


    static public function FindAll(string $Where = null, array $Parameters = [], int $Offset = null, int $Limit = null, bool $CheckAccess = true) : array
    {
        return self::CreateClassObjs(parent::Find(DYNAMIC_MASS_SENDING_PHONE, "dynamic_mass_sending_phone_id", $Where, $Parameters, $Offset, $Limit), $CheckAccess);
    }


    /**
     * @return array DynamicMassSendingPhone Classes
     */
    static public function CreateClassObjs(array $Obj, bool $CheckAccess = true) : array
    {
        $Out = [];
        foreach($Obj as $TempObj)
        {
            $Out[] =    new DynamicMassSendingPhone(
                $TempObj->dynamic_mass_sending_stack_id,
                $TempObj->dynamic_mass_sending_id,
                $TempObj->whatsapp_id,
                $TempObj->phone,
                $TempObj->name,
                $TempObj->status,
                $TempObj->is_read,
                $TempObj->is_response,
                $TempObj->is_sent,
                $TempObj->is_done,
                $TempObj->is_busy,
                strtotime($TempObj->created_at),
                strtotime($TempObj->updated_at),
                $CheckAccess
            );
        }
        return $Out;
    }
}