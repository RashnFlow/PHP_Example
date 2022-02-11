<?php


namespace models;


class Task extends CRUD
{
    private ?int     $Id             = null;
    private ?string  $Type           = null;
    private          $Data           = null;
    private          $Response       = null;
    private bool     $IsRunning      = false;
    private bool     $IsСompleted    = false;
    private ?int     $CreatedAt      = null;
    private ?int     $UpdatedAt      = null;
    private ?int     $Failed         = null;


    /**
     * Do not send parameters when calling the constructor.
     * They are needed for the methods "Find". Instead, use
     * the "Set..()" methods to set values and "Get..()" to read them.
     */
    public function __construct(
        int     $Id             = null,
        string  $Type           = null,
                $Data           = null,
                $Response       = null,
        bool    $IsRunning      = false,
        bool    $IsСompleted    = false,
        int     $CreatedAt      = null,
        int     $UpdatedAt      = null,
        int     $Failed         = null,
        bool    $CheckAccess    = true)
    {
        if($CheckAccess) Authorization::IsAccess(Authentication::GetAuthUser(), "GetTask");
        if(isset($Id))
        {
            $this->Id           = $Id;
            $this->Type         = $Type;
            $this->Data         = $Data;
            $this->Response     = $Response;
            $this->IsRunning    = $IsRunning;
            $this->IsСompleted  = $IsСompleted;
            $this->CreatedAt    = $CreatedAt;
            $this->UpdatedAt    = $UpdatedAt;
            $this->Failed       = $Failed;
        }
    }

    /**
     * Creates or updates
     */
    public function Save(bool $CheckAccess = true) : int
    {
        if($CheckAccess) Authorization::IsAccess(Authentication::GetAuthUser(), "SetTask");
        return $this->Id =
        parent::Save(   TASK_TABLE,
                        "task_id",
                        $this->Id,
                        [
                            'type',
                            'data',
                            'is_running',
                            'failed',
                            'is_completed',
                            'response'
                        ],
                        "$1,$2,$3,$4,$5,$6",
                        [
                            $this->Type,
                            $this->Data,
                            $this->IsRunning,
                            $this->Failed,
                            $this->IsСompleted,
                            $this->Response
                        ], true
                    );
    }


    /**
     * Removes
     */
    public function Delete(bool $CheckAccess = true)
    {
        if($CheckAccess) Authorization::IsAccess(Authentication::GetAuthUser(), "SetTask");
        
        parent::Delete(TASK_TABLE, "task_id", $this->Id);
        $this->Id = null;
    }


    public function GetId() : ?int
    {
        return $this->Id;
    }


    public function GetFailed() : ?int
    {
        return $this->Failed;
    }


    public function GetType() : ?string
    {
        return $this->Type;
    }


    public function GetData()
    {
        return unserialize($this->Data);
    }


    public function GetResponse()
    {
        return unserialize($this->Response);
    }


    public function GetUpdatedAt() : ?int
    {
        return $this->UpdatedAt;
    }


    public function GetCreatedAt() : ?int
    {
        return $this->CreatedAt;
    }


    public function GetIsRunning() : bool
    {
        return $this->IsRunning;
    }


    public function GetIsСompleted() : bool
    {
        return $this->IsСompleted;
    }


    public function SetData($Data)
    {
        $this->Data = serialize($Data);
    }


    public function SetResponse($Response)
    {
        $this->Response = serialize($Response);
    }


    public function SetIsRunning(bool $IsRunning)
    {
        $this->IsRunning = $IsRunning;
    }


    public function SetIsСompleted(bool $IsСompleted)
    {
        $this->IsСompleted = $IsСompleted;
    }


    public function SetType(string $Type)
    {
        $this->Type = $Type;
    }


    public function Fail()
    {
        $this->Failed++;
    }
    





    static public function FindById(int $TaskId, bool $CheckAccess = true) : ?Task
    {
        $Find = (self::CreateClassObjs(parent::Find(TASK_TABLE, "task_id", "task_id = $1", [$TaskId], null, 1), $CheckAccess))[0];
        return empty($Find) ? null : $Find;
    }


    static public function Next(string $Type, bool $CheckAccess = true) : ?Task
    {
        $Find = (self::CreateClassObjs(parent::Find(TASK_TABLE, "task_id", "type = $1 and is_running = false and is_completed = false", [$Type], null, 1), $CheckAccess))[0];
        
        if(!empty($Find))
        {
            $Find->SetIsRunning(true);
            $Find->Save();
        }
        
        return empty($Find) ? null : $Find;
    }


    static public function FindAllByType(string $Type, int $Offset = null, int $Limit = null, bool $CheckAccess = true) : array
    {
        return self::CreateClassObjs(parent::Find(TASK_TABLE, "task_id", "type = $1", [$Type], $Offset, $Limit), $CheckAccess);
    }


    static public function FindAll(string $Where = null, array $Parameters = [], int $Offset = null, int $Limit = null, bool $CheckAccess = true) : array
    {
        return self::CreateClassObjs(parent::Find(TASK_TABLE, "task_id", $Where, $Parameters, $Offset, $Limit), $CheckAccess);
    }


    /**
     * @return array Task Classes
     */
    static public function CreateClassObjs(array $Obj, $CheckAccess = true) : array
    {
        $Out = [];
        foreach($Obj as $TempObj)
        {
            $Out[] =    new Task(
                $TempObj->task_id,
                $TempObj->type,
                $TempObj->data,
                $TempObj->response,
                $TempObj->is_running,
                $TempObj->is_completed,
                strtotime($TempObj->created_at),
                strtotime($TempObj->updated_at),
                $TempObj->failed,
                $CheckAccess
            );
        }
        return $Out;
    }
}