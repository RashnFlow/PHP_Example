<?php


namespace models;

use classes\Tools;
use Exception;


class Instagram extends CRUD
{
    private ?int     $Id                    = null;
    private ?int     $UserId                = null;
    private ?string  $Login                 = null;
    private ?string  $Password              = null;
    private ?string  $Status                = self::STATUSES[0];
    private ?string  $Session               = null;
    private ?int     $ProxyId               = null;
    private ?int     $DefaultFolder         = null;
    private int      $StatusId              = 0;
    private bool     $IsActive              = false;
    private bool     $IsBanned              = false;
    private bool     $CommentTracking       = false;
    private bool     $SubscriberTracking    = false;
    private bool     $IsNew                 = false;


    public const STATUSES = [
        "Не активирован",
        "Активен",
        "Не удалось подключиться",
        "Двухфакторная аутентификация...",
        "Отключен"
    ];


    /**
     * Do not send parameters when calling the constructor.
     * They are needed for the methods "Find". Instead, use
     * the "Set..()" methods to set values and "Get..()" to read them.
     */
    public function __construct(
        int     $Id                 = null,
        int     $UserId             = null,
        string  $Login              = null,
        string  $Password           = null,
        string  $Status             = null,
        string  $Session            = null,
        int     $ProxyId            = null,
        int     $DefaultFolder      = null,
        int     $StatusId           = null,
        bool    $IsActive           = null,
        bool    $IsBanned           = null,
        bool    $CommentTracking    = null,
        bool    $SubscriberTracking = null,
        bool    $CheckAccess        = true)
    {
        if($CheckAccess) Authorization::IsAccess(Authentication::GetAuthUser(), "GetInstagram", $this);
        if(isset($Id))
        {
            if($CheckAccess)
                Authorization::IsAccessInstagram(Authentication::GetAuthUser(), $Id);

            $this->Id                   = $Id;
            $this->UserId               = $UserId;
            $this->Login                = $Login;
            $this->Password             = $Password;
            $this->Status               = $Status;
            $this->DefaultFolder        = $DefaultFolder;
            $this->Session              = $Session;
            $this->ProxyId              = $ProxyId;
            $this->StatusId             = $StatusId;
            $this->IsActive             = $IsActive;
            $this->IsBanned             = $IsBanned;
            $this->CommentTracking      = $CommentTracking;
            $this->SubscriberTracking   = $SubscriberTracking;
        }
        else
            $this->IsNew = true;
    }

    /**
     * Creates or updates
     */
    public function Save(bool $CheckAccess = true) : int
    {
        if($CheckAccess) Authorization::IsAccess(Authentication::GetAuthUser(), "SetInstagram", $this);
        return $this->Id =
        parent::Save(   INSTAGRAM_TABLE,
                        "instagram_id",
                        $this->Id,
                        [
                            'user_id',
                            'login',
                            'password',
                            'status',  
                            'status_id',
                            'is_active',
                            'is_banned',
                            'session',
                            'proxy_id',
                            'comment_tracking',
                            'subscriber_tracking',
                            'default_folder'
                        ],
                        "$1,$2,$3,$4,$5,$6,$7,$8,$9,$10,$11,$12",
                        [
                            $this->UserId,
                            $this->Login,
                            $this->Password,
                            $this->Status,
                            $this->StatusId,
                            $this->IsActive,
                            $this->IsBanned,
                            $this->Session,
                            $this->ProxyId,
                            $this->CommentTracking,
                            $this->SubscriberTracking,
                            $this->DefaultFolder
                        ]
                    );
    }


    /**
     * Removes
     */
    public function Delete(bool $CheckAccess = true)
    {
        if($CheckAccess) Authorization::IsAccess(Authentication::GetAuthUser(), "SetInstagram", $this);
        
        QueryCreator::Delete(DIALOG_TABLE, "(properties->'InstagramId')::int = $1", [$this->GetId()])->Run();

        parent::Delete(INSTAGRAM_TABLE, "instagram_id", $this->Id);
        $this->Id = null;
    }


    public function IsNew() : bool
    {
        return $this->IsNew;
    }


    public function GetUser() : User
    {
        $User = User::FindById($this->GetUserId());
        if(empty($User)) throw new Exception("User is not found");
        return $User;
    }


    private function GetSecretKey() : string
    {
        return Tools::GenerateStringBySeed(100, (int)implode("", unpack("s*", $this->GetLogin() . "_" . $this->GetUserId() . "_" . INSTAGRAM_SDK_API_KEY)));
    }


    public function GetUserId() : ?int
    {
        return $this->UserId;
    }


    public function GetStatusId() : ?int
    {
        return $this->StatusId;
    }


    public function GetDefaultFolder() : int
    {
        return !empty($this->DefaultFolder) ? $this->DefaultFolder : Folder::FindDefault($this->UserId)->GetId();
    }


    public function GetIsActive() : bool
    {
        return ($this->GetIsBanned()) ? false : $this->IsActive;
    }


    public function GetCommentTracking() : bool
    {
        return $this->CommentTracking;
    }


    public function GetSubscriberTracking() : bool
    {
        return $this->SubscriberTracking;
    }


    public function GetIsBanned() : bool
    {
        return $this->IsBanned;
    }


    public function GetId() : ?int
    {
        return $this->Id;
    }


    public function GetStatus() : ?string
    {
        return $this->Status;
    }


    public function GetProxyId() : ?int
    {
        return $this->ProxyId;
    }


    public function GetSession() : ?string
    {
        return $this->Session;
    }


    public function GetLogin() : ?string
    {
        return $this->Login;
    }


    public function GetPassword() : ?string
    {
        return openssl_decrypt($this->Password, "AES-192-CBC", $this->GetSecretKey());
    }


    public function SetIsNew(bool $IsNew)
    {
        $this->IsNew = $IsNew;
    }


    public function SetUserId(int $UserId)
    {
        $this->UserId = $UserId;
    }


    public function SetLogin(string $Login)
    {
        $this->Login = $Login;
    }


    public function SetSession(string $Session)
    {
        $this->Session = $Session;
    }


    public function SetProxyId(string $ProxyId)
    {
        $this->ProxyId = $ProxyId;
    }


    public function SetDefaultFolder(int $DefaultFolder)
    {
        $this->DefaultFolder = $DefaultFolder;
    }


    public function SetStatusId(int $StatusId)
    {
        $this->StatusId = $StatusId;
        $this->Status   = self::STATUSES[$StatusId];

        $this->SetIsActive($StatusId == 1);
    }


    public function SetIsActive(bool $IsActive)
    {
        $this->IsActive = $IsActive;
    }


    public function SetCommentTracking(bool $CommentTracking)
    {
        $this->CommentTracking = $CommentTracking;
    }


    public function SetSubscriberTracking(bool $SubscriberTracking)
    {
        $this->SubscriberTracking = $SubscriberTracking;
    }


    public function SetIsBanned(bool $IsBanned)
    {
        ($IsBanned) ? $this->SetStatusId(4) : $this->SetStatusId(3);
        $this->IsBanned = $IsBanned;
    }


    public function SetPassword(string $Password)
    {
        $this->Password = openssl_encrypt($Password, "AES-192-CBC", $this->GetSecretKey());
    }



    static public function FindById(int $InstagramId, bool $CheckAccess = true) : ?Instagram
    {
        $Find = (self::CreateClassObjs(parent::Find(INSTAGRAM_TABLE, "instagram_id", "instagram_id = $1", [$InstagramId], null, 1), $CheckAccess))[0];
        return empty($Find) ? null : $Find;
    }


    static public function FindByLogin(string $Login, bool $CheckAccess = true) : ?Instagram
    {
        $Find = (self::CreateClassObjs(parent::Find(INSTAGRAM_TABLE, "instagram_id", "login = $1", [$Login], null, 1), $CheckAccess))[0];
        return empty($Find) ? null : $Find;
    }
    
    
    static public function FindAll(string $Where = null, array $Parameters = [], int $Offset = null, int $Limit = null, bool $CheckAccess = true) : array
    {
        return self::CreateClassObjs(parent::Find(INSTAGRAM_TABLE, "instagram_id", $Where, $Parameters, $Offset, $Limit), $CheckAccess);
    }
  
  
    static public function FindAllBannedInstagramByUserId(int $UserId = null, bool $CheckAccess = true) : array
    {
        if(empty($UserId)) $UserId = Authentication::GetAuthUser()->GetId();
        return self::CreateClassObjs(parent::Find(INSTAGRAM_TABLE, "instagram_id", "user_id = $1 and is_banned = $2", [$UserId, true]), $CheckAccess);
    }


    static public function FindAllByUserId(int $UserId = null, bool $CheckAccess = true) : array
    {
        if(empty($UserId)) $UserId = Authentication::GetAuthUser()->GetId();
        return self::CreateClassObjs(parent::Find(INSTAGRAM_TABLE, "instagram_id", "user_id = $1", [$UserId]), $CheckAccess);
    }


    static public function FindAllActive(int $Offset = null, int $Limit = null, bool $CheckAccess = true) : array
    {
        return self::CreateClassObjs(parent::Find(INSTAGRAM_TABLE, "instagram_id", "is_active = true", [], $Offset, $Limit), $CheckAccess);
    }


    /**
     * @return array Instagram Classes
     */
    static public function CreateClassObjs(array $Obj, $CheckAccess = true) : array
    {
        $Out = [];
        foreach($Obj as $TempObj)
        {
            $Out[] =    new Instagram(
                $TempObj->instagram_id,
                $TempObj->user_id,
                $TempObj->login,
                $TempObj->password,
                $TempObj->status,
                $TempObj->session,
                $TempObj->proxy_id,
                $TempObj->default_folder,
                $TempObj->status_id,
                $TempObj->is_active,
                $TempObj->is_banned,
                $TempObj->comment_tracking,
                $TempObj->subscriber_tracking,
                $CheckAccess
            );
        }
        return $Out;
    }
}