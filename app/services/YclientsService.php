<?php


namespace services;

use classes\Tag;
use controllers\MessageController;
use controllers\YclientsController;
use Exception;
use factories\UserFactory;
use models\Authentication;
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
            case 'RecordRemindAtTime':
                $Parameters = $YclientTask->GetParameters()[0];
                if(strtotime($Parameters['start_time']) <= strtotime(date('G:i')) && $YclientTask->GetIgnorePhone('Sent')['RecordRemindAtTime']['Date'] != date('m.d'))
                {
                    $Date = $YclientTask->GetIgnorePhone('Sent');
                    if($Date['RecordRemindAtTime']['Date'] != date('m.d'))
                    {
                        $Date['RecordRemindAtTime'] = [];
                        $YclientTask->SetIgnorePhone('Sent', $Date);
                        $YclientTask->Save();
                    }
                    $Phones = $YclientTask->GetIgnorePhone('Sent')['RecordRemindAtTime']['Phones'];
                    if(empty($Phones))
                        $Phones = [];
                    $Yclients = Yclients::FindById($YclientTask->GetYclientsIntegrationId());
                    $NDate = date('Y-m-d' , strtotime('+' . $Parameters['n_day'] . ' day'));
                    for($i = 1; $i<1000; $i++)
                    {
                        $Response = YclientsController::requestCurl('records/' . $Parameters['company_id'],
                            ['start_date' => $NDate,
                            'end_date' => $NDate,
                            'page' => $i],
                            'GET',
                            [
                                'Authorization: Bearer ' . YCLIENTS_BEARER_KEY . ', User ' . $Yclients->GetUserToken(),
                                'Content-Type: application/json',
                                'Accept: application/vnd.yclients.v2+json'
                            ]);
                        
                        if(empty($Response['data']))
                            break;

                        if($Response['success'])
                        {
                            foreach($Response['data'] as $Client)
                            {
                                if(empty($Client['client']['phone']) || $Client['visit_attendance'] == -1 || in_array($Client['client']['phone'], $Phones))
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
                                    if(!empty($Client['client']['name']))
                                        $Dialog->SetName($Client['client']['name']);
                                    $Dialog->Save();
                                    try
                                    {
                                        $serviceName = '';
                                        foreach($Client['services'] as $Service)
                                        {
                                            $serviceName .= $Service['title'] . ' ';
                                        }
                                        $Message = Message::CreateMessageObj($Parameters['message']);
                                        $Message->SetSource('Yclients');
                                        if($Message->GetType() == Message::MESSAGE_TYPE_TEXT)
                                        {
                                            $Message->SetText(Tag::ReplaceTag(['clientName'     => $Client['client']['name'],
                                                                            'dateAndTime'       => $Client['date'],
                                                                            'masterName'        => $Client['staff']['name'],
                                                                            'serviceName'       => $serviceName], 
                                                                            $Message->GetText()));
                                        }
                                        (new MessageController())->SendMessage($Dialog, $Message);
                                        $Phones[] = $Client['client']['phone'];
                                    }
                                    catch(Exception $error) {}
                                }
                            }
                        }
                    }
                    $Data = $YclientTask->GetIgnorePhone('Sent');
                    if(empty($Data['RecordRemindAtTime']))
                    {
                        $Data['RecordRemindAtTime'] = ['Date' => date('m.d'), 'Phones' => $Phones];
                        $YclientTask->SetIgnorePhone('Sent', $Data);
                    }
                    else
                    {
                        $Data['RecordRemindAtTime']['Phones'] = $Phones;
                        $YclientTask->SetIgnorePhone('Sent', $Data);
                    }
                    $YclientTask->Save();
                }
                break;
            case 'Review':
                $Parameters = $YclientTask->GetParameters()[0];
                $Date = $YclientTask->GetIgnorePhone('Sent');
                    if($Date['Review']['Date'] != date('m.d'))
                    {
                        $Date['Review'] = [];
                        $YclientTask->SetIgnorePhone('Sent', $Date);
                        $YclientTask->Save();
                    }
                    $Phones = $YclientTask->GetIgnorePhone('Sent')['Review']['Phones'];
                if(empty($Phones))
                    $Phones = [];
                $Yclients = Yclients::FindById($YclientTask->GetYclientsIntegrationId());
                $NDate = date('Y-m-d' , strtotime('-' . $Parameters['n_minutes'] . ' minutes'));
                for($i = 1; $i<1000; $i++)
                {
                    $Response = YclientsController::requestCurl('records/' . $Parameters['company_id'],
                        ['start_date' => $NDate,
                        'end_date' => $NDate,
                        'page' => $i],
                        'GET',
                        [
                            'Authorization: Bearer ' . YCLIENTS_BEARER_KEY . ', User ' . $Yclients->GetUserToken(),
                            'Content-Type: application/json',
                            'Accept: application/vnd.yclients.v2+json'
                        ]);
                    
                    if(empty($Response['data']))
                        break;

                    if($Response['success'])
                    {
                        foreach($Response['data'] as $Client)
                        {
                            if(empty($Client['client']['phone']) || $Client['visit_attendance'] == -1 || $Client['visit_attendance'] == 0 || in_array($Client['client']['phone'], $Phones) || strtotime($Client['date'] . '+' . $Parameters['n_minutes'] . ' minutes') >= time() || !preg_match('/ПРОБНЫЙ/', $Client['services'][0]['title']))
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
                                if(!empty($Client['client']['name']))
                                    $Dialog->SetName($Client['client']['name']);
                                $Dialog->Save();
                                try
                                {
                                    $serviceName = '';
                                    foreach($Client['services'] as $Service)
                                    {
                                        $serviceName .= $Service['title'] . ' ';
                                    }
                                    $Message = Message::CreateMessageObj($Parameters['message']);
                                    $Message->SetSource('Yclients');
                                    if($Message->GetType() == Message::MESSAGE_TYPE_TEXT)
                                    {
                                        $Message->SetText(Tag::ReplaceTag(['clientName'     => $Client['client']['name'],
                                                                        'dateAndTime'       => $Client['date'],
                                                                        'masterName'        => $Client['staff']['name'],
                                                                        'serviceName'       => $serviceName], 
                                                                        $Message->GetText()));
                                    }
                                    (new MessageController())->SendMessage($Dialog, $Message);
                                    $Phones[] = $Client['client']['phone'];
                                }
                                catch(Exception $error) {}
                            }
                        }
                    }
                    $Data = $YclientTask->GetIgnorePhone('Sent');
                    if(empty($Data['Review']))
                    {
                        $Data['Review'] = ['Date' => date('m.d'), 'Phones' => $Phones];
                        $YclientTask->SetIgnorePhone('Sent', $Data);
                    }
                    else
                    {
                        $Data['Review']['Phones'] = $Phones;
                        $YclientTask->SetIgnorePhone('Sent', $Data);
                    }
                    $YclientTask->Save();
                }
                break;
            case 'RevisitReminder':
                $Parameters = $YclientTask->GetParameters()[0];
                if(strtotime($Parameters['start_time']) <= strtotime(date('G:i')) && $YclientTask->GetIgnorePhone('Sent')['RevisitReminder']['Date'] != date('m.d'))
                {
                    $Date = $YclientTask->GetIgnorePhone('Sent');
                    if($Date['RevisitReminder']['Date'] != date('m.d'))
                    {
                        $Date['RevisitReminder'] = [];
                        $YclientTask->SetIgnorePhone('Sent', $Date);
                        $YclientTask->Save();
                    }
                    $Phones = $YclientTask->GetIgnorePhone('Sent')['RevisitReminder']['Phones'];
                    if(empty($Phones))
                        $Phones = [];
                    $Yclients = Yclients::FindById($YclientTask->GetYclientsIntegrationId());
                    $NDate = date('Y-m-d' , strtotime('-' . $Parameters['n_day'] . ' day'));
                    $Flag = false;
                    for($i = 1; $i<1000; $i++)
                    {
                        $Response = YclientsController::requestCurl('company/' . $Parameters['company_id'] . '/clients/search',
                        ['order_by' => 'last_visit_date',
                        'order_by_direction' => 'DESC',
                        'page_size' => 200,
                        'page' => $i],
                        'POST',
                        [
                            'Authorization: Bearer ' . YCLIENTS_BEARER_KEY . ', User ' . $Yclients->GetUserToken(),
                            'Content-Type: application/json',
                            'Accept: application/vnd.yclients.v2+json'
                        ]);
                        
                        if(empty($Response['data']) || $Flag)
                            break;

                        if($Response['success'])
                        {
                            foreach($Response['data'] as $LastDate)
                            {
                                $DayVisit = explode(' ', $LastDate['last_visit_date'])[0];
                                if(strtotime($DayVisit) > strtotime($NDate))
                                    continue;
                                else if(strtotime($DayVisit) == strtotime($NDate))
                                {
                                    $Client = YclientsController::requestCurl('client/' . $Parameters['company_id'] . '/' . $LastDate['id'],
                                    [],
                                    'GET',
                                    [
                                        'Authorization: Bearer ' . YCLIENTS_BEARER_KEY . ', User ' . $Yclients->GetUserToken(),
                                        'Content-Type: application/json',
                                        'Accept: application/vnd.yclients.v2+json'
                                    ]);
                                    if(in_array($Client['data']['phone'], $Phones))
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
                                        if(!empty($Client['data']['name']))
                                            $Dialog->SetName($Client['data']['name']);
                                        $Dialog->Save();
                                        try
                                        {
                                            $Message = Message::CreateMessageObj($Parameters['message']);
                                            $Message->SetSource('Yclients');
                                            if($Message->GetType() == Message::MESSAGE_TYPE_TEXT)
                                            {
                                                $Message->SetText(Tag::ReplaceTag(['clientName'     => $Client['data']['name'],
                                                                                'dateAndTime'       => $DayVisit], 
                                                                                $Message->GetText()));
                                            }
                                            (new MessageController())->SendMessage($Dialog, $Message);
                                            $Phones[] = $Client['data']['phone'];
                                        }
                                        catch(Exception $error) {}
                                    }
                                }
                                else if(strtotime($DayVisit) < strtotime($NDate))
                                {
                                    $Flag = true;
                                    break;
                                }
                            }
                        }
                    }
                    $Data = $YclientTask->GetIgnorePhone('Sent');
                    if(empty($Data['RevisitReminder']))
                    {
                        $Data['RevisitReminder'] = ['Date' => date('m.d'), 'Phones' => $Phones];
                        $YclientTask->SetIgnorePhone('Sent', $Data);
                    }
                    else
                    {
                        $Data['RevisitReminder']['Phones'] = $Phones;
                        $YclientTask->SetIgnorePhone('Sent', $Data);
                    }
                    $YclientTask->Save();
                }
                break;
            case 'CrossSelling':
                $Parameters = $YclientTask->GetParameters()[0];
                if(strtotime($Parameters['start_time']) <= strtotime(date('G:i')) && $YclientTask->GetIgnorePhone('Sent')['CrossSelling']['Date'] != date('m.d'))
                {
                    $Date = $YclientTask->GetIgnorePhone('Sent');
                    if($Date['CrossSelling']['Date'] != date('m.d'))
                    {
                        $Date['CrossSelling'] = [];
                        $YclientTask->SetIgnorePhone('Sent', $Date);
                        $YclientTask->Save();
                    }
                    $Phones = $YclientTask->GetIgnorePhone('Sent')['CrossSelling']['Phones'];
                    if(empty($Phones))
                        $Phones = [];
                    $Yclients = Yclients::FindById($YclientTask->GetYclientsIntegrationId());
                    $NDate = date('Y-m-d' , strtotime('-' . $Parameters['n_day'] . ' day'));
                    $YclientService = [];
                    for($i = 1; $i<1000; $i++)
                    {
                        $Response = YclientsController::requestCurl('records/' . $Parameters['company_id'],
                            ['start_date' => $NDate,
                            'end_date' => date('Y-m-d'),
                            'page' => $i],
                            'GET',
                            [
                                'Authorization: Bearer ' . YCLIENTS_BEARER_KEY . ', User ' . $Yclients->GetUserToken(),
                                'Content-Type: application/json',
                                'Accept: application/vnd.yclients.v2+json'
                            ]);
                        
                        if(empty($Response['data']))
                            break;

                        if($Response['success'])
                        {
                            foreach($Response['data'] as $DataYclients)
                            {
                                foreach($DataYclients['services'] as $Service)
                                {
                                    if($Service['id'] == $Parameters['visited_service_id'])
                                        $YclientService[$DataYclients['client']['id']]['visited_service'] += 1;
                                    if($Service['id'] == $Parameters['offered_service_id'])
                                        $YclientService[$DataYclients['client']['id']]['offered_service'] += 1;
                                }
                            }
                            foreach($YclientService as $Id => $Services)
                            {
                                if(($YclientService[$Id]['visited_service'] >= $Parameters['visited_service_start'] && $YclientService[$Id]['visited_service'] <= $Parameters['visited_service_end']) && ($YclientService[$Id]['offered_service'] >= $Parameters['offered_service_start'] && $YclientService[$Id]['offered_service'] <= $Parameters['offered_service_end']))
                                {
                                    $Client = YclientsController::requestCurl('client/' . $Parameters['company_id'] . '/' . $Id,
                                    [],
                                    'GET',
                                    [
                                        'Authorization: Bearer ' . YCLIENTS_BEARER_KEY . ', User ' . $Yclients->GetUserToken(),
                                        'Content-Type: application/json',
                                        'Accept: application/vnd.yclients.v2+json'
                                    ]);

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
                                        if(!empty($Client['data']['name']))
                                            $Dialog->SetName($Client['data']['name']);
                                        $Dialog->Save();
                                        try
                                        {
                                            $Message = Message::CreateMessageObj($Parameters['message']);
                                            $Message->SetSource('Yclients');
                                            if($Message->GetType() == Message::MESSAGE_TYPE_TEXT)
                                            {
                                                $Message->SetText(Tag::ReplaceTag(['clientName'     => $Client['data']['name']], 
                                                                                $Message->GetText()));
                                            }
                                            (new MessageController())->SendMessage($Dialog, $Message);
                                            $Phones[] = $Client['data']['phone'];
                                        }
                                        catch(Exception $error) {}
                                    }
                                }
                            }
                        }
                    }
                    $Data = $YclientTask->GetIgnorePhone('Sent');
                    if(empty($Data['CrossSelling']))
                    {
                        $Data['CrossSelling'] = ['Date' => date('m.d'), 'Phones' => $Phones];
                        $YclientTask->SetIgnorePhone('Sent', $Data);
                    }
                    else
                    {
                        $Data['CrossSelling']['Phones'] = $Phones;
                        $YclientTask->SetIgnorePhone('Sent', $Data);
                    }
                    $YclientTask->Save();
                }
                break;
            case 'RecordRemind':
                $Parameters = $YclientTask->GetParameters()[0];
                $Date = $YclientTask->GetIgnorePhone('Sent');
                if($Date['RecordRemind']['Date'] != date('m.d'))
                {
                    $Date['RecordRemind'] = [];
                    $YclientTask->SetIgnorePhone('Sent', $Date);
                    $YclientTask->Save();
                }
                $Phones = $YclientTask->GetIgnorePhone('Sent')['RecordRemind']['Phones'];
                if(empty($Phones))
                    $Phones = [];
                $Yclients = Yclients::FindById($YclientTask->GetYclientsIntegrationId());
                $NDate = date('Y-m-d' , strtotime('+' . $Parameters['n_hour'] . ' hour'));
                for($i = 1; $i<1000; $i++)
                {
                    $Response = YclientsController::requestCurl('records/' . $Parameters['company_id'],
                        ['start_date' => $NDate,
                        'end_date' => $NDate,
                        'page' => $i],
                        'GET',
                        [
                            'Authorization: Bearer ' . YCLIENTS_BEARER_KEY . ', User ' . $Yclients->GetUserToken(),
                            'Content-Type: application/json',
                            'Accept: application/vnd.yclients.v2+json'
                        ]);
                    
                    if(empty($Response['data']))
                        break;

                    if($Response['success'])
                    {
                        foreach($Response['data'] as $Client)
                        {
                            if(empty($Client['client']['phone']) || $Client['visit_attendance'] == -1 || strtotime($Client['date']) >= strtotime(date('G:i') . '+' . $Parameters['n_hour'] . ' hour') || in_array($Client['client']['phone'], $Phones))
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
                                if(!empty($Client['client']['name']))
                                    $Dialog->SetName($Client['client']['name']);
                                $Dialog->Save();
                                try
                                {
                                    $serviceName = '';
                                    foreach($Client['services'] as $Service)
                                    {
                                        $serviceName .= $Service['title'] . ' ';
                                    }
                                    $Message = Message::CreateMessageObj($Parameters['message']);
                                    $Message->SetSource('Yclients');
                                    if($Message->GetType() == Message::MESSAGE_TYPE_TEXT)
                                    {
                                        $Message->SetText(Tag::ReplaceTag(['clientName'     => $Client['client']['name'],
                                                                        'dateAndTime'       => $Client['date'],
                                                                        'masterName'        => $Client['staff']['name'],
                                                                        'serviceName'       => $serviceName], 
                                                                        $Message->GetText()));
                                    }
                                    (new MessageController())->SendMessage($Dialog, $Message);
                                    $Phones[] = $Client['client']['phone'];
                                }
                                catch(Exception $error) {}
                            }
                        }
                    }
                }
                $Data = $YclientTask->GetIgnorePhone('Sent');
                if(empty($Data['RecordRemind']))
                {
                    $Data['RecordRemind'] = ['Date' => date('m.d'), 'Phones' => $Phones];
                    $YclientTask->SetIgnorePhone('Sent', $Data);
                }
                else
                {
                    $Data['RecordRemind']['Phones'] = $Phones;
                    $YclientTask->SetIgnorePhone('Sent', $Data);
                }
                $YclientTask->Save();
                break;
        }
    }
    sleep(2);
}