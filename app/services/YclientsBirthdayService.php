<?php


namespace services;

use classes\Tag;
use classes\Validator;
use controllers\MessageController;
use controllers\YclientsController;
use Exception;
use factories\UserFactory;
use models\Authentication;
use models\dialogues\InstagramDialog;
use models\dialogues\WhatsappDialog;
use models\Instagram;
use models\Message;
use models\Whatsapp;
use models\Yclients;
use models\YclientsTasks;

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

echo 'Initialization complete';

while(true)
{
    foreach(YclientsTasks::FindAll() as $YclientTask)
    {
        switch($YclientTask->GetType())
        {
            case 'Birthday':
                $Parameters = $YclientTask->GetParameters()[0];
                if(strtotime($Parameters['start_time']) <= strtotime(date('G:i')) && $YclientTask->GetIgnorePhone('Sent')['BirthDay']['Date'] != date('m.d'))
                {
                    $Date = $YclientTask->GetIgnorePhone('Sent');
                    if($Date['BirthDay']['Date'] != date('m.d'))
                    {
                        $Date['BirthDay'] = [];
                        $YclientTask->SetIgnorePhone('Sent', $Date);
                        $YclientTask->Save();
                    }
                    $Phones = $YclientTask->GetIgnorePhone('Sent')['BirthDay']['Phones'];
                    if(empty($Phones))
                        $Phones = [];
                    $Yclients = Yclients::FindById($YclientTask->GetYclientsIntegrationId());
                    $CurrentDate =  str_replace('.', '', date('m.d'));
                    for($i = 1; $i<1000; $i++)
                    {
                        $Result = YclientsController::requestCurl('company/' . $Parameters['company_id']  . '/clients/search', 
                            [
                            'fields' => [
                                'phone',
                                'name'
                            ],
                            'page_size' => 200,
                            'page' => $i
                            ], 'POST',
                            [
                                'Authorization: Bearer ' . YCLIENTS_BEARER_KEY . ', User ' . $Yclients->GetUserToken(),
                                'Content-Type: application/json',
                                'Accept: application/vnd.yclients.v2+json'
                            ]);
                        if(empty($Result['data']))
                            break;
                        
                        foreach($Result['data'] as $Client)
                        {
                            $Response = YclientsController::requestCurl('client/' . $Parameters['company_id'] . '/' . $Client['id'], 
                            [], 
                            'GET',
                            [
                                'Authorization: Bearer ' . YCLIENTS_BEARER_KEY . ', User ' . $Yclients->GetUserToken(),
                                'Content-Type: application/json',
                                'Accept: application/vnd.yclients.v2+json'
                            ]);

                            if($Response['success'])
                            {
                                $BirthDate = explode('-', $Response['data']['birth_date']);

                                if($BirthDate[1].$BirthDate[2] == $CurrentDate)
                                {
                                    if(empty($Response['data']['phone']) || in_array($Response['data']['phone'], $Phones))
                                        continue;

                                    if(!empty($Parameters['whatsapp_id']))
                                    {
                                        $Whatsapp = Whatsapp::FindById($Parameters['whatsapp_id']);
                                        $Dialog = YclientsController::FindDialogWithClient($Whatsapp, $Client['client']['phone']);
                                    }
                                    else if(!empty($Parameters['instagram_id']))
                                    {
                                        $Instagram = Instagram::FindById($Parameters['instagram_id']);
                                        $Dialog = YclientsController::FindDialogWithClient($Instagram, $Client['client']['phone']);
                                    }
                                    if(!empty($Dialog))
                                    {
                                        if(!empty($Response['data']['name']))
                                            $Dialog->SetName($Response['data']['name']);
                                        $Dialog->Save();
                                        try
                                        {
                                            $Message = Message::CreateMessageObj($Parameters['message']);
                                            $Message->SetSource('Yclients');
                                            if($Message->GetType() == Message::MESSAGE_TYPE_TEXT)
                                            {
                                                $Message->SetText(Tag::ReplaceTag(['clientName' => $Response['data']['name'],
                                                                                'clientPhone' =>$Response['data']['phone'],], 
                                                                                $Message->GetText()));
                                            }
                                            (new MessageController())->SendMessage($Dialog, $Message);
                                            $Phones[] = $Response['data']['phone'];
                                        }
                                        catch(Exception $error) {}
                                    }
                                }
                            }
                        }
                    }
                    $Data = $YclientTask->GetIgnorePhone('Sent');
                    if(empty($Data['BirthDay']))
                    {
                        $Data['BirthDay'] = ['Date' => date('m.d'), 'Phones' => $Phones];
                        $YclientTask->SetIgnorePhone('Sent', $Data);
                    }
                    else
                    {
                        $Data['BirthDay']['Phones'] = $Phones;
                        $YclientTask->SetIgnorePhone('Sent', $Data);
                    }
                    $YclientTask->Save();
                }
                break;
        }
    }
    sleep(1);
}