<?php


namespace models;


use classes\Validator;
use Exception;


class Whatsapp extends CRUD
{
    public const ACTIVITIES = [
        "Не компания",
        "Услуги для автомобилей",
        "Одежда",
        "Искусство и развлечения",
        "Красота, косметика и уход за собой",
        "Образование",
        "Организатор мероприятий",
        "Финансы",
        "Продуктовый магазин",
        "Гостиница",
        "Медицина и здоровье",
        "Некоммерческая организация",
        "Ресторан",
        "Покупки и розничная торговля"
    ];

    public const STATUSES = [
        "Не активирован",
        "Активен",
        "Проблемы с подключением к телефону",
        "Недействительная сессия",
        "Отключен"
    ];

    public const LOCATION_TYPE_USER_LOCAIIZATION    = "UserLocalization";
    public const LOCATION_TYPE_SERVER_LOCAIIZATION  = "ServerLocalization";


    private ?int     $Id                    = null;
    private ?int     $UserId                = null;
    private ?string  $Phone                 = null;
    private ?string  $Name                  = null;
    private ?string  $Status                = self::STATUSES[0];
    private int      $StatusId              = 0;
    private string   $LocationType          = "UserLocalization";
    private bool     $IsActive              = false;
    private array    $VenomSessions         = [];
    private ?int     $DeviceId              = null;
    private ?int     $ProxyId               = null;
    private ?int     $WAppId                = null;
    private ?int     $DefaultFolder         = null;
    private bool     $IsDynamic             = false;
    private bool     $IsBanned              = false;
    private bool     $IsBusiness            = false;
    private array    $SendCountToDay        = [];
    private ?int     $SendCountDay          = null;
    private          $Avatar                = null;
    private ?string  $CompanyName           = null;
    private ?int     $ActivityId            = null;
    private bool     $IsNew                 = false;
    private ?int     $CreatedAt      = null;
    private ?int     $UpdatedAt      = null;


    /**
     * Do not send parameters when calling the constructor.
     * They are needed for the methods "Find". Instead, use
     * the "Set..()" methods to set values and "Get..()" to read them.
     */
    public function __construct(
        int     $Id                     = null,
        int     $UserId                 = null,
        string  $Phone                  = null,
        string  $Status                 = null,
        string  $Name                   = null,
        int     $StatusId               = null,
        int     $ProxyId                = null,
        string  $LocationType           = null,
        bool    $IsActive               = false,
        array   $VenomSessions          = null,
        int     $DeviceId               = null,
        int     $WAppId                 = null,
        int     $DefaultFolder          = null,
        bool    $IsDynamic              = false,
        bool    $IsBanned               = false,
        bool    $IsBusiness             = false,
        array   $SendCountToDay         = [],
        int     $SendCountDay           = null,
                $Avatar                 = null,
        string  $CompanyName            = null,
        int     $ActivityId             = null,
        int     $CreatedAt              = null,
        int     $UpdatedAt              = null,
        bool    $CheckAccess            = true)
    {
        if($CheckAccess) Authorization::IsAccess(Authentication::GetAuthUser(), "GetWhatsapp", $this);
        if(isset($Id))
        {
            if($CheckAccess)
                Authorization::IsAccessWhatsapp(Authentication::GetAuthUser(), $Id);

            $this->Id                       = $Id;
            $this->UserId                   = $UserId;
            $this->Phone                    = $Phone;
            $this->ProxyId                  = $ProxyId;
            $this->Status                   = $Status;
            $this->Name                     = $Name;
            $this->StatusId                 = $StatusId;
            $this->DefaultFolder            = $DefaultFolder;
            $this->LocationType             = $LocationType;
            $this->IsActive                 = $IsActive;
            $this->DeviceId                 = $DeviceId;
            $this->WAppId                   = $WAppId;
            $this->IsDynamic                = $IsDynamic;
            $this->IsBanned                 = $IsBanned;
            $this->IsBusiness               = $IsBusiness;
            $this->VenomSessions            = $VenomSessions;
            $this->SendCountToDay           = $SendCountToDay;
            $this->SendCountDay             = $SendCountDay;
            $this->CompanyName              = $CompanyName;
            $this->ActivityId               = $ActivityId;
            $this->Avatar                   = $Avatar;
            $this->CreatedAt                = $CreatedAt;
            $this->UpdatedAt                = $UpdatedAt;
        }
        else
            $this->IsNew = true;
    }

    /**
     * Creates or updates a User record
     */
    public function Save(bool $CheckAccess = true) : int
    {
        if($CheckAccess) Authorization::IsAccess(Authentication::GetAuthUser(), "SetWhatsapp", $this);
        return $this->Id =
        parent::Save(   WHATSAPP_TABLE,
                        "whatsapp_id",
                        $this->Id,
                        [
                            'phone',
                            'user_id',
                            'venom_session',
                            'is_active',
                            'status',
                            'name',
                            'location_type',
                            'status_id',
                            'device_id',
                            'w_app_id',
                            'is_dynamic',
                            'is_banned',
                            'send_count_to_day',
                            'send_count_day',
                            'company_name',
                            'activity_id',
                            'avatar',
                            'is_business',
                            'default_folder',
                            'proxy_id'
                        ],
                        "$1,$2,$3,$4,$5,$6,$7,$8,$9,$10,$11,$12,$13,$14,$15,$16,$17,$18,$19,$20",
                        [
                            $this->Phone,
                            $this->UserId,
                            $this->VenomSessions,
                            $this->IsActive,
                            $this->Status,
                            $this->Name,
                            $this->LocationType,
                            $this->StatusId,
                            $this->DeviceId,
                            $this->WAppId,
                            $this->IsDynamic,
                            $this->IsBanned,
                            $this->SendCountToDay,
                            $this->SendCountDay,
                            $this->CompanyName,
                            $this->ActivityId,
                            $this->Avatar,
                            $this->IsBusiness,
                            $this->DefaultFolder,
                            $this->ProxyId
                        ],
                        true
                    );
    }


    /** 
     * Removes the folder
     */
    public function Delete(bool $CheckAccess = true)
    {
        if($CheckAccess) Authorization::IsAccess(Authentication::GetAuthUser(), "SetWhatsapp", $this);

        QueryCreator::Delete(DIALOG_TABLE, "(properties->'WhatsappId')::int = $1", [$this->GetId()])->Run();

        parent::Delete(WHATSAPP_TABLE, "whatsapp_id", $this->Id);
        $this->Id = null;
    }


    public function AddOneSentMessage()
    {
        if(date("d", $this->SendCountToDay["Time"]) != date("d"))
            $this->SendCountToDay["Count"] = 0;

        $this->SendCountToDay["Count"]++;
        $this->SendCountToDay["Time"] = time();

        if($this->SendCountToDay["Count"] > $this->SendCountDay)
            $this->SendCountDay = $this->SendCountToDay["Count"];
    }


    public function IsNew() : bool
    {
        return $this->IsNew;
    }


    public function GetPhone() : ?string
    {
        return $this->Phone;
    }


    public function GetCompanyName() : ?string
    {
        return $this->CompanyName;
    }


    public function GetProxyId() : ?string
    {
        return $this->ProxyId;
    }


    public function GetName() : ?string
    {
        return $this->Name;
    }


    public function GetUserId() : ?int
    {
        return $this->UserId;
    }


    public function GetActivityId() : ?int
    {
        return $this->ActivityId;
    }


    /**
     * @return string Image byte
     */
    public function GetAvatar() : ?string
    {
        return pg_unescape_bytea($this->Avatar);
    }


    public function GetWAppId() : ?int
    {
        return $this->WAppId;
    }


    public function GetSendCountDay() : ?int
    {
        return $this->SendCountDay;
    }


    public function GetLocationType() : ?string
    {
        return $this->LocationType;
    }


    public function GetId() : ?int
    {
        return $this->Id;
    }


    public function GetDeviceId() : ?int
    {
        return $this->DeviceId;
    }


    public function GetDefaultFolder() : int
    {
        return !empty($this->DefaultFolder) ? $this->DefaultFolder : Folder::FindDefault($this->UserId)->GetId();
    }


    public function GetIsDynamic() : bool
    {
        return $this->IsDynamic;
    }


    public function GetIsBusiness() : bool
    {
        return $this->IsBusiness;
    }


    public function GetIsBanned() : bool
    {
        return $this->IsBanned;
    }


    public function GetStatusId() : ?int
    {
        return $this->StatusId;
    }


    public function GetIsActive() : bool
    {
        return ($this->GetIsBanned()) ? false :  $this->IsActive;
    }


    public function GetStatus() : ?string
    {
        return $this->Status;
    }


    public function GetVenomSessions() : array
    {
        return $this->VenomSessions;
    }


    public function GetUser() : User
    {
        $User = User::FindById($this->GetUserId());
        if(empty($User)) throw new Exception("User is not found");
        return $User;
    }


    public function GetCreatedAt() : ?int
    {
        return $this->CreatedAt;
    }


    public function GetUpdatedAt() : ?int
    {
        return $this->UpdatedAt;
    }


    public function SetIsNew(bool $IsNew)
    {
        $this->IsNew = $IsNew;
    }


    public function SetPhone(string $Phone)
    {
        $this->Phone = Validator::NormalizePhone($Phone);
    }


    public function SetProxyId(int $ProxyId)
    {
        $this->ProxyId = $ProxyId;
    }


    public function SetName(string $Name)
    {
        $this->Name = $Name;
    }


    public function SetLocationType(string $LocationType)
    {
        $this->LocationType = $LocationType;
    }


    public function SetDeviceId(int $DeviceId)
    {
        $this->DeviceId = $DeviceId;
    }


    public function SetAvatar($Avatar)
    {
        $this->Avatar = pg_escape_bytea($Avatar);
    }


    public function SetCompanyName(string $CompanyName)
    {
        $this->CompanyName = $CompanyName;
    }


    public function SetActivityId(int $ActivityId)
    {
        $this->ActivityId = $ActivityId;
    }


    public function SetSendCountDay(int $SendCountDay)
    {
        $this->SendCountDay = $SendCountDay;
    }


    public function SetDefaultFolder(int $DefaultFolder)
    {
        $this->DefaultFolder = $DefaultFolder;
    }


    public function SetIsDynamic(bool $IsDynamic)
    {
        $this->IsDynamic = $IsDynamic;
    }


    public function SetIsBanned(bool $IsBanned)
    {
        ($IsBanned) ? $this->SetStatusId(4) : $this->SetStatusId(3);
        $this->IsBanned = $IsBanned;
    }


    public function SetUserId(int $UserId)
    {
        $this->UserId = $UserId;
    }


    public function SetWAppId(int $WAppId)
    {
        $this->WAppId = $WAppId;
    }


    public function SetStatusId(int $StatusId)
    {
        if(empty(self::STATUSES[$StatusId]))
            throw new Exception("Status not found");

        $this->StatusId = $StatusId;
        $this->Status = self::STATUSES[$StatusId];
    }


    public function SetIsActive(bool $IsActive)
    {
        $this->IsActive = $IsActive;
    }


    public function SetIsBusiness(bool $IsBusiness)
    {
        $this->IsBusiness = $IsBusiness;
    }


    public function SetVenomSessions(array $VenomSessions)
    {
        $this->VenomSessions = $VenomSessions;
    }


    static public function CheckPhone(string $Phone) : bool
    {
        $Find = (self::CreateClassObjs(parent::Find(WHATSAPP_TABLE, "whatsapp_id", "phone = $1", [Validator::NormalizePhone($Phone)], null, 1), false))[0];
        return empty($Find);
    }


    static public function FindByPhoneAndUserId(string $Phone, int $UserId = null, bool $CheckAccess = true) : ?Whatsapp
    {
        if(empty($UserId)) $UserId = Authentication::GetAuthUser()->GetId();
        $Find = (self::CreateClassObjs(parent::Find(WHATSAPP_TABLE, "whatsapp_id", "phone = $1 and user_id = $2", [$Phone, $UserId], null, 1), $CheckAccess))[0];
        return empty($Find) ? null : $Find;
    }



    static public function FindByUserId(int $UserId = null, bool $CheckAccess = true) : ?Whatsapp
    {
        if(empty($UserId)) $UserId = Authentication::GetAuthUser()->GetId();
        $Find = (self::CreateClassObjs(parent::Find(WHATSAPP_TABLE, "whatsapp_id", "user_id = $1", [$UserId], null, 1), $CheckAccess))[0];
        return empty($Find) ? null : $Find;
    }
  
  
    static public function FindByPhone(string $Phone, bool $CheckAccess = true) : ?Whatsapp
    {
        $Find = (self::CreateClassObjs(parent::Find(WHATSAPP_TABLE, "whatsapp_id", "phone = $1", [$Phone], null, 1), $CheckAccess))[0];

        return empty($Find) ? null : $Find;
    }


    static public function FindById(int $WhatsappId, bool $CheckAccess = true) : ?Whatsapp
    {
        $Find = (self::CreateClassObjs(parent::Find(WHATSAPP_TABLE, "whatsapp_id", "whatsapp_id = $1", [$WhatsappId], null, 1), $CheckAccess))[0];
        return empty($Find) ? null : $Find;
    }


    static public function FindAll(string $Where = null, array $Parameters = [], int $Offset = null, int $Limit = null, bool $CheckAccess = true) : array
    {
        return self::CreateClassObjs(parent::Find(WHATSAPP_TABLE, "whatsapp_id", $Where, $Parameters, $Offset, $Limit), $CheckAccess);
    }


    static public function FindAllIsDynamic(int $Offset = null, int $Limit = null, bool $CheckAccess = true) : array
    {
        return self::CreateClassObjs(parent::Find(WHATSAPP_TABLE, "whatsapp_id", "is_dynamic = true", [], $Offset, $Limit), $CheckAccess);
    }


    static public function FindAllByUserId(int $UserId = null, int $Offset = null, int $Limit = null, bool $CheckAccess = true) : array
    {
        if(empty($UserId)) $UserId = Authentication::GetAuthUser()->GetId();
        return self::CreateClassObjs(parent::Find(WHATSAPP_TABLE, "whatsapp_id", "user_id = $1", [$UserId], $Offset, $Limit), $CheckAccess);
    }
  
  
    static public function FindAllBannedWhatsAppByUserId(int $UserId = null, int $Offset = null, int $Limit = null, bool $CheckAccess = true) : array
    {
        if(empty($UserId)) $UserId = Authentication::GetAuthUser()->GetId();
        return self::CreateClassObjs(parent::Find(WHATSAPP_TABLE, "whatsapp_id", "user_id = $1 and is_banned = $2", [$UserId, true], $Offset, $Limit), $CheckAccess);
    }


    static public function FindAllActive(int $Offset = null, int $Limit = null, bool $CheckAccess = true) : array
    {
        return self::CreateClassObjs(parent::Find(WHATSAPP_TABLE, "whatsapp_id", "is_active = true", [], $Offset, $Limit), $CheckAccess);
    }


    /**
     * @return array User Classes
     */
    static public function CreateClassObjs(array $Obj, bool $CheckAccess = true) : array
    {
        $Out = [];
        foreach($Obj as $TempObj)
        {
            $Out[] = new Whatsapp(
                $TempObj->whatsapp_id,
                $TempObj->user_id,
                $TempObj->phone,
                $TempObj->status,
                $TempObj->name,
                $TempObj->status_id,
                $TempObj->proxy_id,
                $TempObj->location_type,
                $TempObj->is_active,
                json_decode($TempObj->venom_session, true),
                $TempObj->device_id,
                $TempObj->w_app_id,
                $TempObj->default_folder,
                $TempObj->is_dynamic,
                $TempObj->is_banned,
                $TempObj->is_business,
                empty($TempObj->send_count_to_day) ? [] : json_decode($TempObj->send_count_to_day, true),
                $TempObj->send_count_day,
                $TempObj->avatar,
                $TempObj->company_name,
                $TempObj->activity_id,
                strtotime($TempObj->created_at),
                strtotime($TempObj->updated_at),
                $CheckAccess
            );
        }
        return $Out;
    }
}