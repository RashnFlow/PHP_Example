<?php

namespace controllers;


use classes\Validator;
use Exception;
use models\MegaplanIntegration;
use models\Authentication;
use models\dialogues\Dialog;
use models\dialogues\InstagramDialog;
use models\Message;
use models\User;
use models\dialogues\WhatsappDialog;
use sdk\php\amocrm\AmoCurl;
use views\PrintJson;
use views\View;

class MegaplanController
{
    static public function CheckAccess(?string $Token) : bool
    {
        if(md5($Token) == AMOCRM_API_KEY)
            return true;
        else
            throw new Exception("Access is denied", ACCESS_DENIED);
    }

        public function ActionDeleteIntegration(array $Parameters)
    {
        if(!empty($Find = MegaplanIntegration::FindByUserId()))
        {
            $Find->Delete();
            PrintJson::OperationSuccessful();
        }
        else
            PrintJson::OperationError(AmoCRMError, NOT_FOUND);
    }

}
?>