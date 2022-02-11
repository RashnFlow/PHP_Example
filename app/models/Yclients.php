<?php

namespace models;

use classes\Tools;

class Yclients extends CRUD
    {
        
        private ?int     $Id                = null;
        private ?int     $UserId            = null;
        private ?string  $Login             = null;
        private ?string  $Password          = null;
        private ?string  $UserToken         = null;
        private array    $Tasks             = [];
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
            string  $Login                   = null,
            string  $Password                = null,
            string  $UserToken               = null,
            array   $Tasks                   = [],
            array   $Cache                   = [],
            bool    $IsActive                = true,
            bool    $CheckAccess             = true)
        {
            if($CheckAccess) Authorization::IsAccess(Authentication::GetAuthUser(), "GetYclientsIntegration");
            if(isset($Id))
            {
                if($CheckAccess)
                    Authorization::IsAccessYclientsIntegration(Authentication::GetAuthUser(), $Id);

                $this->Id               = $Id;
                $this->UserId           = $UserId;
                $this->Login            = $Login;
                $this->Password         = $Password;
                $this->UserToken        = $UserToken;
                $this->Tasks            = $Tasks;
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
            if($CheckAccess) Authorization::IsAccess(Authentication::GetAuthUser(), "SetYclientsIntegration");
            return $this->Id =
            parent::Save(   YCLIENTS_INTEGRATION_TABLE,
                            "yclients_integration_id",
                            $this->Id,
                            [
                                'user_id',
                                'login',
                                'password',
                                'user_token',
                                'tasks',
                                'is_active',
                                'cache'
                            ],
                            "$1,$2,$3,$4,$5,$6,$7",
                            [
                                $this->UserId,
                                $this->Login,
                                $this->Password,
                                $this->UserToken,
                                $this->Tasks,
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
            if($CheckAccess) Authorization::IsAccess(Authentication::GetAuthUser(), "SetYclientsIntegration");
            
            parent::Delete(YCLIENTS_INTEGRATION_TABLE, "yclients_integration_id", $this->Id);
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

        public function GetLogin() : ?string
        {
            return $this->Login;
        }

        public function GetPassword() : ?string
        {
            return openssl_decrypt($this->Password, "AES-192-CBC", $this->GetSecretKey());
        }

        public function GetUserToken() : ?string
        {
            return $this->UserToken;
        }

        public function GetTaskss() : ?array
        {
            return $this->Tasks;
        }

        public function GetNewDialogAction() : ?array
        {
            return $this->NewDialogAction;
        }

        public function GetCache(string $Name)
        {
            return $this->Cache[$Name]["Data"];
        }

        private function GetSecretKey() : string
        {
            return Tools::GenerateStringBySeed(100, (int)implode("", unpack("s*", $this->GetLogin() . "_" . $this->GetUserId() . "_" . YCLIENTS_BEARER_KEY)));
        }

        public function IsActive() : bool
        {
            return $this->IsActive;
        }

        
        public function SetUserId(int $UserId)
        {
            $this->UserId = $UserId;
        }

        public function SetLogin(string $Login)
        {
            $this->Login = $Login;
        }

        public function SetPassword(string $Password)
        {
            $this->Password = openssl_encrypt($Password, "AES-192-CBC", $this->GetSecretKey());
        }

        public function SetUserToken(string $UserToken)
        {
            $this->UserToken = $UserToken;
        }

        public function SetTaskss(array $Tasks)
        {
            $this->Tasks = $Tasks;
        }

        public function SetNewDialogAction(array $NewDialogAction)
        {
            $this->NewDialogAction =  $NewDialogAction;
        }

        public function SetCache(string $Name, $Data, $Flag = false)
        {
            $this->Cache[$Name] = ["Data" => $Data, "Time" => time(), "Flag" => $Flag];
        }


        static public function FindByLoginAndUserId(string $YclientsIntegrationLogin, int $UserId, bool $CheckAccess = true) : ?Yclients
        {
            $Find = (self::CreateClassObjs(parent::Find(YCLIENTS_INTEGRATION_TABLE, "yclients_integration_id", "login = $1 and user_id = $2", [$YclientsIntegrationLogin, $UserId], null, 1), $CheckAccess))[0];
            return empty($Find) ? null : $Find;
        }
    

        static public function FindByLogin(string $YclientsIntegrationLogin, bool $CheckAccess = true) : ?Yclients
        {
            $Find = (self::CreateClassObjs(parent::Find(YCLIENTS_INTEGRATION_TABLE, "yclients_integration_id", "login = $1", [$YclientsIntegrationLogin], null, 1), $CheckAccess))[0];
            return empty($Find) ? null : $Find;
        }

        static public function FindByUserId(int $UserId = null, bool $CheckAccess = true) : ?Yclients
        {
            if(empty($UserId)) $UserId = Authentication::GetAuthUser()->GetId();
            $Find = (self::CreateClassObjs(parent::Find(YCLIENTS_INTEGRATION_TABLE, "yclients_integration_id", "user_id = $1", [$UserId], null, 1), $CheckAccess))[0];
            return empty($Find) ? null : $Find;
        }

        static public function FindById(int $YclientsIntegrationId, bool $CheckAccess = true) : ?Yclients
        {
            $Find = (self::CreateClassObjs(parent::Find(YCLIENTS_INTEGRATION_TABLE, "yclients_integration_id", "yclients_integration_id = $1", [$YclientsIntegrationId], null, 1), $CheckAccess))[0];
            return empty($Find) ? null : $Find;
        }

        static public function FindAll(string $Where = null, array $Parameters = [], int $Offset = null, int $Limit = null, bool $CheckAccess = true) : array
        {
            return self::CreateClassObjs(parent::Find(YCLIENTS_INTEGRATION_TABLE, "yclients_integration_id", $Where, $Parameters, $Offset, $Limit), $CheckAccess);
        }

        static public function FindAllActiveIntegration( int $Offset = null, int $Limit = null, bool $CheckAccess = true) : array
        {
            return self::CreateClassObjs(parent::Find(YCLIENTS_INTEGRATION_TABLE, "yclients_integration_id", "user_id > 0", [], $Offset, $Limit), $CheckAccess);
        }


        /**
         * @return array Yclients Classes
         */
        static public function CreateClassObjs(array $Obj, $CheckAccess = true) : array
        {
            $Out = [];
            foreach($Obj as $TempObj)
            {
                $Out[] =    new Yclients(
                    $TempObj->yclients_integration_id,
                    $TempObj->user_id,
                    $TempObj->login,
                    $TempObj->password,
                    $TempObj->user_token,
                    json_decode($TempObj->tasks, true),
                    json_decode($TempObj->cache, true),
                    $TempObj->is_active,
                    $CheckAccess
                );
            }
            return $Out;
        }
    }
    

?>