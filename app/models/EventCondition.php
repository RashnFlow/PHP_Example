<?php


namespace models;

use models\dialogues\Dialog;

class EventCondition
{
    public const OPERATOR_AND       = "&&";
    public const OPERATOR_OR        = "||";

    public const TYPE_WORD_EXISTS       = "WordExists";
    public const TYPE_WORD_NOT_EXISTS   = "WordNotExists";


    private ?string  $Type = null;
    private          $Data = null;




    /**
     * Do not send parameters when calling the constructor.
     * They are needed for the methods "Find". Instead, use
     * the "Set..()" methods to set values and "Get..()" to read them.
     */
    public function __construct(string $Type = null, $Data = null)
    {
        if(isset($Type))
        {
            $this->Type     = $Type;
            $this->Data     = $Data;
        }
    }


    public function Check(Dialog $Dialog) : bool
    {
        $Out = false;
        switch($this->Type)
        {
            case self::TYPE_WORD_EXISTS:
            case self::TYPE_WORD_NOT_EXISTS:
                $Count = preg_match_all("/\b" . $this->Data["Text"] . "\b/ui", $Dialog->GetLastMessage()->GetText());
                
                if($this->Data["Count"] > 0)
                    $Out = $Count >= $this->Data["Count"];
                else
                    $Out = $Count > 0;
                break;
        }

        if($this->Type == self::TYPE_WORD_NOT_EXISTS)
            $Out = !$Out;

        return $Out;
    }


    public function GetType() : ?string
    {
        return $this->Type;
    }


    public function GetData()
    {
        return $this->Data;
    }


    public function SetType(string $Type)
    {
        $this->Type = $Type;
    }


    public function SetData($Data)
    {
        $this->Data = $Data;
    }




    public function ToArray() : array
    {
        return
        [
            "Type"      => $this->GetType(),
            "Data"      => base64_encode(serialize($this->GetData()))
        ];
    }


    static public function CreateEventObj(array $ArrayObj) : EventCondition
    {
        return new EventCondition(
            $ArrayObj["Type"],
            unserialize(base64_decode($ArrayObj["Data"]))
        );
    }
}