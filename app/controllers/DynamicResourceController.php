<?php


namespace controllers;

use classes\Tools;
use classes\Validator;
use Exception;
use models\DynamicResource;
use views\View;

class DynamicResourceController
{
    static public function CheckAccess(?string $Token, string $DynamicResourceUid = null) : bool
    {
        if(md5($Token) == GET_FILE_API_KEY || (!empty($DynamicResourceUid) && Tools::GenerateStringBySeed(100, Tools::ConvertStringToSeed($DynamicResourceUid)) == $Token))
            return true;
        throw new Exception("Access is denied", ACCESS_DENIED);
    }


    public function ActionGetDynamicResource(array $Parameters)
    {
        if(Validator::IsValid($Parameters["Get"], [
            ["Key" => "uid"],
            ["Key" => "download", "IsNull" => true]
        ]))
        {
            $Resource = DynamicResource::FindByUidAndUserId($Parameters["Get"]["uid"]);
            if(!empty($Resource) && file_exists($Resource->GetPath()))
                View::Print("DynamicResource", ["Resource" => $Resource, "IsDownload" => $Parameters["Get"]["download"] == "true"]);
            else
                View::Print("CodeErrors", ["Code" => NOT_FOUND]);
        }
    }
}