<?php


namespace controllers;

use classes\Tools;
use classes\Validator;
use Exception;
use factories\UserFactory;
use models\Affiliate;
use models\AffiliatePartners;
use models\Authentication;
use models\Operation;
use models\Purse;
use models\User;
use models\UserTariff;
use views\PrintJson;

class AffiliateController
{
    static public function CheckAccess(?string $Token) : bool
    {
        if(md5($Token) == AFFILIATE_API_KEY)
            return true;
        else
            throw new Exception("Access is denied", ACCESS_DENIED);
    }

    public function ActionCreateUrl(array $Parameters)
    {
        if(Validator::IsValid($Parameters["Post"], [
            ["Key" => "url_name"]
        ]))
        {
            $UserId = Authentication::GetAuthUser()->GetId();
            if(empty($Affiliate = Affiliate::FindByUrlNameAndUserId($Parameters["Post"]["url_name"], $UserId)))
            {
                $Affiliate = new Affiliate();
                if(empty($Purse = Purse::FindByUserId($UserId)))
                {
                    $Purse = new Purse();
                    $Purse->UserId = $UserId;
                    $Purse->Balance = 0;
                    $Purse->Save();
                }
                if(empty($Partner = AffiliatePartners::FindByUserId($UserId)))
                {
                    $Partner = new AffiliatePartners();
                    $Partner->UserId = $UserId;
                    $Partner->PartnerName = "Партнёр";
                    $Partner->PartnerSale = 15;
                    $Partner->Save();
                }
                $Affiliate->UserId = $UserId;
                $Affiliate->UrlName = $Parameters["Post"]["url_name"];
                $Affiliate->Url = Tools::GenerateStringBySeed(15, Tools::ConvertStringToSeed($Affiliate->UrlName . "_" . $Affiliate->UserId));
                $Affiliate->Save();
                PrintJson::OperationSuccessful();
            }
            else
                PrintJson::OperationError(UrlNameAlreadyExist, IS_EXISTS);
        }
    }

    public function ActionGetAllAffiliate(array $Parameters)
    {
        if(!empty($Affiliates = Affiliate::FindAllByUserId(Authentication::GetAuthUser()->GetId())))
        {
            $Out = [];
            foreach ($Affiliates as $Affiliate)
            {
                $Out["affiliate"][] = [
                    "affiliate_id"          => $Affiliate->AffiliateId,
                    "url_name"              => $Affiliate->UrlName,
                    "url"                   => $Affiliate->Url,
                    "clicks"                => $Affiliate->Clicks
                ];
            }
            PrintJson::OperationSuccessful($Out);
        }
        else
            PrintJson::OperationError(UrlNotFound, NOT_FOUND);
    }


    public function ActionGetAffiliateReferral(array $Parameters)
    {
        if(Validator::IsValid($Parameters["Get"], [
            ["Key" => "affiliate_id"]
        ]))
        {
            if(!empty($Affiliate = Affiliate::FindById($Parameters["Get"]["affiliate_id"])))
            {
                $Out["affiliate"][] = [
                    "affiliate_id"          => $Affiliate->AffiliateId,
                    "url_name"              => $Affiliate->UrlName,
                    "url"                   => $Affiliate->Url,
                    "clicks"                => $Affiliate->Clicks
                ];
                PrintJson::OperationSuccessful($Out);
            }
            else
                PrintJson::OperationError(UrlNotFound, NOT_FOUND);
        }
    }

    public function ActionGetAffiliateStatuses(array $Parameters)
    {
        if(!empty($AffiliatePartners = AffiliatePartners::FindByUserId(Authentication::GetAuthUser()->GetId())))
        {
            $Out = [];
            $Affiliates = Affiliate::FindAllByUserId(Authentication::GetAuthUser()->GetId());
            foreach($Affiliates as $Affiliate)
            {
                $RegistrationUsersId = $AffiliatePartners->RegistrationUsersId;
                $RegistrationUsersCount = 0;
                foreach($RegistrationUsersId as $UserId)
                {
                    if(!empty(User::FindById($UserId["user_id"])))
                        $RegistrationUsersCount++;
                }
            }

            $OperationSum = 0;
            if(!empty($Operations = Operation::FindAllByUserId(Authentication::GetAuthUser()->GetId())))
            {
                foreach($Operations as $Operation)
                    $OperationSum += $Operation->Sum;
            }

            $Out["partner"] = [
                "partner_name"              => $AffiliatePartners->PartnerName,
                "partner_sale"              => $AffiliatePartners->PartnerSale,
                "referrals"                 => $RegistrationUsersCount,
                "purse"                     => Purse::FindByUserId(Authentication::GetAuthUser()->GetId())->Balance,
                "operations"                => $OperationSum
            ];
            
            PrintJson::OperationSuccessful($Out);
        }
        else
        {
            $Out["partner"] = [
                "partner_name"              => "Клиент",
                "partner_sale"              => 0,
                "referrals"                 => 0,
                "purse"                     => 0,
                "operations"                => 0
            ];
            
            PrintJson::OperationSuccessful($Out);
        }
    }


    public function ActionCountClicks(array $Parameters)
    {
        if(Validator::IsValid($Parameters["Post"], [
            ["Key" => "referral"]
        ]))
        {
            Authentication::SetAuthUser(UserFactory::GetSystemUser(), SYSTEM_SESSION);

            if(!empty($Affiliate = Affiliate::FindByUrl($Parameters["Post"]["referral"])))
            {
                $Affiliate->Clicks += 1;
                $Affiliate->Save();
                PrintJson::OperationSuccessful();
            }
            else
                PrintJson::OperationError(UrlNotFound, NOT_FOUND);
        }
    }


    public function ActionCreateOperationOnPay(array $Parameters)
    {
        if(Validator::IsValid($Parameters["Post"], [
            ["Key" => "card_number", "Type" => "string"],
            ["Key" => "sum", "Type" => "int"],
        ]))
        {
            if(Validator::CheckCardNumber($Parameters["Post"]["card_number"]))
            {
                if(!empty($Purse = Purse::FindByUserId(Authentication::GetAuthUser()->GetId())))
                {
                    if($Purse->Balance < $Parameters["Post"]["sum"] || $Parameters["Post"]["sum"] < 10000)
                    {
                        PrintJson::OperationError(InsufficientFunds, REQUEST_FAILED);
                        return;
                    }
                    $Purse->Balance -= $Parameters["Post"]["sum"];
                    $Purse->Save();
                    $Operation = new Operation();
                    $Operation->Sum = $Parameters["Post"]["sum"];
                    $Operation->UserId = Authentication::GetAuthUser()->GetId();
                    $Operation->Data = $Parameters["Post"]["card_number"];
                    $Operation->Save();
                    PrintJson::OperationSuccessful();
                }
                else
                    PrintJson::OperationError(PurseNotFound, NOT_FOUND);
            }
            else
                PrintJson::OperationError(CardNumberInvalid, REQUEST_FAILED);
        }
    }


    public function ActionGetAllOperation(array $Parameters)
    {
        if(!empty($Operations = Operation::FindAllByUserId(Authentication::GetAuthUser()->GetId())))
        {
            $Out = [];
            foreach ($Operations as $Operation)
            {
                $Out["operation"][] = [
                    "operation_id"     => $Operation->OperationId,
                    "sum"              => $Operation->Sum,
                    "created_at"       => $Operation->CreatedAt,
                    "pay_date"         => $Operation->PayDate,
                    "is_paid"          => $Operation->IsPaid,
                    "card_number"      => $Operation->Data
                ];
            }
            PrintJson::OperationSuccessful($Out);
        }
        else
            PrintJson::OperationError(OperationNotFound, NOT_FOUND);
    }


    public function ActionUpdateAffiliate(array $Parameters)
    {
        if(Validator::IsValid($Parameters["Post"], [
            ["Key" => "affiliate_id"],
            ["Key" => "url_name"],
        ]))
        {
            if(empty($Affiliate = Affiliate::FindByUrlNameAndUserId($Parameters["Post"]["url_name"], Authentication::GetAuthUser()->GetId())))
            {
                if(!empty($Affiliate = Affiliate::FindById($Parameters["Post"]["affiliate_id"])))
                {
                    $Affiliate->UrlName = $Parameters["Post"]["url_name"];
                    $Affiliate->Save();
                    PrintJson::OperationSuccessful();
                }
                else
                    PrintJson::OperationError(UrlNotFound, NOT_FOUND);
            }
            else
                PrintJson::OperationError(UrlNameAlreadyExist, IS_EXISTS);
        }
    }

    public function ActionDeleteAffiliate(array $Parameters)
    {
        if(Validator::IsValid($Parameters["Post"], [
            ["Key" => "affiliate_id"]
        ]))
        {
            if(!empty($Affiliate = Affiliate::FindById($Parameters["Post"]["affiliate_id"])))
            {
                $Affiliate->Delete();
                PrintJson::OperationSuccessful();
            }
            else
                PrintJson::OperationError(UrlNotFound, NOT_FOUND);
        }
    }
}