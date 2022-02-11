<?php


namespace controllers;


use Exception;
use models\Authentication;
use models\DynamicResource;
use SplFileInfo;
use views\PrintJson;


class UploadController
{
    static public function CheckAccess(?string $Token) : bool
    {
        if(md5($Token) == UPLOAD_FILE_API_KEY)
            return true;
        else
            throw new Exception("Access is denied", ACCESS_DENIED);
    }


    public function ActionUploadFile(array $Parameters)
    {
        if(!empty($Parameters["Files"]))
        {
            $Out = [];
            foreach($Parameters["Files"] as $File)
            {
                if(is_uploaded_file($File['tmp_name']))
                {
                    $Resources = new DynamicResource();
                    $Resources->SetUserId(Authentication::GetAuthUser()->GetId());
                    $Resources->SetResourceByFile($File['tmp_name']);

                    try
                    {
                        if(!empty($File['type']))
                            $Resources->SetType($File['type']);

                        if(!empty($File['name']))
                        {
                            $Resources->SetName($File['name']);
                            $Resources->SetExtension((new SplFileInfo($File['name']))->getExtension());
                        }
                    }
                    catch(Exception $error){}

                    $Resources->Save();

                    $Out[] = $Resources->GetUid();
                }
            }

            PrintJson::OperationSuccessful(["files" => $Out]);
        }
        else
            PrintJson::OperationError(FilesIsNull, REQUEST_FAILED);
    }
}