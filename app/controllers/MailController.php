<?php


namespace controllers;


use classes\Tools;
use classes\Validator;
use factories\UserFactory;
use models\Authentication;
use models\Email;
use models\Task;
use models\User;
use views\PrintJson;
use views\View;


class MailController
{
    public function ActionSMTPWebhooks(array $Parameters)
    {
        Authentication::SetAuthUser(UserFactory::GetSystemUser(), SYSTEM_SESSION);
        foreach($Parameters["Post"] as $obj)
        {
            $Email = Email::FindByEmail($obj["recipient"]);
            if(empty($Email))
            {
                $Email = new Email();
                $Email->SetEmail($obj["recipient"]);
            }

            switch($obj["event"])
            {
                case 'delivered':
                    $Email->Sent();
                    break;

                case 'undelivered':
                    $Email->FailedSent();
                    break;

                case 'opened':
                    $Email->Opened();
                    break;

                case 'clicked':
                    $Email->Clicked();
                    break;

                case 'spam_by_user':
                    $Email->Spam();
                    break;

                case 'unsubscribed':
                    $Email->SetIsSend(false);
                    break;
            }

            $Email->Save();
        }

        PrintJson::OperationSuccessful();
    }


    public function ActionEmailConfirmation(array $Parameters)
    {
        if(Validator::IsValid($Parameters["Uri"], [
            ["Key" => 0]
        ]))
        {
            Authentication::SetAuthUser(UserFactory::GetSystemUser(), SYSTEM_SESSION);
            $Email = Email::FindByUid($Parameters["Uri"][0]);
            if(!empty($Email))
            {
                $User = User::FindByEmail($Email->GetEmail());
                if(!empty($User))
                {
                    $User->SetIsActive(true);

                    $Password = Tools::GenerateString(15);
                    $User->SetPassword($Password);

                    $User->Save();
                    
                    $Task = new Task();
                    $Task->SetType("SendMail");
                    $Task->SetData([
                        "Emails" => [$User->GetEmail()],
                        "Subject" => "Ваш пароль",
                        "Mail" => [
                            "Template" => "emails/EmailConfirmed",
                            "Parameters" => [
                                "Login" => $User->GetLogin(),
                                "Password" => $Password
                            ]
                        ]
                    ]);
                    $Task->Save();


                    $Token = Authentication::AuthUser($User->GetLogin(), $Password);
                }
            }
        }

        View::Print("Redirect", [
            "Url" => DOMAIN_FRONT_URL . (!empty($Token) ? "/authorization/$Token" : "")
        ]);
    }


    public function ActionEmailUnsubscribe(array $Parameters)
    {
        if(Validator::IsValid($Parameters["Uri"], [
            ["Key" => 0]
        ]))
        {
            Authentication::SetAuthUser(UserFactory::GetSystemUser(), SYSTEM_SESSION);
            $Email = Email::FindByUid($Parameters["Uri"][0]);
            if(!empty($Email))
            {
                $Email->SetIsSend(false);
                $Email->Save();
            }
        }

        View::Print("Redirect", [
            "Url" => DOMAIN_FRONT_URL
        ]);
    }
}