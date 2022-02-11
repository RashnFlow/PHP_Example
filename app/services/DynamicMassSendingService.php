<?php

/**
 * ПЕРЕПИСАТЬ
 */


namespace services;


use Exception;
use controllers\VenomBotController;
use factories\UserFactory;
use models\Authentication;
use models\DynamicMassSending;
use models\Task;
use models\User;


/**
 * Init
 */
set_time_limit(0);
define("ROOT", str_replace("\\", "/", __DIR__ . "/.."));
require ROOT."/init.php";







/**
 * Service
 */
Authentication::SetAuthUser(UserFactory::GetSystemUser(), SYSTEM_SESSION);

echo "Initialization complete";

$VenomBotController = new VenomBotController();
while(true)
{
    $Task = Task::Next("StartDynamicMassSending");
    if(!empty($Task))
    {
        try
        {
            $Curl = curl_init();
            curl_setopt_array($Curl, array(
                CURLOPT_URL => DOMAIN_API_URL . "/services/DynamicMassSendingService.php",
                CURLOPT_TIMEOUT => 1,
                CURLOPT_HTTPHEADER => array("Cookie: XDEBUG_SESSION=XDEBUG_ECLIPSE")
            ));
            curl_exec($Curl);
            curl_close($Curl);

            while(true)
            {
                $DynamicMassSending = DynamicMassSending::FindById($Task->GetData()["DynamicMassSendingId"]);

                /**
                 * Проверка условий запуска
                 */
                if($DynamicMassSending->CheckStart())
                {
                    if(!empty($DynamicMassSending->GetSendFileUid()))
                        $DynamicMassSending->UploadFile();

                    for($i = 0; $i < 5; $i++)
                    {
                        $TaskTemp = new Task();
                        $TaskTemp->SetType("SendDynamicMassSending");
                        $TaskTemp->SetData(["DynamicMassSendingId" => $DynamicMassSending->GetId(), "WhatsappId" => $DynamicMassSending->ReserveWhatsapp()]);
                        $TaskTemp->Save();

                        sleep(5);
                    }

                    $DynamicMassSending->Save();
                    break;
                }
                else if(!$DynamicMassSending->GetIsEnable())
                    break;
                else
                    sleep(5);
            }
            $Task->Delete();
            die();
        }
        catch(Exception $error)
        {
            $Task->SetIsRunning(false);
            $Task->Fail();
            $Task->Save();
            die();
        }
    }
    sleep(1);
}