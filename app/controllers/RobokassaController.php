<?php


namespace controllers;

use classes\Validator;
use Exception;
use factories\UserFactory;
use models\AffiliatePartners;
use models\Authentication;
use models\Authorization;
use models\Instagram;
use models\InstagramTariff;
use models\Purse;
use models\SalesTariff;
use models\User;
use models\UserTariff;
use models\Whatsapp;
use models\WhatsAppTariff;
use sdk\php\robokassa\Robokassa;
use views\PrintJson;
use views\View;

class RobokassaController
{
    static public function CheckAccess(?string $Token) : bool
    {
        if(md5($Token) == ROBOKASSA_API_KEY)
            return true;
        else
            throw new Exception("Access is denied", ACCESS_DENIED);
    }


    public function ActionCreateLink(array $Parameters)
    {
        if(Validator::IsValid($Parameters["Post"], [
            ["Key" => "user_tariff_id", "Type" => "int", "IsNull" => true],
            ["Key" => "whatsapp_tariff_id", "Type" => "int", "IsNull" => true],
            ["Key" => "instagram_tariff_id", "Type" => "int", "IsNull" => true],
        ]))
        {
            if(!empty($Parameters["Post"]["user_tariff_id"]) && !empty($UserTariff = UserTariff::FindById($Parameters["Post"]["user_tariff_id"])))
            {
                $SignatureValue = md5(ROBOKASSA_LOGIN .":$UserTariff->Price:$UserTariff->UserTariffId:" . ROBOKASSA_PASSWORD_ONE . ":shp_UserTariff=$UserTariff->UserTariffId");
                $Link = Robokassa::PayMentUrl($UserTariff->Price, $UserTariff->UserTariffId, $SignatureValue, "shp_UserTariff", $UserTariff->UserTariffId);
                PrintJson::OperationSuccessful(["link" => $Link]);
            }
            else if(!empty($Parameters["Post"]["whatsapp_tariff_id"]) && !empty($WhatsAppTariff = WhatsAppTariff::FindById($Parameters["Post"]["whatsapp_tariff_id"])))
            {
                $SignatureValue = md5(ROBOKASSA_LOGIN .":$WhatsAppTariff->Price:$WhatsAppTariff->WhatsAppTariffId:" . ROBOKASSA_PASSWORD_ONE . ":shp_WhatsAppTariff=$WhatsAppTariff->WhatsAppTariffId");
                $Link = Robokassa::PayMentUrl($WhatsAppTariff->Price, $WhatsAppTariff->WhatsAppTariffId, $SignatureValue, "shp_WhatsAppTariff", $WhatsAppTariff->WhatsAppTariffId);
                PrintJson::OperationSuccessful(["link" => $Link]);
            }
            else if(!empty($Parameters["Post"]["instagram_tariff_id"]) && !empty($InstagramTariff = InstagramTariff::FindById($Parameters["Post"]["instagram_tariff_id"])))
            {
                $SignatureValue = md5(ROBOKASSA_LOGIN .":$InstagramTariff->Price:$InstagramTariff->InstagramTariffId:" . ROBOKASSA_PASSWORD_ONE . ":shp_InstagramTariff=$InstagramTariff->InstagramTariffId");
                $Link = Robokassa::PayMentUrl($InstagramTariff->Price, $InstagramTariff->InstagramTariffId, $SignatureValue, "shp_InstagramTariff", $InstagramTariff->InstagramTariffId);
                PrintJson::OperationSuccessful(["link" => $Link]);
            }
            else
                PrintJson::OperationError(UserTariffNotFound, NOT_FOUND);
        }
    }

    public function ActionResultUrl(array $Parameters)
    {
        Authentication::SetAuthUser(UserFactory::GetSystemUser(), SYSTEM_SESSION);

        if(!empty($OutSum = $Parameters["Post"]["OutSum"]))
        {
            $InvId = $Parameters["Post"]["InvId"];
            $UserId = 0;
            if(!empty($Parameters["Post"]["shp_UserTariff"]))
            {
                if(md5("$OutSum:$InvId:" . ROBOKASSA_PASSWORD_ONE . ":shp_UserTariff=$InvId") == $Parameters["Post"]["SignatureValue"])
                {
                    $UserTariff = UserTariff::FindById($Parameters["Post"]["shp_UserTariff"]);
                    $UserId = $UserTariff->UserId;
                    $IsNew = true;
                    if(!empty($WhatsAppTariff = WhatsAppTariff::FindById($UserTariff->WhatsAppTariffId)))
                    {
                        $WhatsAppTariff->PayDate = time();
                        if(!empty($WhatsAppTariff->EndDate) && !$WhatsAppTariff->IsCheking)
                        {
                            $WhatsAppTariff->EndDate = strtotime("+" . SalesTariff::FindById($WhatsAppTariff->SaleId)->Month . " month", $WhatsAppTariff->EndDate);
                            $IsNew = false;
                        }
                        else if(!empty($WhatsAppTariff->EndDate) && $WhatsAppTariff->IsCheking)
                            $WhatsAppTariff->EndDate = strtotime("+" . SalesTariff::FindById($WhatsAppTariff->SaleId)->Month . " month", time());
                        else
                            $WhatsAppTariff->EndDate = strtotime("+" . SalesTariff::FindById($WhatsAppTariff->SaleId)->Month . " month");
                        $WhatsAppTariff->OldPrice += $WhatsAppTariff->Price;
                        $WhatsAppTariff->Price = 0;
                        $WhatsAppTariff->Status = "success";
                        $WhatsAppTariff->IsCheking = false;
                        $WhatsAppTariff->Save();
                    }
                    if(!empty($InstagramTariff = InstagramTariff::FindById($UserTariff->InstagramTariffId)))
                    {
                        $InstagramTariff->PayDate = time();
                        if(!empty($InstagramTariff->EndDate) && !$InstagramTariff->IsCheking)
                        {
                            $InstagramTariff->EndDate = strtotime("+" . SalesTariff::FindById($InstagramTariff->SaleId)->Month . " month", $InstagramTariff->EndDate);
                            $IsNew = false;
                        }
                        else if(!empty($InstagramTariff->EndDate) && $InstagramTariff->IsCheking)
                            $InstagramTariff->EndDate = strtotime("+" . SalesTariff::FindById($InstagramTariff->SaleId)->Month . " month", time());
                        else
                            $InstagramTariff->EndDate = strtotime("+" . SalesTariff::FindById($InstagramTariff->SaleId)->Month . " month");
                        $InstagramTariff->OldPrice += $InstagramTariff->Price;
                        $InstagramTariff->Price = 0;
                        $InstagramTariff->Status = "success";
                        $InstagramTariff->IsCheking = false;
                        $InstagramTariff->Save();
                    }
                    if($IsNew)
                        $this->CountPermissions($UserTariff->WhatsAppTariffId, $UserTariff->InstagramTariffId);
                }
            }
            else if(!empty($Parameters["Post"]["shp_WhatsAppTariff"]))
            {
                if(md5("$OutSum:$InvId:" . ROBOKASSA_PASSWORD_ONE . ":shp_WhatsAppTariff=$InvId") == $Parameters["Post"]["SignatureValue"])
                {
                    $IsNew = true;
                    $WhatsAppTariff = WhatsAppTariff::FindById($Parameters["Post"]["shp_WhatsAppTariff"]);
                    $UserId = $WhatsAppTariff->UserId;

                    $WhatsAppTariff->PayDate = time();
                    if(!empty($WhatsAppTariff->EndDate) && !$WhatsAppTariff->IsCheking)
                    {
                        $WhatsAppTariff->EndDate = strtotime("+" . SalesTariff::FindById($WhatsAppTariff->SaleId)->Month . " month", $WhatsAppTariff->EndDate);
                        $IsNew = false;
                    }
                    else if(!empty($WhatsAppTariff->EndDate) && $WhatsAppTariff->IsCheking)
                        $WhatsAppTariff->EndDate = strtotime("+" . SalesTariff::FindById($WhatsAppTariff->SaleId)->Month . " month", time());
                    else
                        $WhatsAppTariff->EndDate = strtotime("+" . SalesTariff::FindById($WhatsAppTariff->SaleId)->Month . " month");
                    $WhatsAppTariff->OldPrice += $WhatsAppTariff->Price;
                    $WhatsAppTariff->Price = 0;
                    $WhatsAppTariff->Status = "success";
                    $WhatsAppTariff->IsCheking = false;
                    $WhatsAppTariff->Save();
                    if($IsNew)
                        $this->CountPermissions($WhatsAppTariff->WhatsAppTariffId, 0);
                }
            }
            else if(!empty($Parameters["Post"]["shp_InstagramTariff"]))
            {
                if(md5("$OutSum:$InvId:" . ROBOKASSA_PASSWORD_ONE . ":shp_InstagramTariff=$InvId") == $Parameters["Post"]["SignatureValue"])
                {
                    $IsNew = true;
                    $InstagramTariff = InstagramTariff::FindById($Parameters["Post"]["shp_InstagramTariff"]);
                    $UserId = $InstagramTariff->UserId;
                    
                    $InstagramTariff->PayDate = time();
                    if(!empty($InstagramTariff->EndDate)  && !$InstagramTariff->IsCheking)
                    {
                        $InstagramTariff->EndDate = strtotime("+" . SalesTariff::FindById($InstagramTariff->SaleId)->Month . " month", $InstagramTariff->EndDate);
                        $IsNew = false;
                    }
                    else if(!empty($InstagramTariff->EndDate) && $InstagramTariff->IsCheking)
                        $InstagramTariff->EndDate = strtotime("+" . SalesTariff::FindById($InstagramTariff->SaleId)->Month . " month", time());
                    else
                        $InstagramTariff->EndDate = strtotime("+" . SalesTariff::FindById($InstagramTariff->SaleId)->Month . " month");
                    $InstagramTariff->OldPrice += $InstagramTariff->Price;
                    $InstagramTariff->Price = 0;
                    $InstagramTariff->Status = "success";
                    $InstagramTariff->IsCheking = false;
                    $InstagramTariff->Save();
                    if($IsNew)
                        $this->CountPermissions(0, $InstagramTariff->InstagramTariffId);
                }
            }
            else
                PrintJson::OperationError(PaymentNotFound, REQUEST_FAILED);

            $AllRegistrationUsersId = AffiliatePartners::FindAll();
            
            foreach($AllRegistrationUsersId as $RegistrationUsersId)
            {
                foreach($RegistrationUsersId->RegistrationUsersId as $key => $id)
                {
                    if($UserId == $id["user_id"])
                    {
                        $Purse = Purse::FindByUserId($RegistrationUsersId->UserId);
                        $Purse->Balance += $OutSum * ($RegistrationUsersId->PartnerSale / 100);
                        $Purse->Save();
                    }
                        
                }
            }
            View::Print("Redirect", [
                "Url" => DOMAIN_FRONT_URL . "/settings/finance/history"
            ]);
        }
    }


    private function CountPermissions($WhatsAppTariffId = null, $InstagramTariffId = null)
    {
        $Permissions = [];
        if(!empty($WhatsAppTariffId) && !empty($InstagramTariffId))
        {
            $WhatsAppTariff = WhatsAppTariff::FindById($WhatsAppTariffId);
            $InstagramTariff = InstagramTariff::FindById($InstagramTariffId);
            $User = User::FindById($WhatsAppTariff->UserId);
            if(empty($User->GetPermissions()))
                $User->SetPermissions([array_merge($User->GetPermissions(), $WhatsAppTariff->Access, $InstagramTariff->Access)]);
            else
            {
                $Permissions["GetWhatsapp"] = $WhatsAppTariff->Access["GetWhatsapp"] + $User->GetPermissionVal("GetWhatsapp");
                $Permissions["SetWhatsapp"] = $WhatsAppTariff->Access["SetWhatsapp"] + $User->GetPermissionVal("SetWhatsapp");
                $Permissions["GetInstagram"] = $InstagramTariff->Access["GetInstagram"] + $User->GetPermissionVal("GetInstagram");
                $Permissions["SetInstagram"] = $InstagramTariff->Access["SetInstagram"] + $User->GetPermissionVal("SetInstagram");
                $User->SetPermissions([$Permissions]);
                if(!empty($WhatsApps = Whatsapp::FindAllBannedWhatsAppByUserId($WhatsAppTariff->UserId)))
                    $this->UnblockChannels($WhatsApps, $WhatsAppTariff->Access["GetWhatsapp"]);
                if(!empty($Instagrams = Instagram::FindAllBannedInstagramByUserId($InstagramTariff->UserId)))
                    $this->UnblockChannels($Instagrams, $InstagramTariff->Access["GetInstagram"]);
            }
        }
        else if(!empty($WhatsAppTariffId))
        {
            $WhatsAppTariff = WhatsAppTariff::FindById($WhatsAppTariffId);
            $User = User::FindById($WhatsAppTariff->UserId);
            if(empty($User->GetPermissions()))
                $User->SetPermissions([array_merge($User->GetPermissions(), $WhatsAppTariff->Access)]);
            else
            {
                $Permissions["GetWhatsapp"] = $WhatsAppTariff->Access["GetWhatsapp"] + $User->GetPermissionVal("GetWhatsapp");
                $Permissions["SetWhatsapp"] = $WhatsAppTariff->Access["SetWhatsapp"] + $User->GetPermissionVal("SetWhatsapp");
                $Permissions["GetInstagram"] += $User->GetPermissionVal("GetInstagram");
                $Permissions["SetInstagram"] += $User->GetPermissionVal("SetInstagram");
                $User->SetPermissions([$Permissions]);
                if(!empty($WhatsApps = Whatsapp::FindAllBannedWhatsAppByUserId($WhatsAppTariff->UserId)))
                    $this->UnblockChannels($WhatsApps, $WhatsAppTariff->Access["GetWhatsapp"]);
            }
        }
        else if(!empty($InstagramTariffId))
        {
            $InstagramTariff = InstagramTariff::FindById($InstagramTariffId);
            $User = User::FindById($InstagramTariff->UserId);
            if(empty($User->GetPermissions()))
                $User->SetPermissions([array_merge($User->GetPermissions(), $InstagramTariff->Access)]);
            else
            {
                $Permissions["GetWhatsapp"] += $User->GetPermissionVal("GetWhatsapp");
                $Permissions["SetWhatsapp"] += $User->GetPermissionVal("SetWhatsapp");
                $Permissions["GetInstagram"] = $InstagramTariff->Access["GetInstagram"] + $User->GetPermissionVal("GetInstagram");
                $Permissions["SetInstagram"] = $InstagramTariff->Access["SetInstagram"] + $User->GetPermissionVal("SetInstagram");
                $User->SetPermissions([$Permissions]);
                if(!empty($Instagrams = Instagram::FindAllBannedInstagramByUserId($InstagramTariff->UserId)))
                    $this->UnblockChannels($Instagrams, $InstagramTariff->Access["GetInstagram"]);
            }
        }
        $User->Save();
    }


    private function UnblockChannels(array $BannedChannels, int $Counter)
    {
        foreach($BannedChannels as $BannedChannel)
        {
            if($Counter == 0)
                break;
            $BannedChannel->SetIsBanned(false);
            $BannedChannel->Save();
            $Counter--;
        }
    }
}