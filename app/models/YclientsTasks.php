<?php

namespace models;

use classes\Tools;

class YclientsTasks extends CRUD
    {
        
        private ?int     $Id                        = null;
        private ?int     $YclientsIntegrationId     = null;
        private ?string  $Type                      = null;
        private ?string  $TaskName                  = null;
        private array    $Parameters                = [];
        private array    $IgnorePhone               = [];



        /**
         * Do not send parameters when calling the constructor.
         * They are needed for the methods "Find". Instead, use
         * the "Set..()" methods to set values and "Get..()" to read them.
         */
        public function __construct(
            int     $Id                             = null, 
            int     $YclientsIntegrationId          = null, 
            string  $Type                           = null,
            string  $TaskName                       = null,
            array   $Parameters                     = [],
            array   $IgnorePhone                    = [],
            bool    $CheckAccess                    = true)
        {
            if($CheckAccess) Authorization::IsAccess(Authentication::GetAuthUser(), "GetYclientsTasks");
            if(isset($Id))
            {
                if($CheckAccess)
                    Authorization::IsAccessYclientsTasks(Authentication::GetAuthUser(), $Id);

                $this->Id                           = $Id;
                $this->YclientsIntegrationId        = $YclientsIntegrationId;
                $this->Type                         = $Type;
                $this->TaskName                     = $TaskName;
                $this->Parameters                   = $Parameters;
                $this->IgnorePhone                  = $IgnorePhone;
            }
        }

        /**
         * Creates or updates
         */
        public function Save(bool $CheckAccess = true) : int
        {
            if($CheckAccess) Authorization::IsAccess(Authentication::GetAuthUser(), "SetYclientsTasks");
            return $this->Id =
            parent::Save(   YCLIENTS_INTEGRATION_TASK_TABLE,
                            "task_id",
                            $this->Id,
                            [
                                'yclients_integration_id',
                                'type',
                                'task_name',
                                'parameters',
                                'ignore_phone'
                            ],
                            "$1,$2,$3,$4,$5",
                            [
                                $this->YclientsIntegrationId,
                                $this->Type,
                                $this->TaskName,
                                $this->Parameters,
                                $this->IgnorePhone
                            ]
                        );
        }

        /**
         * Removes
         */
        public function Delete(bool $CheckAccess = true)
        {
            if($CheckAccess) Authorization::IsAccess(Authentication::GetAuthUser(), "SetYclientsTasks");
            
            parent::Delete(YCLIENTS_INTEGRATION_TASK_TABLE, "task_id", $this->Id);
            $this->Id = null;
        }

        public function GetYclientsIntegrationId() : ?int
        {
            return $this->YclientsIntegrationId;
        }


        public function GetId() : ?int
        {
            return $this->Id;
        }

        public function GetType() : ?string
        {
            return $this->Type;
        }

        public function GetTaskName() : ?string
        {
            return $this->TaskName;
        }

        public function GetParameters() : ?array
        {
            return $this->Parameters;
        }

        public function GetIgnorePhone(string $Name) : ?array
        {
            return $this->IgnorePhone[$Name]["Data"];
        }
        
        public function SetYclientsIntegrationId(int $YclientsIntegrationId)
        {
            $this->YclientsIntegrationId = $YclientsIntegrationId;
        }

        public function SetType(string $Type)
        {
            $this->Type = $Type;
        }

        public function SetTaskName(string $TaskName)
        {
            $this->TaskName = $TaskName;
        }

        public function SetParameters(array $Parameters)
        {
            $this->Parameters = $Parameters;
        }

        public function SetIgnorePhone(string $Name, $Data, $Flag = false)
        {
            $this->IgnorePhone[$Name] = ["Data" => $Data, "Time" => time(), "Flag" => $Flag];
        }
    

        static public function FindByType(string $YclientsIntegrationType, bool $CheckAccess = true) : ?YclientsTasks
        {
            $Find = (self::CreateClassObjs(parent::Find(YCLIENTS_INTEGRATION_TASK_TABLE, "type", "type = $1", [$YclientsIntegrationType], null, 1), $CheckAccess))[0];
            return empty($Find) ? null : $Find;
        }

        static public function FindById(int $TaskId, bool $CheckAccess = true) : ?YclientsTasks
        {
            $Find = (self::CreateClassObjs(parent::Find(YCLIENTS_INTEGRATION_TASK_TABLE, "task_id", "task_id = $1", [$TaskId], null, 1), $CheckAccess))[0];
            return empty($Find) ? null : $Find;
        }

        static public function FindAll(string $Where = null, array $Parameters = [], int $Offset = null, int $Limit = null, bool $CheckAccess = true) : array
        {
            return self::CreateClassObjs(parent::Find(YCLIENTS_INTEGRATION_TASK_TABLE, "task_id", $Where, $Parameters, $Offset, $Limit), $CheckAccess);
        }

        static public function FindAllByYclientId(int $YclientId, int $Offset = null, int $Limit = null, bool $CheckAccess = true) : array
        {
            return self::CreateClassObjs(parent::Find(YCLIENTS_INTEGRATION_TASK_TABLE, "yclients_integration_id", "yclients_integration_id = $1", [$YclientId], $Offset, $Limit), $CheckAccess);
        }

        static public function FindAllByType(string $YclientsTaskType, int $Offset = null, int $Limit = null, bool $CheckAccess = true) : array
        {
            return self::CreateClassObjs(parent::Find(YCLIENTS_INTEGRATION_TASK_TABLE, "type", "type = $1", [$YclientsTaskType], $Offset, $Limit), $CheckAccess);
        }


        /**
         * @return array YclientsTasks Classes
         */
        static public function CreateClassObjs(array $Obj, $CheckAccess = true) : array
        {
            $Out = [];
            foreach($Obj as $TempObj)
            {
                $Out[] =    new YclientsTasks(
                    $TempObj->task_id,
                    $TempObj->yclients_integration_id,
                    $TempObj->type,
                    $TempObj->task_name,
                    json_decode($TempObj->parameters, true),
                    json_decode($TempObj->ignore_phone, true),
                    $CheckAccess
                );
            }
            return $Out;
        }
    }
    

?>