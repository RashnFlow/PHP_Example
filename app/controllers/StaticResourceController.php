<?php


namespace controllers;

use classes\File;
use classes\StaticResources;
use classes\Validator;
use Exception;
use views\View;


class StaticResourceController
{
    public function ActionGetImageResource(array $Parameters)
    {
        if(Validator::IsValid($Parameters["Get"], [
            ["Key" => "filename"],
            ["Key" => "download", "IsNull" => true]
        ]))
        {
            try
            {
                $Resource = StaticResources::GetImage($Parameters["Get"]["filename"]);
            }
            catch(Exception $error) {}
            if(!empty($Resource) && File::Exists($Resource->GetPath()))
                View::Print("DynamicResource", ["Resource" => $Resource, "IsDownload" => $Parameters["Get"]["download"] == "true"]);
            else
                View::Print("CodeErrors", ["Code" => NOT_FOUND]);
        }
    }
}