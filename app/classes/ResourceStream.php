<?php


namespace classes;


use Exception;


class ResourceStream
{
    private $Stream;
    

    /**
     * @param string $Resource Url or FileName
     */
    public function __construct(string $Resource, string $Mode = 'r+')
    {
        $Temp = fopen($Resource, $Mode);
        if(!$Temp)
            throw new Exception("Resource open error");
            
        $this->Stream = $Temp;
        unset($Temp);
    }


    private function GetStat() : array
    {
        $Out = fstat($this->Stream);
        if(!$Out)
            throw new Exception("Unknown error");
        return $Out;
    }


    public function GetSize() : int
    {
        $Stat = $this->GetStat();
        if(!isset($Stat["size"]))
            throw new Exception("Size is null");
        return (int)$Stat["size"];
    }


    public function ReadBytes(int $Offset, int $Limit = -1)
    {
        if($Offset <= -1)
            $Offset += $this->Seek;

        $Out = stream_get_contents($this->Stream, $Limit, $Offset);
        if(!$Out)
            throw new Exception("Unknown error");
        return $Out;
    }


    public function ReadAllBytes()
    {
        return $this->ReadBytes(-1, -1);
    }
}