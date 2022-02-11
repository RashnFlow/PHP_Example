<?php


namespace classes;


class Log
{
    public const TYPE_FATAL_ERROR   = "Fatal Error";
    public const TYPE_ERROR         = "Error";
    public const TYPE_WARNING       = "Warning";
    public const TYPE_INFO          = "Info";


    private ?string  $Type          = null;
    private ?string  $Message       = null;
    private ?string  $Source        = null;
    private $Data                   = null;
    private ?int     $UserId        = null;
    private ?string  $SessionUid    = null;


    public function GetSessionUid() : ?string
    {
        return $this->SessionUid;
    }


    public function GetUserId() : ?int
    {
        return $this->UserId;
    }


    public function GetMessage() : ?string
    {
        return $this->Message;
    }


    public function GetType() : ?string
    {
        return $this->Type;
    }


    public function GetSource() : ?string
    {
        return $this->Source;
    }


    public function GetData()
    {
        return $this->Data;
    }


    public function SetData($Data)
    {
        $this->Data = $Data;
    }


    public function SetMessage(string $Message)
    {
        $this->Message = $Message;
    }


    public function SetSource(string $Source)
    {
        $this->Source = $Source;
    }


    public function SetType(string $Type)
    {
        $this->Type = $Type;
    }


    public function SetSessionUid(string $SessionUid)
    {
        $this->SessionUid = $SessionUid;
    }


    public function SetUserId(int $UserId)
    {
        $this->UserId = $UserId;
    }




    public function __toString() : string
    {
        $Data = $this->GetData();
        if(is_array($Data))
            $Data = json_encode($this->GetData());
        if(is_object($Data))
            $Data = serialize($Data);
        return date("[H:i:s]") . " SessionUid: " . (empty($this->GetSessionUid()) ? "null" : $this->GetSessionUid()) . " UserId: " . (empty($this->GetUserId()) ? "null" : $this->GetUserId()) . " Type: " . (empty($this->GetType()) ? "null" : $this->GetType()) . " Message: " . (empty($this->GetMessage()) ? "null" : $this->GetMessage()) . " Data: " . (empty($Data) ? "null" : $Data) . " Source: " . (empty($this->GetSource()) ? "null" : $this->GetSource());
    }
}