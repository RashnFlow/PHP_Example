<?php


namespace models;


use classes\Collection;


class ModelCollection extends Collection
{
    public function SaveModels(bool $CheckAccess = true)
    {
        QueryBuffer::Start(["Update"]);
        foreach($this->List as $Model)
            $Model->Save($CheckAccess);
        $Query = QueryBuffer::Stop();
        if(!empty($Query))
            $Query->Run();
    }


    public function DeleteModels(bool $CheckAccess = true)
    {
        QueryBuffer::Start(["Delete"]);
        foreach($this->List as $Model)
            $Model->Delete($CheckAccess);
        $Query = QueryBuffer::Stop();
        if(!empty($Query))
            $Query->Run();
    }
}