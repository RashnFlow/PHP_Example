<?php


namespace controllers;


use classes\Validator;
use models\DynamicResource;
use views\View;

class ImageController
{
    public function ActionGetImage(array $Parameters)
    {
        if(Validator::IsValid($Parameters["Get"], [
            ["Key" => "image_uid"]
        ]))
        {
            $Resource = DynamicResource::FindByUidAndUserId($Parameters["Get"]["image_uid"]);
            if(!empty($Resource))
                View::Print("Image", ["Type" => "jpeg", "Image" => $Resource->GetResource()]);
            else
                View::Print("CodeErrors", ["Code" => NOT_FOUND]);
        }
    }
}