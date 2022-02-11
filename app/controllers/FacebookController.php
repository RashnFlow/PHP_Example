<?php


namespace controllers;

use classes\Log;
use classes\Logger;
use classes\Validator;
use models\Authentication;
use models\Facebook;
use sdk\php\facebook\FacebookSDK;
use Throwable;
use views\PrintJson;


class FacebookController
{
    public function ActionCreate(array $Parameters)
    {
        if(Validator::IsValid($Parameters["Post"], [
            ["Key" => "access_token"]
        ]))
        {
            $FacebookSDK = new FacebookSDK();

            try
            {
                $Facebook = new Facebook();
                $Facebook->AccessToken = $Parameters["Post"]["access_token"];

                $Response = $FacebookSDK->get('/me?fields=id,name,picture.width(250)', $Facebook->AccessToken);

                $Facebook->RefreshToken     = $FacebookSDK->getOAuth2Client()->getLongLivedAccessToken($Facebook->AccessToken)->getValue();
                $Facebook->FacebookUserId   = (int)$Response->getDecodedBody()['id'];
                $Facebook->FullName         = $Response->getDecodedBody()['name'];
                $Facebook->PictureUrl       = $Response->getDecodedBody()['picture']['data']['url'];
                $Facebook->UserId           = Authentication::GetAuthUser()->GetId();
                $Facebook->IsActive         = true;

                $Response = $FacebookSDK->get('me/accounts?fields=access_token,id', $Facebook->AccessToken)->getDecodedBody();
                $Facebook->Pages = array_combine(array_column($Response["data"], 'id'), array_column($Response["data"], 'access_token'));

                if(!empty(Facebook::FindByFacebookUserId($Facebook->FacebookUserId)))
                {
                    PrintJson::OperationError(FacebookIsExists, 400);
                    return;
                }

                $Facebook->Save();
                PrintJson::OperationSuccessful(Validator::ArrayKeyPascalCaseToSnakeCase($Facebook->ToArray(['FacebookId'])));
            }
            catch(Throwable $error)
            {
                Logger::Log(Log::TYPE_FATAL_ERROR, "Ошибка при создании Facebook аккаунта", (string)$error);
                PrintJson::OperationError(FacebookAuthError, 400);
            }
        }
    }


    public function ActionGetAllModels(array $Parameters)
    {
        $Out = [];
        try
        {
            foreach(Facebook::FindAllByUserId() as $Facebook)
                $Out[] = self::FacebookToArray($Facebook);
        }
        catch(Throwable $error) {}
        PrintJson::OperationSuccessful(["facebooks" => $Out]);
    }


    static public function FacebookToArray(Facebook $Facebook) : array
    {
        return Validator::ArrayKeyPascalCaseToSnakeCase($Facebook->ToArray([
            'FacebookId',
            'UserId',
            'FullName',
            'PictureUrl'
        ]));
    }
}