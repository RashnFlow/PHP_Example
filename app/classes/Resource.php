<?php


namespace classes;


use Exception;
use interfaces\IResource;


class Resource implements IResource
{
    private string $FileName;


    public function __construct(string $FileName)
    {
        $this->FileName = $FileName;
    }


    public function GetName() : string
    {
        return basename($this->FileName);
    }


    public function GetSize() : int
    {
        $Size = filesize($this->GetPath());
        if($Size === false)
            throw new Exception('Error get file size');
        return $Size;
    }


    public function GetPath() : string
    {
        return $this->FileName;
    }


    public function GetType() : string
    {
        $MIME = mime_content_type($this->FileName);
        if($MIME === false)
            new Exception('Error get mime content type');
        return $MIME;
    }


    public function GetExtension() : string
    {
        return end(explode('.', $this->FileName));
    }


    public function GetResource() : string
    {
        if(!File::Exists($this->FileName)) throw new Exception("Resource is empty");
        return File::ReadAllText($this->FileName);
    }
}