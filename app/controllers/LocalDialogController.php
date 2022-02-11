<?php


namespace controllers;

use classes\Tools;
use models\Authentication;
use models\dialogues\LocalDialog;
use models\Message;
use models\User;
use views\PrintJson;


class LocalDialogController
{
    public function GetIsOnline(LocalDialog $InstagramDialog) : bool
    {
        //Сделать обработку проверки статуса онлайна
        return true;
    }


    public function SendMessage(LocalDialog $Dialog, Message $Message) : ?string
    {
        $Message->SetIsMe(true);
        (new MessageController())->OnMessage(
            $Dialog,
            User::FindById($Dialog->GetToUserId())->GetLogin(),
            null,
            false,
            null,
            $Message
        );

        $Message->SetIsMe(false);
        $Message->Untie();
        $ToDialog = LocalDialog::FindByUserIdAndToUserId($Dialog->GetToUserId(), $Dialog->GetUserId(), false);
        if(empty($ToDialog))
        {
            Authentication::SetAuthUser(User::FindById($Dialog->GetToUserId()), SYSTEM_SESSION);
            $ToDialog = new LocalDialog();
            $ToDialog->SetUserId($Dialog->GetToUserId());
            $ToDialog->SetToUserId($Dialog->GetUserId());
            $ToDialog->SetName(Authentication::GetAuthUser()->GetLogin());
        }
        (new MessageController())->OnMessage(
            $ToDialog,
            Authentication::GetAuthUser()->GetLogin(),
            null,
            false,
            null,
            $Message
        );
        return $Message->GetUid();
    }


    public function ActionCreateSupportDialog(array $Parameters)
    {
        $SupportUser = User::FindByUserType(User::USER_TYPE_SUPPORT);
        if(!empty($SupportUser))
        {
            $User = Authentication::GetAuthUser();
            $Dialog = LocalDialog::FindByUserIdAndToUserId($User->GetId(), $SupportUser->GetId());
            if(empty($Dialog))
            {
                $Dialog = new LocalDialog();
                $Dialog->SetUserId($User->GetId());
                $Dialog->SetToUserId($SupportUser->GetId());
                $Dialog->SetName($SupportUser->GetLogin());
                $Dialog->Save();

                PrintJson::OperationSuccessful();
            }
            else
                PrintJson::OperationError(OperationError, REQUEST_FAILED);
        }
        else
            PrintJson::OperationError(OperationError, REQUEST_FAILED);
    }
}