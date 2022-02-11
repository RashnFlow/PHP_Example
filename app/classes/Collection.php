<?php

namespace classes;


use Exception;


class Collection
{
    protected array $List = [];


    public function Add($Obj)
    {
        if(!isset($Obj))
            throw new Exception("Obj is null");
        $this->List[] = $Obj;
    }


    public function Remove($Obj)
    {
        unset($this->List[array_search($Obj, $this->List)]);
    }


    public function RemoveAt(int $Index)
    {
        unset($this->List[$Index]);
    }


    public function ToArray()
    {
        return $this->List;
    }


    public function Clear()
    {
        $this->List = [];
    }
}