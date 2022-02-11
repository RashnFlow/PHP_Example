<?php


namespace interfaces;


interface IResource
{
    public function GetName()       : ?string;
    public function GetResource()   : ?string;
    public function GetExtension()  : ?string;
    public function GetType()       : ?string;
    public function GetPath()       : ?string;
    public function GetSize()       : ?int;
}