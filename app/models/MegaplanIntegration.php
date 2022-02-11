<?php

    namespace models;

    class MegaplanIntegration extends CRUD
    {
        
        private ?int     $Id                = null;
        private ?int     $UserId            = null;
        private array    $Accounts          = [];
        private ?string  $ProfileURL        = null;
        private ?string  $AccessToken       = null;
        private ?string  $RefreshToken      = null;
        private array    $NewDialogAction   = [];
        private array    $FunnelAction      = [];
        private array    $Cache             = [];
        private bool     $IsActive          = true;



        /**
         * Do not send parameters when calling the constructor.
         * They are needed for the methods "Find". Instead, use
         * the "Set..()" methods to set values and "Get..()" to read them.
         */
        public function __construct(
            int     $Id                      = null, 
            int     $UserId                  = null, 
            array   $Accounts                = [], 
            string  $ProfileURL              = null, 
            string  $AccessToken             = null, 
            string  $RefreshToken            = null,
            array   $NewDialogAction         = [],
            array   $FunnelAction            = [],
            array   $Cache                   = [],
            bool    $IsActive                = true,
            bool    $CheckAccess             = true)
        {
            if($CheckAccess) Authorization::IsAccess(Authentication::GetAuthUser(), "GetMegaplanIntegration");
            if(isset($Id))
            {
                if($CheckAccess)
                    Authorization::IsMegaplanIntegration(Authentication::GetAuthUser(), $Id);

                $this->Id               = $Id;
                $this->UserId           = $UserId;
                $this->Accounts         = $Accounts;
                $this->ProfileURL       = $ProfileURL;
                $this->AccessToken      = $AccessToken;
                $this->RefreshToken     = $RefreshToken;
                $this->NewDialogAction  = $NewDialogAction;
                $this->FunnelAction     = $FunnelAction;
                $this->Cache            = $Cache;
                $this->IsActive         = $IsActive;

                $this->ClearInvalidCache();
            }
        }

        /**
         * Creates or updates
         */
        public function Save(bool $CheckAccess = true) : int
        {
            if($CheckAccess) Authorization::IsAccess(Authentication::GetAuthUser(), "SetMegaplanIntegration");
            return $this->Id =
            parent::Save(   MEGAPLAN_INTEGRATION_TABLE,
                            "megaplan_integration_id",
                            $this->Id,
                            [
                                'user_id',
                                'profile_url',
                                'accounts',
                                'access_token',
                                'refresh_token',
                                'new_dialog_action',
                                'funnel_action',
                                'is_active',
                                'cache'
                            ],
                            "$1,$2,$3,$4,$5,$6,$7,$8,$9",
                            [
                                $this->UserId,
                                $this->ProfileURL,
                                $this->Accounts,
                                $this->AccessToken,
                                $this->RefreshToken,
                                $this->NewDialogAction,
                                $this->FunnelAction,
                                $this->IsActive,
                                $this->Cache
                            ]
                        );
        }

        /**
         * Removes
         */
        public function Delete(bool $CheckAccess = true)
        {
            if($CheckAccess) Authorization::IsAccess(Authentication::GetAuthUser(), "SetAmoCRMIntegration");
            
            parent::Delete(MEGAPLAN_INTEGRATION_TABLE, "megaplan_integration_id", $this->Id);
            $this->Id = null;
        }

        private function ClearInvalidCache()
        {
            foreach($this->Cache as &$obj)
                if($obj["Time"] < (time() - 1209600)){
                    if($obj["Flag"] = true) continue;
                    unset($obj);
                }
                    
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


        public function GetProfileURL() : ?string
        {
            return $this->ProfileURL;
        }

        public function GetAccessToken() : ?string
        {
            return $this->AccessToken;
        }

        public function GetRefreshToken() : ?string
        {
            return $this->RefreshToken;
        }

        public function GetFunnelActions() : ?array
        {
            return $this->FunnelAction;
        }

        public function GetNewDialogAction() : ?array
        {
            return $this->NewDialogAction;
        }

        public function GetCache(string $Name)
        {
            return $this->Cache[$Name]["Data"];
        }

        public function IsActive() : bool
        {
            return $this->IsActive;
        }

        
        public function SetUserId(int $UserId)
        {
            $this->UserId = $UserId;
        }

        public function SetAccountId(string $Type, int $Id)
        {
            $this->Accounts[$Type] = $Id;
        }

        public function SetProfileURL(string $ProfileURL)
        {
            $this->ProfileURL = $ProfileURL;
        }

        public function SetAccessToken(string $AccessToken)
        {
            $this->AccessToken = $AccessToken;
        }

        public function SetRefreshToken(string $RefreshToken)
        {
            $this->RefreshToken = $RefreshToken;
        }

        public function SetFunnelActions(array $FunnelAction)
        {
            $this->FunnelAction = $FunnelAction;
        }

        public function SetNewDialogAction(array $NewDialogAction)
        {
            $this->NewDialogAction =  $NewDialogAction;
        }

        public function SetCache(string $Name, $Data, $Flag = false)
        {
            $this->Cache[$Name] = ["Data" => $Data, "Time" => time(), "Flag" => $Flag];
        }
    

        static public function FindByProfileUrl(string $AmoCRMIntegrationURL, bool $CheckAccess = true) : ?AmoCRMIntegration
        {
            $Find = (self::CreateClassObjs(parent::Find(MEGAPLAN_INTEGRATION_TABLE, "megaplan_integration_id", "profile_url = $1", [$AmoCRMIntegrationURL], null, 1), $CheckAccess))[0];
            return empty($Find) ? null : $Find;
        }

        static public function FindByUserId(int $UserId = null, bool $CheckAccess = true) : ?AmoCRMIntegration
        {
            if(empty($UserId)) $UserId = Authentication::GetAuthUser()->GetId();
            $Find = (self::CreateClassObjs(parent::Find(MEGAPLAN_INTEGRATION_TABLE, "megaplan_integration_id", "user_id = $1", [$UserId], null, 1), $CheckAccess))[0];
            return empty($Find) ? null : $Find;
        }

        static public function FindById(int $AmoCRMIntegrationId, bool $CheckAccess = true) : ?AmoCRMIntegration
        {
            $Find = (self::CreateClassObjs(parent::Find(MEGAPLAN_INTEGRATION_TABLE, "megaplan_integration_id", "megaplan_integration_id = $1", [$AmoCRMIntegrationId], null, 1), $CheckAccess))[0];
            return empty($Find) ? null : $Find;
        }

        static public function FindAll(string $Where = null, array $Parameters = [], int $Offset = null, int $Limit = null, bool $CheckAccess = true) : array
        {
            return self::CreateClassObjs(parent::Find(MEGAPLAN_INTEGRATION_TABLE, "megaplan_integration_id", $Where, $Parameters, $Offset, $Limit), $CheckAccess);
        }

        static public function FindAllActiveIntegration( int $Offset = null, int $Limit = null, bool $CheckAccess = true) : array
        {
            return self::CreateClassObjs(parent::Find(MEGAPLAN_INTEGRATION_TABLE, "megaplan_integration_id", "user_id > 0", [], $Offset, $Limit), $CheckAccess);
        }


        /**
         * @return array AmoCRM Classes
         */
        static public function CreateClassObjs(array $Obj, $CheckAccess = true) : array
        {
            $Out = [];
            foreach($Obj as $TempObj)
            {
                $Out[] =    new AmoCRMIntegration(
                    $TempObj->megaplan_integration_id,
                    $TempObj->user_id,
                    json_decode($TempObj->accounts, true),
                    $TempObj->profile_url,  
                    $TempObj->access_token,
                    $TempObj->refresh_token,
                    json_decode($TempObj->new_dialog_action, true),
                    json_decode($TempObj->funnel_action, true),
                    json_decode($TempObj->cache, true),
                    $TempObj->is_active,
                    $CheckAccess
                );
            }
            return $Out;
        }
    }
    

?>