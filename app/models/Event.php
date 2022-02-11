<?php


namespace models;

use models\dialogues\Dialog;

class Event
{
    public const ACTION_SEND_MESSAGE    = "SendMessage";
    public const ACTION_MOVE_TO_FOLDER  = "MoveToFolder";

    private ?int     $Id                 = null;
    private ?string  $Event              = null;
    private array    $StartCondition     = [];
    private ?string  $ActionType         = null;
    private          $ActionData         = null;
    private ?string  $ElseActionType     = null;
    private          $ElseActionData     = null;
    private bool     $IsActive           = false;
    private array    $Completed          = [];
    private bool     $DisableDialog      = true;


    /**
     * Do not send parameters when calling the constructor.
     * They are needed for the methods "Find". Instead, use
     * the "Set..()" methods to set values and "Get..()" to read them.
     */
    public function __construct(
        int     $Id = null,
        string  $Event = null,
        string  $ActionType = null,
                $ActionData = null,
        string  $ElseActionType = null,
                $ElseActionData = null,
        array   $Completed = null,
        bool    $IsActive = false,
        array   $StartCondition = null,
        bool    $DisableDialog = true)
    {
        if(isset($Id))
        {
            $this->Id               = $Id;
            $this->Event            = $Event;
            $this->ActionType       = $ActionType;
            $this->ActionData       = $ActionData;
            $this->ElseActionType   = $ElseActionType;
            $this->ElseActionData   = $ElseActionData;
            $this->IsActive         = $IsActive;
            $this->Completed        = empty($Completed) ? [] : $Completed;
            $this->StartCondition   = empty($StartCondition) ? [] : $StartCondition;
            $this->DisableDialog    = $DisableDialog;
        }
    }


    public function Run(Dialog &$Dialog) : bool
    {
        if(array_search($Dialog->GetId(), $this->Completed) !== false)  return false;
        
        if($this->CheckStartCondition($Dialog))
            $this->RunAction($Dialog, $this->ActionType, $this->ActionData);
        else if(!empty($this->ElseActionType) && !empty($this->ElseActionData))
            $this->RunAction($Dialog, $this->ElseActionType, $this->ElseActionData);
        else
            return false;

        if($this->DisableDialog)
            $this->Completed[] = $Dialog->GetId();
        return true;
    }


    public function RunAction(Dialog &$Dialog, string $ActionType, $ActionData)
    {
        switch($ActionType)
        {
            case "SendMessage":
                (new \controllers\MessageController())->SendMessage($Dialog, $ActionData);
                break;

            case "MoveToFolder":
                $Dialog->SetFolderId($ActionData);
                $Dialog->Save();
                break;
        }
    }


    private function CheckStartCondition(Dialog $Dialog) : bool
    {
        $Out        = true;
        $Operator   = "";
        foreach ($this->StartCondition as $Condition)
        {
            if($Condition instanceof EventCondition)
            {
                $TempOut = $Condition->Check($Dialog);

                if(!empty($Operator))
                {
                    switch($Operator)
                    {
                        case EventCondition::OPERATOR_AND:
                            $Out = $Out && $TempOut;
                            break;

                        case EventCondition::OPERATOR_OR:
                            $Out = $Out || $TempOut;
                            break;
                    }
                }
                else
                    $Out = $TempOut;
            }
            else
                $Operator = $Condition;
        }

        return $Out;
    }


    public function GetId() : ?int
    {
        return $this->Id;
    }


    public function GetEvent() : ?string
    {
        return $this->Event;
    }


    public function GetDisableDialog() : bool
    {
        return $this->DisableDialog;
    }


    public function GetCompleted() : array
    {
        return $this->Completed;
    }


    public function GetStartCondition() : array
    {
        return $this->StartCondition;
    }


    public function GetActionType() : ?string
    {
        return $this->ActionType;
    }


    public function GetActionData()
    {
        return $this->ActionData;
    }


    public function GetElseActionType() : ?string
    {
        return $this->ElseActionType;
    }


    public function GetElseActionData()
    {
        return $this->ElseActionData;
    }


    public function GetIsActive() : bool
    {
        return $this->IsActive;
    }


    public function SetEvent(string $Event)
    {
        $this->Event = $Event;
    }


    public function SetId(int $Id)
    {
        $this->Id = $Id;
    }


    public function SetActionType(string $ActionType)
    {
        $this->ActionType = $ActionType;
    }


    public function SetElseActionType(string $ElseActionType)
    {
        $this->ElseActionType = $ElseActionType;
    }


    public function SetStartCondition(array $StartCondition)
    {
        $this->StartCondition = $StartCondition;
    }


    public function SetActionData($ActionData)
    {
        $this->ActionData = $ActionData;
    }


    public function SetElseActionData($ElseActionData)
    {
        $this->ElseActionData = $ElseActionData;
    }


    public function SetIsActive(bool $IsActive)
    {
        $this->IsActive = $IsActive;
    }


    public function SetDisableDialog(bool $DisableDialog)
    {
        $this->DisableDialog = $DisableDialog;
    }





    public function ToArray() : array
    {
        return
        [
            "Id"                => $this->GetId(),
            "Event"             => $this->GetEvent(),
            "ActionType"        => $this->GetActionType(),
            "ActionData"        => base64_encode(serialize($this->GetActionData())),
            "ElseActionType"    => $this->GetElseActionType(),
            "ElseActionData"    => base64_encode(serialize($this->GetElseActionData())),
            "IsActive"          => $this->GetIsActive(),
            "Completed"         => $this->GetCompleted(),
            "StartCondition"    => base64_encode(serialize($this->GetStartCondition())),
            "DisableDialog"     => $this->GetDisableDialog()
        ];
    }


    static public function CreateEventObj(array $ArrayObj) : Event
    {
        return new Event(
            $ArrayObj["Id"],
            $ArrayObj["Event"],
            $ArrayObj["ActionType"],
            unserialize(base64_decode($ArrayObj["ActionData"])),
            $ArrayObj["ElseActionType"],
            unserialize(base64_decode($ArrayObj["ElseActionData"])),
            $ArrayObj["Completed"],
            $ArrayObj["IsActive"],
            unserialize(base64_decode($ArrayObj["StartCondition"])),
            $ArrayObj["DisableDialog"]
        );
    }
}