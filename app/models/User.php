<?php


namespace models;


class User extends CRUD
{
    public const USER_TYPE_CUSTOMER = "customer";
    public const USER_TYPE_ADMIN    = "admin";
    public const USER_TYPE_SUPPORT  = "support";
    public const USER_TYPE_SYSTEM   = "system";


    private ?string  $UserType       = null;
    private ?int     $Id             = null;
    private ?string  $Login          = null;
    private ?string  $Phone          = null;
    private ?string  $Name           = null;
    private ?string  $Password       = null;
    private ?string  $Email          = null;
    private array    $Permissions    = [];
    private bool     $IsActive       = false;
    private array    $Rules          = [];
    private          $Avatar         = null;
    private ?string  $AuthSession    = null;
    private ?int     $CreatedAt      = null;
    private ?int     $UpdatedAt      = null;


    /**
     * Do not send parameters when calling the constructor.
     * They are needed for the methods "Find". Instead, use
     * the "Set..()" methods to set values and "Get..()" to read them.
     */
    public function __construct(
        int     $Id             = null,
        string  $Login          = null,
        string  $Password       = null,
        string  $Phone          = null,
        string  $Name           = null,
        string  $Email          = null,
        bool    $IsActive       = false,
        array   $Rules          = [],
        string  $UserType       = null,
        array   $Permissions    = [],
        $Avatar                 = null,
        int     $CreatedAt      = null,
        int     $UpdatedAt      = null
    )
    {
        if(isset($Id))
        {
            $this->Id               = $Id;
            $this->Login            = $Login;
            $this->Phone            = $Phone;
            $this->Name             = $Name;
            $this->Password         = $Password;
            $this->Permissions      = $Permissions;
            $this->Email            = $Email;
            $this->IsActive         = $IsActive;
            $this->Rules            = $Rules;
            $this->UserType         = $UserType;
            $this->Avatar           = $Avatar;
            $this->CreatedAt        = $CreatedAt;
            $this->UpdatedAt        = $UpdatedAt;
        }
    }


    /**
     * Creates or updates
     */
    public function Save() : int
    {
        return $this->Id =
        parent::Save(   USER_TABLE,
                        "user_id",
                        $this->Id,
                        [
                            'login',
                            'password',
                            'user_type',
                            'avatar',
                            'is_active',
                            'rules',
                            'email',
                            'phone',
                            'permissions',
                            'name'
                        ]
                        ,"$1,$2,$3,$4,$5,$6,$7,$8,$9,$10",
                        [
                            $this->Login,
                            base64_encode($this->Password),
                            $this->UserType,
                            $this->Avatar,
                            $this->IsActive,
                            $this->Rules,
                            $this->Email,
                            $this->Phone,
                            $this->Permissions,
                            $this->Name
                        ],
                        true
                    );
    }


    /**
     * Removes
     */
    public function Delete()
    {
        parent::Delete(USER_TABLE, "user_id", $this->Id);
        $this->Id = null;
    }


    public function GetSession() : ?string
    {
        return $this->AuthSession;
    }


    public function GetUpdatedAt() : ?int
    {
        return $this->UpdatedAt;
    }


    public function GetCreatedAt() : ?int
    {
        return $this->CreatedAt;
    }


    public function GetLogin() : ?string
    {
        return $this->Login;
    }


    public function GetName() : ?string
    {
        return $this->Name;
    }


    public function GetPermissions() : ?array
    {
        return $this->Permissions;
    }


    public function GetPermissionVal(string $Permission) : ?int
    {
        $Access = $this->Permissions[0];
        if(isset($Access[$Permission]))
            return $Access[$Permission];
        else
            return Authorization::GetAccessVal($this->UserType, $Permission);
    }


    public function GetPhone() : ?string
    {
        return $this->Phone;
    }


    public function GetEmail() : ?string
    {
        return $this->Email;
    }


    public function GetIsActive() : bool
    {
        return $this->IsActive;
    }


    public function GetRules() : ?array
    {
        return $this->Rules;
    }


    public function GetPassword() : ?string
    {
        return $this->Password;
    }


    public function GetUserType() : ?string
    {
        return $this->UserType;
    }


    public function GetId() : ?int
    {
        return $this->Id;
    }


    /**
     * @return string Image byte
     */
    public function GetAvatar() : ?string
    {
        return pg_unescape_bytea($this->Avatar);
    }


    protected function SetId(int $Id)
    {
        $this->Id = $Id;
    }


    public function SetLogin(string $Login)
    {
        $this->Login = $Login;
    }


    public function SetName(string $Name)
    {
        $this->Name = $Name;
    }


    public function SetPhone(string $Phone)
    {
        $this->Phone = $Phone;
    }


    public function SetPermissionVal(string $Permission, int $Val)
    {
        $this->Permissions[$Permission] = $Val;
    }


    public function SetAllPermissions(array $Permissions)
    {
        $this->Permissions = $Permissions;
    }


    public function SetPermissions(array $Permissions)
    {
        $this->Permissions = $Permissions;
    }
    

    public function SetEmail(string $Email)
    {
        $this->Email = $Email;
    }


    public function SetPassword(string $Password)
    {
        $this->Password = password_hash($Password, PASSWORD_DEFAULT);
    }


    public function SetUserType(string $UserType)
    {
        $this->UserType = $UserType;
    }


    public function SetIsActive(bool $IsActive)
    {
        $this->IsActive = $IsActive;
    }


    public function SetRulesVal(string $Rule, bool $Val)
    {
        $this->Rules[$Rule] = $Val;
    }


    public function SetAvatar($Avatar)
    {
        $this->Avatar = pg_escape_bytea($Avatar);
    }


    public function SetSession(string $Session)
    {
        $this->AuthSession = $Session;
    }


    static public function FindByLogin(string $Login) : ?User
    {
        $Find = (self::CreateClassObjs(parent::Find(USER_TABLE, "user_id", "login = $1", [$Login], null, 1)))[0];
        return empty($Find) ? null : $Find;
    }


    static public function FindByEmail(string $Email) : ?User
    {
        $Find = (self::CreateClassObjs(parent::Find(USER_TABLE, "user_id", "email = $1", [$Email], null, 1)))[0];
        return empty($Find) ? null : $Find;
    }


    static public function FindAll(string $Where = null, array $Parameters = [], int $Offset = null, int $Limit = null) : array
    {
        return self::CreateClassObjs(parent::Find(USER_TABLE, "user_id", $Where, $Parameters, $Offset, $Limit));
    }


    static public function FindById(int $Id) : ?User
    {
        $Find = (self::CreateClassObjs(parent::Find(USER_TABLE, "user_id", "user_id = $1", [$Id], null, 1)))[0];
        return empty($Find) ? null : $Find;
    }


    static public function FindByUserType(string $Type) : ?User
    {
        $Find = (self::CreateClassObjs(parent::Find(USER_TABLE, "user_id", "user_type = $1", [$Type], null, 1)))[0];
        return empty($Find) ? null : $Find;
    }


    /**
     * @return array User Classes
     */
    static private function CreateClassObjs(array $Obj) : array
    {
        $Out = [];
        foreach($Obj as $TempObj)
        {
            $Out[] =    new User(
                $TempObj->user_id,
                $TempObj->login,
                base64_decode($TempObj->password),
                $TempObj->phone,
                $TempObj->name,
                $TempObj->email,
                $TempObj->is_active,
                json_decode($TempObj->rules, true),
                $TempObj->user_type,
                json_decode($TempObj->permissions, true),
                $TempObj->avatar,
                strtotime($TempObj->created_at),
                strtotime($TempObj->updated_at),
            );
        }
        return $Out;
    }
}