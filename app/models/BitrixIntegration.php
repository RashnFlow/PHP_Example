<?php


namespace models;


class BitrixIntegration extends CRUD
{
    private ?int     $Id                = null;
    private ?int     $UserId            = null;
    private array    $Accounts          = [];
    private array    $Cache             = [];
    private ?string  $ProfileURL        = null;
    private ?string  $AccessToken       = null;
    private ?string  $RefreshToken      = null;
    private ?string  $ApplicationToken  = null;
    private array    $FunnelActions     = [];
    private array    $NewDialogAction   = [];



    /**
     * Do not send parameters when calling the constructor.
     * They are needed for the methods "Find". Instead, use
     * the "Set..()" methods to set values and "Get..()" to read them.
     */
    public function __construct(
        int     $Id                  = null,
        int     $UserId              = null,
        array   $Accounts            = [],
        string  $ProfileURL          = null,
        string  $AccessToken         = null,
        string  $RefreshToken        = null,
        string  $ApplicationToken    = null,
        array   $FunnelActions       = [],
        array   $Cache               = [],
        array   $NewDialogAction     = [],
        bool $CheckAccess = true)
    {
        if($CheckAccess) Authorization::IsAccess(Authentication::GetAuthUser(), "GetBitrixIntegration");
        if(isset($Id))
        {
            if($CheckAccess)
                Authorization::IsAccessBitrixIntegration(Authentication::GetAuthUser(), $Id);

            $this->Id               = $Id;
            $this->UserId           = $UserId;
            $this->Accounts         = $Accounts;
            $this->ProfileURL       = $ProfileURL;
            $this->AccessToken      = $AccessToken;
            $this->RefreshToken     = $RefreshToken;
            $this->ApplicationToken = $ApplicationToken;
            $this->FunnelActions    = $FunnelActions;
            $this->NewDialogAction  = $NewDialogAction;
            $this->Cache            = $Cache;

            $this->ClearInvalidCache();
        }
    }

    /**
     * Creates or updates
     */
    public function Save(bool $CheckAccess = true) : int
    {
        if($CheckAccess) Authorization::IsAccess(Authentication::GetAuthUser(), "SetBitrixIntegration");
        return $this->Id =
        parent::Save(   BITRIX_INTEGRATION_TABLE,
                        "bitrix_integration_id",
                        $this->Id,
                        [
                            'user_id',
                            'profile_url',
                            'accounts',
                            'access_token',
                            'refresh_token',
                            'application_token',
                            'funnel_actions',
                            'new_dialog_action',
                            'cache'
                        ],
                        "$1,$2,$3,$4,$5,$6,$7,$8,$9",
                        [
                            $this->UserId,
                            $this->ProfileURL,
                            $this->Accounts,
                            $this->AccessToken,
                            $this->RefreshToken,
                            $this->ApplicationToken,
                            $this->FunnelActions,
                            $this->NewDialogAction,
                            $this->Cache
                        ]
                    );
    }


    /**
     * Removes
     */
    public function Delete(bool $CheckAccess = true)
    {
        if($CheckAccess) Authorization::IsAccess(Authentication::GetAuthUser(), "SetBitrixIntegration");
        
        parent::Delete(BITRIX_INTEGRATION_TABLE, "bitrix_integration_id", $this->Id);
        $this->Id = null;
    }


    private function ClearInvalidCache()
    {
        foreach($this->Cache as &$obj)
            if($obj["Time"] < (time() - 1209600))
                unset($obj);
    }


    public function GetUserId() : ?int
    {
        return $this->UserId;
    }


    public function GetId() : ?int
    {
        return $this->Id;
    }


    public function GetAccountId(string $Type) : ?int
    {
        return $this->Accounts[$Type];
    }


    public function GetCache(string $Name)
    {
        return $this->Cache[$Name]["Data"];
    }


    public function GetProfileURL() : ?string
    {
        return $this->ProfileURL;
    }


    public function GetFunnelActions() : array
    {
        return $this->FunnelActions;
    }


    public function GetNewDialogAction() : array
    {
        return $this->NewDialogAction;
    }


    public function GetAccessToken() : ?string
    {
        return $this->AccessToken;
    }


    public function GetRefreshToken() : ?string
    {
        return $this->RefreshToken;
    }


    public function GetApplicationToken() : ?string
    {
        return $this->ApplicationToken;
    }


    public function IsActive() : bool
    {
        return $this->UserId != null;
    }


    public function SetUserId(int $UserId)
    {
        $this->UserId = $UserId;
    }


    public function SetAccountId(string $Type, int $Id)
    {
        $this->Accounts[$Type] = $Id;
    }


    public function SetCache(string $Name, $Data)
    {
        $this->Cache[$Name] = ["Data" => $Data, "Time" => time()];
    }


    public function SetProfileURL(string $ProfileURL)
    {
        $this->ProfileURL = $ProfileURL;
    }


    public function SetFunnelActions(array $FunnelActions)
    {
        $this->FunnelActions = $FunnelActions;
    }


    public function SetNewDialogAction(array $NewDialogAction)
    {
        $this->NewDialogAction = $NewDialogAction;
    }

    
    public function SetAccessToken(string $AccessToken)
    {
        $this->AccessToken = $AccessToken;
    }


    public function SetRefreshToken(string $RefreshToken)
    {
        $this->RefreshToken = $RefreshToken;
    }


    public function SetApplicationToken(string $ApplicationToken)
    {
        $this->ApplicationToken = $ApplicationToken;
    }




    static public function FindByProfileUrl(string $BitrixIntegrationURL, bool $CheckAccess = true) : ?BitrixIntegration
    {
        $Find = (self::CreateClassObjs(parent::Find(BITRIX_INTEGRATION_TABLE, "bitrix_integration_id", "profile_url = $1", [$BitrixIntegrationURL], null, 1), $CheckAccess))[0];
        return empty($Find) ? null : $Find;
    }


    static public function FindById(int $BitrixIntegrationId, bool $CheckAccess = true) : ?BitrixIntegration
    {
        $Find = (self::CreateClassObjs(parent::Find(BITRIX_INTEGRATION_TABLE, "bitrix_integration_id", "bitrix_integration_id = $1", [$BitrixIntegrationId], null, 1), $CheckAccess))[0];
        return empty($Find) ? null : $Find;
    }


    static public function FindByUserId(int $UserId = null, bool $CheckAccess = true) : ?BitrixIntegration
    {
        if(empty($UserId)) $UserId = Authentication::GetAuthUser()->GetId();
        $Find = (self::CreateClassObjs(parent::Find(BITRIX_INTEGRATION_TABLE, "bitrix_integration_id", "user_id = $1", [$UserId], null, 1), $CheckAccess))[0];
        return empty($Find) ? null : $Find;
    }


    static public function FindByApplicationToken(int $BitrixIntegrationApplicationToken, bool $CheckAccess = true) : ?BitrixIntegration
    {
        $Find = (self::CreateClassObjs(parent::Find(BITRIX_INTEGRATION_TABLE, "bitrix_integration_id", "application_token = $1", [$BitrixIntegrationApplicationToken], null, 1), $CheckAccess))[0];
        return empty($Find) ? null : $Find;
    }


    static public function FindAll(string $Where = null, array $Parameters = [], int $Offset = null, int $Limit = null, bool $CheckAccess = true) : array
    {
        return self::CreateClassObjs(parent::Find(BITRIX_INTEGRATION_TABLE, "bitrix_integration_id", $Where, $Parameters, $Offset, $Limit), $CheckAccess);
    }


    static public function FindAllActiveIntegration( int $Offset = null, int $Limit = null, bool $CheckAccess = true) : array
    {
        return self::CreateClassObjs(parent::Find(BITRIX_INTEGRATION_TABLE, "bitrix_integration_id", "user_id > 0", [], $Offset, $Limit), $CheckAccess);
    }




    /**
     * @return array Bitrix Classes
     */
    static public function CreateClassObjs(array $Obj, $CheckAccess = true) : array
    {
        $Out = [];
        foreach($Obj as $TempObj)
        {
            $Out[] =    new BitrixIntegration(
                $TempObj->bitrix_integration_id,
                $TempObj->user_id,
                json_decode($TempObj->accounts, true),
                $TempObj->profile_url,  
                $TempObj->access_token,
                $TempObj->refresh_token,
                $TempObj->application_token,
                json_decode($TempObj->funnel_actions, true),
                json_decode($TempObj->cache, true),
                json_decode($TempObj->new_dialog_action, true),
                $CheckAccess
            );
        }
        return $Out;
    }
}