<?php


namespace controllers;

use classes\Headers;
use classes\Validator;
use Exception;
use models\ApiToken;
use models\Authentication;
use views\PrintJson;


class AuthController
{
    public function ActionGetUserToken(array $Parameters)
    {
        if(Validator::IsValid($Parameters["Post"], [
            ["Key" => "login"],
            ["Key" => "password"]
        ]))
        {
            try
            {
                PrintJson::OperationSuccessful(["token" => Authentication::AuthUser($Parameters["Post"]["login"], $Parameters["Post"]["password"])]);
            }
            catch(Exception $error)
            {
                if($error->getCode() == NOT_FOUND || $error->getCode() == REQUEST_FAILED)
                    PrintJson::OperationError(AuthError);
            }
        }
    }


    public function ActionUserLogout(array $Parameters)
    {
        if(empty(Authentication::GetAuthUser()))
        {
            PrintJson::OperationError(NotAuth, REQUEST_FAILED);
            return;
        }

        $Token = Headers::GetAuth();
        ApiToken::FindByToken($Token, false)->Delete();
        PrintJson::OperationSuccessful();
    }
}