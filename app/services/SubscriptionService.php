<?php


namespace services;

use classes\Log;
use classes\Logger;
use controllers\InstagramSdkController;
use controllers\VenomBotController;
use Exception;
use factories\UserFactory;
use models\Authentication;
use models\Counter;
use models\Instagram;
use models\InstagramTariff;
use models\User;
use models\UserTariff;
use models\Whatsapp;
use models\WhatsAppTariff;
use Throwable;

/*
 * Init
 */
set_time_limit(0);
define("ROOT", str_replace("\\", "/", __DIR__ . "/.."));
require ROOT."/init.php";







/*
 * Service
 */
Authentication::SetAuthUser(UserFactory::GetSystemUser(), SYSTEM_SESSION);

echo "Initialization complete";

while(true)
{
    try
    {
        foreach(UserTariff::FindAll() as $UserTariff)
        {
            if(!empty($UserTariff->WhatsAppTariffId) && !empty($WhatsAppTariff = WhatsAppTariff::FindById($UserTariff->WhatsAppTariffId)))
            {
                $User = User::FindById($WhatsAppTariff->UserId);
                if(empty($User))
                    continue;
                $Permissions = $User->GetPermissions()[0];
                if($WhatsAppTariff->EndDate > 0 && $WhatsAppTariff->EndDate <= time() && !$WhatsAppTariff->IsCheking)
                {
                    $Permissions["GetWhatsapp"] -= $WhatsAppTariff->Access["GetWhatsapp"];
                    $Permissions["SetWhatsapp"] -= $WhatsAppTariff->Access["SetWhatsapp"];
                    $User->SetPermissions([$Permissions]);
                    if($Permissions["GetWhatsapp"] < $Counter = Counter::CountNotBannedWhatsapps($WhatsAppTariff->UserId))
                    {
                        $Venom = new VenomBotController();
                        foreach($Whatsapps = Whatsapp::FindAllByUserId($User->GetId()) as $Whatsapp)
                        {
                            if($Counter == $Permissions["GetWhatsapp"])
                                break;

                            try { $Venom->CloseWhatsapp($Whatsapp); } catch(Exception $error) {}
                            $Whatsapp->SetIsBanned(true);
                            $Whatsapp->Save();
                            $Counter--;
                        }
                    }
                    $WhatsAppTariff->Status = "completed";
                    $WhatsAppTariff->IsCheking = true;
                    $User->Save();
                    $WhatsAppTariff->Save();
                }
            }

            if(!empty($UserTariff->InstagramTariffId) && !empty($InstagramTariff = InstagramTariff::FindById($UserTariff->InstagramTariffId)))
            {
                $User = User::FindById($InstagramTariff->UserId);
                if(empty($User))
                    continue;
                $Permissions = $User->GetPermissions()[0];
                if($InstagramTariff->EndDate > 0 && $InstagramTariff->EndDate <= time() && !$InstagramTariff->IsCheking)
                {
                    $Permissions["GetInstagram"] -= $InstagramTariff->Access["GetInstagram"];
                    $Permissions["SetInstagram"] -= $InstagramTariff->Access["SetInstagram"];
                    $User->SetPermissions([$Permissions]);
                    if($Permissions["GetInstagram"] < $Counter = Counter::CountNotBannedInstagrams($InstagramTariff->UserId))
                    {
                        $InstagramSDK = new InstagramSdkController();
                        foreach($Instagrams = Instagram::FindAllByUserId($User->GetId()) as $Instagram)
                        {
                            if($Counter == $Permissions["GetInstagram"])
                                break;
                            try { $InstagramSDK->CloseInstagram($Instagram); } catch(Exception $error) {}
                            $Instagram->SetIsBanned(true);
                            $Instagram->Save();
                            $Counter--;
                        }
                    }
                    $InstagramTariff->Status = "completed";
                    $InstagramTariff->IsCheking = true;
                    $InstagramTariff->Save();
                    $User->Save();
                }
            }
            sleep(1);
        }
    }
    catch(Throwable $error)
    {
        Logger::Log(Log::TYPE_ERROR, "SubscriptionService", (string)$error);
    }
}