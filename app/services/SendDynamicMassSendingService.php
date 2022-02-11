<?php

/**
 * ПЕРЕПИСАТЬ
 */


namespace services;


use Exception;
use controllers\VenomBotController;
use controllers\MessageController;
use factories\UserFactory;
use models\Authentication;
use models\DynamicMassSending;
use models\DynamicMassSendingPhone;
use models\ExternalPhone;
use models\Folder;
use models\Task;
use models\User;
use models\Whatsapp;
use models\dialogues\WhatsappDialog;


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
    $Task = Task::Next("SendDynamicMassSending");
    if(!empty($Task))
    {
        try
        {
            // $Curl = curl_init();
            // curl_setopt_array($Curl, array(
            //     CURLOPT_URL => DOMAIN_API_URL . "/services/SendDynamicMassSendingService.php",
            //     CURLOPT_TIMEOUT => 1,
            //     CURLOPT_HTTPHEADER => array("Cookie: XDEBUG_SESSION=XDEBUG_ECLIPSE")
            // ));
            // curl_exec($Curl);
            // curl_close($Curl);
            while(true)
            {
                $DynamicMassSending = DynamicMassSending::FindById($Task->GetData()["DynamicMassSendingId"]);
                
                /**
                 * Проверка условий запуска
                 */
                if($DynamicMassSending->CheckStart())
                {
                    /**
                     * Подготовка...
                     */
                    $Whatsapp = Whatsapp::FindById($Task->GetData()["WhatsappId"]);
                    if(empty($Whatsapp))
                        throw new Exception("Whatsapp is empty");
                    
                    
                    $Whatsapp->SetAvatar($DynamicMassSending->GetAvatar());
                    $Whatsapp->SetActivityId($DynamicMassSending->GetActivityId());
                    $Whatsapp->SetCompanyName($DynamicMassSending->GetCompanyName());
                    $Whatsapp->Save();


                    $VenomBotController->UpdateSettingsWhatsappBusiness($Whatsapp);
                    $VenomBotController->InitWhatsapp($Whatsapp);

                    if(!empty($Whatsapp->GetAvatar()))
                        $VenomBotController->SetAvatar($Whatsapp);

                    $FolderAll = Folder::FindById($DynamicMassSending->GetDialogFolderId());
                    /**
                     * Рассылка
                     */
                    for($t = 0; $t < $Whatsapp->GetSendCountDay(); $t++)
                    {
                        $DynamicMassSendingPhone = DynamicMassSendingPhone::Next($DynamicMassSending->GetId());
                        $DynamicMassSendingPhone->SetWhatsappId($Whatsapp->GetId());
                        $DynamicMassSendingPhone->SetIsDone(true);
                        try
                        {
                            //FixDialogues
                            $Dialog = WhatsappDialog::FindByPhoneAndWhatsappId($DynamicMassSendingPhone->GetPhone(), $Whatsapp->GetId());
                            if(empty($Dialog))
                                $Dialog = new WhatsappDialog();

                            //Разрешаем доступ к диалогам
                            $Dialog->SetWhatsappId($Whatsapp->GetId());
                            $Dialog->SetPhone($DynamicMassSendingPhone->GetPhone());
                            $Dialog->SetName(empty($DynamicMassSendingPhone->GetName()) ? $DynamicMassSendingPhone->GetPhone() : $DynamicMassSendingPhone->GetName());
                            $Dialog->SetFolderId($FolderAll->GetId());
                            $Dialog->AddUserIdToWhitelist($DynamicMassSending->GetUserId());
                            $Dialog->Save();
                            
                            (new MessageController())->SendMessage($Dialog, $DynamicMassSending->GetMessage());
                            
                            //FixLog
                            file_put_contents(ROOT."/U_". $DynamicMassSending->GetUserId() .$DynamicMassSending->GetName()."Log.txt", "$Phone Отправлено " . time() . "\n", FILE_APPEND);
                            
                            $DynamicMassSendingPhone->SetIsSent(true);
                            $DynamicMassSendingPhone->SetStatus("Отправлено");
                            $DynamicMassSendingPhone->Save();
                            sleep(5);
                        }
                        catch(Exception $error)
                        {
                            $DynamicMassSendingPhone->SetIsSent(false);

                            if($error->getCode() == 404)
                                $DynamicMassSendingPhone->SetStatus("Номер не существует");
                            else
                                throw new Exception("Send error");

                            //FixLog
                            file_put_contents(ROOT."/U_". $DynamicMassSending->GetUserId() .$DynamicMassSending->GetName()."Log.txt", "$Phone ".$DynamicMassSendingPhone->GetStatus()." " . time() . "\n", FILE_APPEND);

                            $DynamicMassSendingPhone->Save();

                            if($error->getCode() != 404)
                                throw $error;
                        }

                        $ExternalPhone = ExternalPhone::FindByPhone($DynamicMassSendingPhone->GetPhone());
                        if(empty($ExternalPhone))
                            $ExternalPhone = new ExternalPhone();
                        $ExternalPhone->SetPhone($DynamicMassSendingPhone->GetPhone());
                        $ExternalPhone->AddIdUse($Whatsapp->GetId());
                        $ExternalPhone->Save();

                        //Fix
                        $DynamicMassSending = DynamicMassSending::FindById($DynamicMassSending->GetId());

                        //Отметить то, что было отправлено
                        $DynamicMassSending->CalculateCountSent();

                        $DynamicMassSending->SetStatus("Отправка: " . ceil($DynamicMassSending->GetCountSent() / ($DynamicMassSending->GetCountSend() * 0.01)) . "%");

                        
                        $DynamicMassSending->Save();

                        while(true)
                        {
                            if($DynamicMassSending->CheckStart())
                                break;
                            else if(!$DynamicMassSending->GetIsEnable())
                                throw new Exception("Exit");
                                
                            sleep(5);
                        }
                    }

                    /**
                     * Завершение
                     */
                    //$VenomBotController->CloseWhatsapp($Whatsapp);
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