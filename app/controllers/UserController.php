<?php


namespace controllers;


use classes\Image;
use classes\StaticResources;
use classes\Validator;
use factories\UserFactory;
use models\Affiliate;
use models\AffiliatePartners;
use models\ApiToken;
use models\Authentication;
use models\DynamicResource;
use models\Email;
use models\Folder;
use models\InstagramTariff;
use models\SalesTariff;
use models\Task;
use models\User;
use models\UserTariff;
use models\WhatsAppTariff;
use views\PrintJson;
use views\View;


class UserController
{
    public function ActionGetUser(array $Parameters)
    {
        $User = Authentication::GetAuthUser();
        if(!empty($User))
        {
            PrintJson::OperationSuccessful(["user" =>
                [
                    "login"         => $User->GetLogin(),
                    "user_id"       => $User->GetId(),
                    "user_type"     => $User->GetUserType(),
                    "is_admin"      => $User->GetUserType() == User::USER_TYPE_ADMIN,
                    "is_customer"   => $User->GetUserType() == User::USER_TYPE_CUSTOMER,
                    "rules"         => $User->GetRules(),
                    "avatar"        => DOMAIN_API_URL . "/get/user/avatar/" . $User->GetId()
                ]
            ]);
        }
        else
            PrintJson::OperationError(UserNotFound, NOT_FOUND);
    }


    public function ActionUserRegistration(array $Parameters)
    {
        if(Validator::IsValid($Parameters["Post"], [
            ["Key" => "name", "StrMax" => 250],
            ["Key" => "email", "StrMax" => 250],
            ["Key" => "phone", "StrMax" => 40],
            ["Key" => "referral", "IsNull" => true],
        ]))
        {
            $Email = filter_var($Parameters["Post"]["email"], FILTER_VALIDATE_EMAIL);
            if($Email)
            {
                if(empty(User::FindByEmail($Email)))
                {
                    $Phone = Validator::NormalizePhone($Parameters["Post"]["phone"]);

                    if(empty(User::FindByLogin($Phone)))
                    {
                        Authentication::SetAuthUser(UserFactory::GetSystemUser(), SYSTEM_SESSION);

                        $User = new User();
                        $User->SetLogin($Phone);
                        $User->SetPhone($Phone);
                        $User->SetEmail($Email);
                        $User->SetName($Parameters["Post"]["name"]);
                        $User->SetUserType(User::USER_TYPE_CUSTOMER);
                        $User->Save();

                        $User = User::FindByLogin($Phone);
                        Authentication::SetAuthUser($User, SYSTEM_SESSION);

                        $DefaultFolder = new Folder();
                        $DefaultFolder->SetUserId($User->GetId());
                        $DefaultFolder->SetIsDefault(true);
                        $DefaultFolder->SetName("Основное");
                        $DefaultFolder->Save();

                        //Отправка письма с подтверждением
                        $EmailObj = Email::FindByEmail($Email);
                        if(empty($EmailObj))
                        {
                            $EmailObj = new Email();
                            $EmailObj->SetEmail($Email);
                            $EmailObj->Save();
                        }

                        $Task = new Task();
                        $Task->SetType("SendMail");
                        $Task->SetData([
                            "Emails" => [$Email],
                            "Subject" => "Подтвердите почту",
                            "Mail" => [
                                "Template" => "emails/EmailConfirmation",
                                "Parameters" => [
                                    "EmailConfirmationUrl" => DOMAIN_API_URL . "/email/confirmation/" . $EmailObj->GetUid()
                                ]
                            ]
                        ]);
                        $Task->Save();
                        if(!empty($Parameters["Post"]["referral"]) && !empty($Affiliate = Affiliate::FindByUrl($Parameters["Post"]["referral"])))
                        {
                            $AffiliatePartner = AffiliatePartners::FindByUserId($Affiliate->UserId);
                            $AffiliatePartner->RegistrationUsersId[] = ["user_id" => $User->GetId()];
                            $AffiliatePartner->Save();
                        }

                        $UserTariff = new UserTariff();
                        $WhatsAppTariff = new WhatsAppTariff();
                        $InstagramTariff = new InstagramTariff();
                        $Sales = SalesTariff::FindById(1);
                        $EndDate = strtotime("+7 days");

                        $WhatsAppTariff->UserId = $User->GetId();
                        $WhatsAppTariff->SaleId = $Sales->SaleId;
                        $WhatsAppTariff->OldPrice = 0;
                        $WhatsAppTariff->Price = 0;
                        $WhatsAppTariff->AllPrice = 0;
                        $WhatsAppTariff->Status = "success";
                        $WhatsAppTariff->EndDate = $EndDate;
                        $WhatsAppTariff->PayDate = time();
                        $WhatsAppTariff->Access = [
                            "GetWhatsapp" => 1,
                            "SetWhatsapp" => 1,
                        ];
                        $WhatsAppTariff->Save();

                        $InstagramTariff->UserId = $User->GetId();
                        $InstagramTariff->SaleId = $Sales->SaleId;
                        $InstagramTariff->OldPrice = 0;
                        $InstagramTariff->Price = 0;
                        $InstagramTariff->AllPrice = 0;
                        $InstagramTariff->Status = "success";
                        $InstagramTariff->EndDate = $EndDate;
                        $InstagramTariff->PayDate = time();
                        $InstagramTariff->Access = [
                            "GetInstagram" => 1,
                            "SetInstagram" => 1,
                        ];
                        $InstagramTariff->Save();

                        $UserTariff->UserId = $User->GetId();
                        $UserTariff->WhatsAppTariffId = $WhatsAppTariff->WhatsAppTariffId;
                        $UserTariff->InstagramTariffId = $InstagramTariff->InstagramTariffId;
                        $UserTariff->SaleId = $Sales->SaleId;
                        $UserTariff->Price = 0;
                        $UserTariff->Save();

                        $User->SetPermissions([array_merge($User->GetPermissions(), $WhatsAppTariff->Access, $InstagramTariff->Access)]);
                        $User->Save();

                        PrintJson::OperationSuccessful();
                    }
                    else
                        PrintJson::OperationError(UserIsExist, IS_EXISTS);
                }
                else
                    PrintJson::OperationError(UserEmailIsExist, IS_EXISTS);
            }
            else
                PrintJson::OperationError(UserInvalidEmail, REQUEST_FAILED);
        }
    }

    
    public function ActionGetUserAvatar(array $Parameters)
    {
        $User = Authentication::GetAuthUser();
        if(!empty($User))
        {
            $Avatar = $User->GetAvatar();
            View::Print("Image", ["Type" => "jpeg", "Image" => empty($Avatar) ? StaticResources::GetImage(USER_UNKNOWN_AVATAR)->GetResource() : $Avatar]);
        }
        else
            PrintJson::OperationError(UserNotFound, NOT_FOUND);
    }


    public function ActionSetUserAvatar(array $Parameters)
    {
        if(Validator::IsValid($Parameters["Post"], [
            ["Key" => "img_uid"]
        ]))
        {
            $User = Authentication::GetAuthUser();
            if(!empty($User))
            {
                $DynamicResource = DynamicResource::FindByUidAndUserId($Parameters["Post"]["img_uid"]);

                if(!empty($DynamicResource))
                {
                    $Image = new Image($DynamicResource->GetResource());
                    $Image->Resize(300, 300);
                    $User->SetAvatar($Image->GetImageJpeg());
                    $User->Save();

                    $DynamicResource->Delete();
                    PrintJson::OperationSuccessful();
                }
                else
                    PrintJson::OperationError(ResourceNotFound, REQUEST_FAILED);
            }
            else
                PrintJson::OperationError(UserNotFound, NOT_FOUND);
        }
    }


    public function ActionSetUserPassword(array $Parameters)
    {
        if(Validator::IsValid($Parameters["Post"], [
            ["Key" => "old_password"],
            ["Key" => "new_password", "StrMin" => 8, "StrMax" => 30]
        ]))
        {
            $User = Authentication::GetAuthUser();
            if(!empty($User))
            {
                if(password_verify($Parameters["Post"]["old_password"], $User->GetPassword()))
                {
                    $User->SetPassword($Parameters["Post"]["new_password"]);
                    $User->Save();

                    //Очистка токенов
                    foreach(ApiToken::FindAllByUserId() as $ApiToken)
                        if($ApiToken->GetToken() != $User->GetSession())
                            $ApiToken->Delete();

                    PrintJson::OperationSuccessful();
                }
                else
                    PrintJson::OperationError(UserPassUpdateError, REQUEST_FAILED);
            }
            else
                PrintJson::OperationError(UserNotFound, NOT_FOUND);
        }
    }


    public function ActionGrantRights(array $Parameters)
    {
        if(Validator::IsValid($Parameters["Post"], [
            ["Key" => "rule", "Type" => "string"]
        ]))
        {
            if(!empty($User = Authentication::GetAuthUser()))
            {
                $User->SetRulesVal($Parameters["Post"]["rule"], true);
                $User->Save();
                PrintJson::OperationSuccessful();
            }
            else
                PrintJson::OperationError(UserNotFound, NOT_FOUND);
        }
    }
}