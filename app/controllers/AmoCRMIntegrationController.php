<?php

namespace controllers;

use classes\Freeze;
use classes\Http;
use classes\Log;
use classes\Logger;
use classes\Tag;
use classes\Tools;
use classes\Validator;
use Exception;
use factories\UserFactory;
use models\AmoCRMIntegration;
use models\Authentication;
use models\dialogues\Dialog;
use models\Instagram;
use models\dialogues\InstagramDialog;
use models\Message;
use models\Whatsapp;
use models\dialogues\WhatsappDialog;
use models\DynamicResource;
use sdk\php\amocrm\AmoCurl;
use views\PrintJson;

class AmoCRMIntegrationController
{
    static public function CheckAccess(?string $Token) : bool
    {
        if(md5($Token) == AMOCRM_API_KEY)
            return true;
        else
            throw new Exception("Access is denied", ACCESS_DENIED);
    }

    private function GetNewTokens(AmoCRMIntegration $Find)
    {
        static $counter;
        $request = [
            'client_id' => AMOCRM_CLIENT_ID,
            'client_secret' => AMOCRM_CLIENT_SECRET,
            'grant_type' => 'refresh_token',
            'refresh_token' => $Find->GetRefreshToken(),
            'redirect_uri' => AMOCRM_REDDIRECT_URI
        ];
        $result = AmoCurl::SendDataToAmoCRM($request, 'https://' . $Find->GetProfileURL() . '/oauth2/access_token');

        if(!empty($result['access_token']))
        {
            $counter = 0;
            $Find->SetAccessToken($result['access_token']);
            $Find->SetRefreshToken($result['refresh_token']);
            $Find->Save();
        } else
        {
            $counter++;
            Logger::Log(Log::TYPE_FATAL_ERROR, "AmoCRMTokenError", ["result" => $result, "request" => $request]);
        }
        if($counter > 5)
            throw new Exception("Error data: " . json_encode($result));
    }

    public function ActionAmoCRMInstall(array $Parameters)
    {
        if(Validator::IsValid($Parameters['Post'], [
            ["Key" => "client_id"],
            ["Key" => "code"],
            ["Key" => "referer"],
            ["Key" => "state"]
        ]))
        {
            $Domain = $Parameters['Post']['referer'];
            $AuthCode = $Parameters['Post']['code'];

            /**
             * Turn Data in Database
             */
            $response = AmoCurl::SendDataToAmoCRM([
                'client_id' => AMOCRM_CLIENT_ID,
                'client_secret' => AMOCRM_CLIENT_SECRET,
                'grant_type' => 'authorization_code',
                'code' => $AuthCode,
                'redirect_uri' => AMOCRM_REDDIRECT_URI,
            ],  'https://' . $Domain . '/oauth2/access_token');

            /**
             * Webhook
             */
            AmoCurl::SendDataAndHeaderToAmoCRM([
                "destination" => DOMAIN_API_URL . '/' . AMOCRM_WEBHOOK . '?token=' . AMOCRM_API_TOKEN,
                "settings" => [
                        "update_lead",
                        "add_lead"
                    ],
                    "sort" => 10
            ], [
            'Authorization: Bearer ' . $response['access_token']
            ], 'https://' . $Domain . '/api/v4/webhooks');

            if(empty(AmoCRMIntegration::FindByUserId()))
            {
                $AmoCRM = new AmoCRMIntegration();

                $AccountId = AmoCurl::SendHeaderToAmoCRM(['Authorization: Bearer ' . $response['access_token']],  'https://' . $Domain . '/api/v4/account?with=amojo_id')["amojo_id"];

                $body = json_encode([
                    'account_id' => $AccountId,
                    'title' => "FastLead",
                    'hook_api_version' => 'v2'
                ]);

                $Signature = hash_hmac('sha1', $body, AMOCRM_BOT_SECRET);

                $ScopeId = AmoCurl::SendDataToChatAmoCRM($body, 
                ["Cache-Control: no-cache",
                "Content-Type: application/json",
                "X-Signature: $Signature"],
                "https://amojo.amocrm.ru/v2/origin/custom/". AMOCRM_BOT_ID ."/connect");

                $Field = AmoCurl::SendDataAndHeaderToAmoCRM([
                    'name' => 'Instagram (FL)',
                    'type' => 'text'
                ], [
                    'Authorization: Bearer ' . $response['access_token']
                ], 'https://' . $Domain . '/api/v4/contacts/custom_fields');

                $AmoCRM->SetAccessToken($response['access_token']);
                $AmoCRM->SetProfileURL($Domain);
                $AmoCRM->SetRefreshToken($response['refresh_token']);
                $AmoCRM->SetUserId(Authentication::GetAuthUser()->GetId());
                $AmoCRM->SetAccountId($AccountId);
                $AmoCRM->SetScopeId($ScopeId["scope_id"]);
                $AmoCRM->SetCache('Instagram (FL)', $Field['id'], true);
                $AmoCRM->Save();

                PrintJson::OperationSuccessful();
            }
            else
                PrintJson::OperationError(IntegrationError, REQUEST_FAILED);
        }
    }


    public function ActionAmoCRMWebHook(array $Parameters)
    {
        Freeze::SetProgress("Webhook_AmoCRM_" . $_POST['account']['subDomain']);

        Authentication::SetAuthUser(UserFactory::GetSystemUser(), SYSTEM_SESSION);

        if(!empty($AmoCRMs = AmoCRMIntegration::FindAllByProfileUrl($_POST['account']['subDomain'] . ".amocrm.ru")))
        {
            foreach($AmoCRMs as $AmoCRM)
            {
                if(!empty($_POST['leads']['add']))
                {
                    $pipelineId = $_POST['leads']['add'][0]['pipeline_id'];
                    $statusId = $_POST['leads']['add'][0]['status_id'];
                    $id = $_POST['leads']['add'][0]['id'];
                }
                else if(!empty($_POST['leads']['update']))
                {
                    $pipelineId = $_POST['leads']['update'][0]['pipeline_id'];
                    $statusId = $_POST['leads']['update'][0]['status_id'];
                    $id = $_POST['leads']['update'][0]['id'];
                }
                if($statusId != $AmoCRM->GetCache("STATUS_ID_" . $id))
                {
                    $AmoCRM->SetCache("STATUS_ID_" . $id, $statusId);
                    $AmoCRM->Save();
                    $FunnelActions = $AmoCRM->GetFunnelActions();
                    foreach($FunnelActions as $Type => $Id)
                    {
                        foreach($Id as $FunnelAction)
                        {
                            try
                            {
                                //FixDialogues
                                if(!empty($FunnelAction[$pipelineId][$statusId]))
                                {
                                    if(!empty($_POST['leads']['add']))
                                    {
                                        $leads = AmoCurl::SendHeaderToAmoCRM([
                                            'Authorization: Bearer ' . $AmoCRM->GetAccessToken()
                                            ], 'https://' . $AmoCRM->GetProfileURL() . '/api/v4/leads/'. $id .'?with=contacts');
                                    } else if(!empty($_POST['leads']['update']))
                                    {
                                        $leads = AmoCurl::SendHeaderToAmoCRM([
                                            'Authorization: Bearer ' . $AmoCRM->GetAccessToken()
                                            ], 'https://' . $AmoCRM->GetProfileURL() . '/api/v4/leads/'. $id .'?with=contacts');    
                                    }
                                    
                                    $contact = AmoCurl::SendHeaderToAmoCRM([
                                        'Authorization: Bearer ' . $AmoCRM->GetAccessToken()
                                        ], 'https://' . $AmoCRM->GetProfileURL() . '/api/v4/contacts/'. $leads['_embedded']['contacts'][0]['id']);
                                    
                                    if(empty($leads['_embedded']) || empty($contact['_embedded']))
                                    {
                                        $this->GetNewTokens($AmoCRM);
                                        $this->ActionAmoCRMWebHook($Parameters);
                                    }
                                    $Name = $contact["name"]; // Тэг с именем клиента
                                    foreach($leads["custom_fields_values"] as $obj)
                                    {
                                        if($obj["field_type"] == "date")
                                            $Date = date('d/m/Y', $obj["values"][0]["value"]); //Тэг с датой
                                    }
                                    $Message = Message::CreateMessageObj($FunnelAction[$pipelineId][$statusId]);
                                    $Message->SetSource("AmoCRM");
                                    foreach ($contact['custom_fields_values'] as $obj)
                                    {
                                        if($Type == "WhatsApp" && $obj['field_name'] == "Телефон")
                                        {
                                            $Whatsapp = Whatsapp::FindById(key($Id));
                                            $Dialog = WhatsappDialog::FindByPhoneAndWhatsappId(Validator::NormalizePhone($obj['values'][0]['value']), $Whatsapp->GetId());
                                            if(!empty($Whatsapp))
                                            {
                                                if(empty($Dialog))
                                                    $Dialog = new WhatsappDialog();
                                                $Dialog->SetWhatsappId($Whatsapp->GetId());
                                                $Dialog->SetPhone(Validator::NormalizePhone($obj['values'][0]['value']));
                                            }                        
                                        } elseif($Type == "Instagram" && $obj['field_name'] == 'Instagram (FL)')
                                        {
                                            $Instagram = Instagram::FindById(key($Id));
                                            $Dialog = InstagramDialog::FindByLoginAndInstagramId($obj['values'][0]['value'], $Instagram->GetId());
                                            if (!empty($Instagram))
                                            {
                                                if(empty($Dialog))
                                                    $Dialog = new InstagramDialog();
                                                $Dialog->SetLogin(str_replace("@", "", $obj['values'][0]['value']));
                                                $Dialog->SetInstagramId($Instagram->GetId());
                                            }
                                        }
                                        if(!empty($Dialog))
                                        {
                                            if(!empty($contact["name"]))
                                                $Dialog->SetName($contact["name"]);
                                            else
                                                $Dialog->SetName($obj["custom_fields_values"][0]["field_name"]);

                                            if(!empty($FunnelActions["Folder"][$pipelineId][$statusId]["folder_id"]))
                                                $Dialog->SetFolderId($FunnelActions["Folder"][$pipelineId][$statusId]["folder_id"]);

                                            $Dialog->Save();
                                            $Tag = [
                                                "clientName" => $Name,
                                                "date" => $Date
                                            ];
                                            try
                                            {  
                                                if($Message->GetType() == Message::MESSAGE_TYPE_TEXT)
                                                {
                                                    $Message->SetText(Tag::ReplaceTag($Tag, $Message->GetText()));
                                                }
                                                (new MessageController())->SendMessage($Dialog, $Message);
                                                return;
                                            }
                                            catch(Exception $error) {}
                                        }
                                    }
                                }
                                next($Id);
                            } 
                            catch(Exception $error) {}
                        }
                    } 
                }
            }
        }
        else
            PrintJson::OperationError(AmoCRMError, REQUEST_FAILED);

        Freeze::DeleteProgress("Webhook_AmoCRM_" . $_POST['account']['subDomain']);
    }


    public function OnMessage(Dialog $Dialog)
    {
        if(!empty($AmoCRM = AmoCRMIntegration::FindByUserId(Authentication::GetAuthUser()->GetId())) && $Dialog instanceof WhatsappDialog)
        {
            $Message = $Dialog->GetLastMessage();
            $ScopeId = $AmoCRM->GetScopeId();
            if(!empty($BotSettings = $AmoCRM->GetBotClientPhone($Dialog->GetWhatsappId(), $Dialog->GetPhone())) && !$Message->GetIsMe() && !$Dialog->IsNew())
                $body = [
                    "account_id" => $AmoCRM->GetAccountId(),
                    "event_type"=> "new_message",
                    "payload"=> [
                        "timestamp"=> time(),
                        "msgid"=> uniqid(),
                        "conversation_id"=> uniqid(),
                        "conversation_ref_id"=> $BotSettings["conversation_id"],
                            "sender"=> [
                                "id" => uniqid(),
                                "ref_id"=> $BotSettings["receiver_id"],
                                "name"=> $Dialog->GetName(),
                                "profile" => [
                                    "phone" => $Dialog->GetPhone()
                                ]
                            ],
                        "message"=> [
                            "text"=> (empty($Message->GetText()) ? "" : $Message->GetText()),
                        ],
                        "silent"=> false
                    ]
                ];
            else if(!empty($BotSettings = $AmoCRM->GetBotClientPhone($Dialog->GetWhatsappId(), $Dialog->GetPhone())) && $Message->GetIsMe()  && !$Dialog->IsNew())
                $body = [
                    "account_id" => $AmoCRM->GetAccountId(),
                    "event_type"=> "new_message",
                    "payload"=> [
                        "timestamp"=> time(),
                        "msgid"=> uniqid(),
                        "conversation_id"=> uniqid(),
                        "conversation_ref_id"=> $BotSettings["conversation_id"],
                            "sender"=> [
                                "id" => uniqid(),
                                "ref_id"=> $BotSettings["sender_id"],
                                "name"=> $Dialog->GetName(),
                                "profile" => [
                                    "phone" => $Dialog->GetPhone()
                                ]
                            ],
                        "message"=> [
                            "text"=> (empty($Message->GetText()) ? "" : $Message->GetText()),
                        ],
                        "silent"=> false
                    ]
                ];
            else if($Dialog->IsNew())
            {
                $ConversationId = uniqid();
                $SendId = uniqid();
                $DialogAction = $AmoCRM->GetNewDialogAction();

                if($Dialog instanceof WhatsappDialog)
                {
                    if(!empty($DialogAction['WhatsApp'][$Dialog->GetWhatsappId()]))
                    {
                        $Status = AmoCurl::SendHeaderToAmoCRM([
                            'Authorization: Bearer ' . $AmoCRM->GetAccessToken()
                        ], 'https://' . $AmoCRM->GetProfileURL() . '/api/v4/leads/pipelines/' . $DialogAction['WhatsApp'][$Dialog->GetWhatsappId()]['FunnelId'] . '/statuses/' . (int)$DialogAction['WhatsApp'][$Dialog->GetWhatsappId()]['ColumnUid']);
                    }
                }
                else if($Dialog instanceof InstagramDialog)
                {
                    if(!empty($DialogAction['Instagram'][$Dialog->GetInstagramId()]))
                    {
                        $Status = AmoCurl::SendHeaderToAmoCRM([
                            'Authorization: Bearer ' . $AmoCRM->GetAccessToken()
                        ], 'https://' . $AmoCRM->GetProfileURL() . '/api/v4/leads/pipelines/' . $DialogAction['Instagram'][$Dialog->GetInstagramId()]['FunnelId'] . '/statuses/' . (int)$DialogAction['Instagram'][$Dialog->GetInstagramId()]['ColumnUid']);
                    }
                }
                else
                    return;
                
                if($Status["name"] == "Неразобранное")
                {
                    //FixDialogues
                    if($Dialog instanceof WhatsappDialog)
                    {
                        $Phone = Validator::NormalizePhone($Dialog->GetPhone());
                        $ContactId = AmoCurl::SendDataAndHeaderToAmoCRM([[
                            'source_name' => 'FastLead',
                            'source_uid' => 'a1fee7c0fc436088e64ba2e8822ba2b3',
                            'pipeline_id' => $DialogAction['WhatsApp'][$Dialog->GetWhatsappId()]['FunnelId'],
                            'created_at' => time(),
                            '_embedded' => [
                                'leads' => [[
                                    'name' => 'Заявка от ' . $Dialog->GetName(),
                                    '_embedded' => [
                                        'tags' => [
                                            ['name' => 'FastLead'],
                                            ['name' => 'WhatsApp']
                                        ]
                                    ]
                                ]],
                                'contacts' => [[
                                'first_name' => $Dialog->GetName(),
                                "custom_fields_values" => [[
                                    "field_name"=> "Телефон",
                                    "field_code"=> "PHONE",
                                    "field_type"=> "multitext",
                                    "values"=> [[
                                        "value"=> $Phone,
                                        "enum_code"=> "WORK"
                                    ]]
                                ]]
                                ]]
                            ],
                            'metadata' => [
                            'ip' => '192.168.10.21',
                            'form_id' => 'a1fee7c0fc436088e64ba2e8822ba2b3',
                            'form_name' => 'Форма с FastLead',
                            'form_sent_at' => time(),
                            'form_page' => 'https://crm.fastlead.app'
                            ]
                        ]], [
                            'Authorization: Bearer ' . $AmoCRM->GetAccessToken()
                        ], 'https://' . $AmoCRM->GetProfileURL() . '/api/v4/leads/unsorted/forms')["_embedded"]["unsorted"][0]["_embedded"]["contacts"][0]["id"];
                    } 
                    elseif($Dialog instanceof InstagramDialog)
                    {
                        $ContactId = AmoCurl::SendDataAndHeaderToAmoCRM([[
                            'source_name' => 'FastLead',
                            'pipeline_id' => $DialogAction['Instagram'][$Dialog->GetInstagramId()]['FunnelId'],
                            'created_at' => time(),
                            '_embedded' => [
                                'leads' => [[
                                    'name' => 'Заявка от ' . $Dialog->GetName(),
                                    '_embedded' => [
                                        'tags' => [
                                            ['name' => 'FastLead'],
                                            ['name' => 'Instagram']
                                        ]  
                                    ]
                                ]],
                                'contacts' => [[
                                'first_name' => $Dialog->GetName(),
                                "custom_fields_values" => [[
                                    "field_id"=> $AmoCRM->GetCache('Instagram (FL)'),
                                        "values"=> [[
                                            "value"=> '@' . $Dialog->GetLogin()
                                        ]]
                                ]]
                                ]]
                            ]
                        ]], [
                            'Authorization: Bearer ' . $AmoCRM->GetAccessToken()
                        ], 'https://' . $AmoCRM->GetProfileURL() . '/api/v4/leads/unsorted/forms')["_embedded"]["unsorted"][0]["_embedded"]["contacts"][0]["id"];
                    }
                }
                else
                {
                    if($Dialog instanceof WhatsappDialog)
                    {
                        $Phone = Validator::NormalizePhone($Dialog->GetPhone());
                        $ContactId = AmoCurl::SendDataAndHeaderToAmoCRM([[
                            'name' => 'Заявка от ' . $Dialog->GetName(),
                            'status_id' => (int)$DialogAction['WhatsApp'][$Dialog->GetWhatsappId()]['ColumnUid'],
                            'pipeline_id' => $DialogAction['WhatsApp'][$Dialog->GetWhatsappId()]['FunnelId'],
                            '_embedded' => [
                                'contacts' => [[
                                'first_name' => $Dialog->GetName(),
                                "custom_fields_values" => [[
                                    "field_name"=> "Телефон",
                                    "field_code"=> "PHONE",
                                    "field_type"=> "multitext",
                                    "values"=> [[
                                        "value"=> $Phone,
                                        "enum_code"=> "WORK"
                                    ]]
                                ]]
                                ]],
                                'tags' => [
                                    ['name' => 'FastLead'],
                                    ['name' => 'WhatsApp']
                                ] 
                            ]
                        ]], [
                            'Authorization: Bearer ' . $AmoCRM->GetAccessToken()
                        ], 'https://' . $AmoCRM->GetProfileURL() . '/api/v4/leads/complex')[0]["contact_id"];
                    } 
                    elseif($Dialog instanceof InstagramDialog)
                    {
                        $ContactId = AmoCurl::SendDataAndHeaderToAmoCRM([[
                            'name' => 'Заявка от ' . $Dialog->GetName(),
                            'status_id' => (int)$DialogAction['Instagram'][$Dialog->GetInstagramId()]['ColumnUid'],
                            'pipeline_id' => $DialogAction['Instagram'][$Dialog->GetInstagramId()]['FunnelId'],
                            '_embedded' => [
                                'contacts' => [[
                                'first_name' => $Dialog->GetName(),
                                "custom_fields_values" => [[
                                        "field_id"=> $AmoCRM->GetCache('Instagram (FL)'),
                                        "values"=> [[
                                            "value"=> '@' . $Dialog->GetLogin()
                                        ]]
                                ]]
                                ]],
                                'tags' => [
                                    ['name' => 'FastLead'],
                                    ['name' => 'Instagram']
                                ] 
                            ]
                        ]], [
                            'Authorization: Bearer ' . $AmoCRM->GetAccessToken()
                        ], 'https://' . $AmoCRM->GetProfileURL() . '/api/v4/leads/complex')[0]["contact_id"];
                    }
                }

                $chat = [
                    "conversation_id" => $ConversationId,
                    "user" => [
                        "id" => $SendId,
                        "name" => $Dialog->GetName(),
                        "avatar" => "https://fastlead.app/assets/logo.svg",
                        "profile" => [
                            "phone" => $Dialog->GetPhone(),
                        ]
                    ]
                ];

                $SignatureChat = hash_hmac('sha1', json_encode($chat), AMOCRM_BOT_SECRET);

                $ChatId = AmoCurl::SendDataToChatAmoCRM(json_encode($chat), 
                [ "Content-Type: application/json",
                "X-Signature: $SignatureChat"],
                "https://amojo.amocrm.ru/v2/origin/custom/$ScopeId/chats")["id"];

                AmoCurl::SendDataAndHeaderToAmoCRM([[
                    "contact_id" => $ContactId,
                    "chat_id" => $ChatId 
                ]], [
                    'Authorization: Bearer ' . $AmoCRM->GetAccessToken()
                ], 'https://' . $AmoCRM->GetProfileURL() . '/api/v4/contacts/chats');

                $body = [
                    "account_id" => $AmoCRM->GetAccountId(),
                    "event_type"=> "new_message",
                    "payload"=> [
                        "timestamp"=> time(),
                        "msgid"=> uniqid(),
                        "conversation_id"=> $ConversationId,
                            "sender"=> [
                                "id" => $SendId,
                                "name"=> $Dialog->GetName(),
                                "profile" => [
                                    "phone" => $Dialog->GetPhone()
                                ]
                            ],
                        "message"=> [
                            "text"=> (empty($Message->GetText()) ? "" : $Message->GetText()),
                        ],
                        "silent"=> false
                    ]
                ];
            }
                

                switch($Message->GetType())
                {
                    case Message::MESSAGE_TYPE_IMG:
                        $DynamicResource = $Message->GetImg();
                        $body["payload"]["message"]["type"] = 'picture';
                        break;
                    case Message::MESSAGE_TYPE_VIDEO:
                        $DynamicResource = $Message->GetVideo();
                        $body["payload"]["message"]["type"] = 'video';
                        break;
                    case Message::MESSAGE_TYPE_DOCUMENT:
                        $DynamicResource = $Message->GetDocument();
                        $body["payload"]["message"]["type"] = 'file';
                    case Message::MESSAGE_TYPE_TEXT:
                        $body["payload"]["message"]["type"] = 'text';
                        break;
                }

                if($Message->GetType() == Message::MESSAGE_TYPE_IMG || $Message->GetType() == Message::MESSAGE_TYPE_VIDEO || $Message->GetType() == Message::MESSAGE_TYPE_DOCUMENT)
                    $body["payload"]["message"]["media"] = DOMAIN_API_URL . "/get/dynamic/resource?download=true&uid=" . $DynamicResource . "&user-id=" . Authentication::GetAuthUser()->GetId() . "&token=" . Tools::GenerateStringBySeed(100, Tools::ConvertStringToSeed($DynamicResource));
                

                $Signature = hash_hmac('sha1', json_encode($body), AMOCRM_BOT_SECRET);

                $ScopeId = AmoCurl::SendDataToChatAmoCRM(json_encode($body), 
                ["Cache-Control: no-cache",
                "Content-Type: application/json",
                "X-Signature: $Signature"],
                "https://amojo.amocrm.ru/v2/origin/custom/$ScopeId");
        }
        else
            PrintJson::OperationError(AmoCRMError, REQUEST_FAILED);
    }


    public function ActionAmoCRMBotWebHook(array $Parameters)
    {
        Authentication::SetAuthUser(UserFactory::GetSystemUser(), SYSTEM_SESSION);

        if(!empty($AmoCRM = AmoCRMIntegration::FindByAccountId($Parameters["Post"]["account_id"])))
        {
            $Phone = Validator::NormalizePhone($Parameters["Post"]["message"]["receiver"]["phone"]);
            $Whatsapp = Whatsapp::FindByUserId($AmoCRM->GetUserId());
            if(!empty($Whatsapp))
            {
                $Dialog = WhatsappDialog::FindByPhoneAndWhatsappId($Phone, $Whatsapp->GetId());
                if(empty($Dialog))
                    $Dialog = new WhatsappDialog();
                $Dialog->SetWhatsappId($Whatsapp->GetId());
                $Dialog->SetPhone($Phone);

                if(!empty($Dialog))
                {
                    $Dialog->SetName($Phone);

                    $Message = new Message();
                    $Message->SetIsMe(true);
                    $Message->SetTime(time());

                    if(!empty($Parameters["Post"]["message"]["message"]["media"]))
                    {
                        $Http = new Http();
                        $DynamicResource = new DynamicResource();
                        $DynamicResource->SetUserId(Authentication::GetAuthUser()->GetId());
                        $DynamicResource->SetResource($Http->SendGet($Parameters["Post"]["message"]["message"]["media"]));
                        $DynamicResource->SetType($Http->GetHeaders()['content-type']);
                        $DynamicResource->Save();
                    }

                    switch($Parameters["Post"]["message"]["message"]["type"])
                    {
                        case 'text':
                            $Message->SetText($Parameters["Post"]["message"]["message"]["text"]);
                            break;

                        case 'file':
                            $Message->SetDocument($DynamicResource->GetUid());
                            $Message->SetCaption($Parameters["Post"]["message"]["message"]["text"]);
                            break;

                        case 'picture':
                            $Message->SetImg($DynamicResource->GetUid());
                            $Message->SetCaption($Parameters["Post"]["message"]["message"]["text"]);
                            break;

                        case 'video':
                            $Message->SetVideo($DynamicResource->GetUid());
                            $Message->SetCaption($Parameters["Post"]["message"]["message"]["text"]);
                            break;

                        default:
                            $Message->SetText("[Нераспознанное сообщение]");
                            break;
                    }
                    try
                    {  
                        (new MessageController())->SendMessage($Dialog, $Message);
                    }
                    catch(Exception $error) {}
                    if(empty($AmoCRM->GetBotClientPhone($Whatsapp->GetId(), $Phone)))
                    {
                        $AmoCRM->SetBotSettings([
                            $Whatsapp->GetId() =>
                            [
                                $Phone =>
                                [
                                    "conversation_id" => $Parameters["Post"]["message"]["conversation"]["id"],
                                    "receiver_id" => $Parameters["Post"]["message"]["receiver"]["id"],
                                    "sender_id" => $Parameters["Post"]["message"]["sender"]["id"]
                                ]
                            ]
                        ]);
                    }
                    $AmoCRM->Save();

                    PrintJson::OperationSuccessful();
                }
            }
        }
        else
            PrintJson::OperationError(AmoCRMError, NOT_FOUND);
    }

    

    public function ActionGetPipelines(array $Parameters)
    {
        if(!empty($Find = AmoCRMIntegration::FindByUserId()))
        {
            $pipelines = AmoCurl::SendHeaderToAmoCRM([
                'Authorization: Bearer ' . $Find->GetAccessToken()
            ], 'https://' . $Find->GetProfileURL() . '/api/v4/leads/pipelines');

            if(empty($pipelines['_embedded']))
            {
                $this->GetNewTokens($Find);
                $this->ActionGetPipelines($Parameters);
            }

            if(is_array($pipelines))
            {
                foreach ($pipelines['_embedded']['pipelines'] as $statusPipelines)
                {
                    $Out[] = ['funnel_id' => $statusPipelines['id'], 'name' => $statusPipelines['name'], 'columns' => $this->GetStatuses($statusPipelines['id'])];
                }
                PrintJson::OperationSuccessful(["funnels" => $Out]);
            }
        }
        else
            PrintJson::OperationError(AmoCRMError, NOT_FOUND);
    }

    private function GetStatuses(int $PipelineId)
    {
        if(!empty($Find = AmoCRMIntegration::FindByUserId()))
        {
            $statuses = AmoCurl::SendHeaderToAmoCRM([
                'Authorization: Bearer ' . $Find->GetAccessToken()
            ], 'https://' . $Find->GetProfileURL() . '/api/v4/leads/pipelines/' . $PipelineId .'/statuses');

            if(is_array($statuses))
            {
                foreach($statuses['_embedded']['statuses'] as $status)
                {
                    $Out[] = ['column_uid' => (string)$status['id'], 'name' => $status['name']];
                }
                return $Out;
            }
        }
        else
            PrintJson::OperationError(AmoCRMError, NOT_FOUND);
        
    }


    public function ActionGetAllTask(array $Parameters)
    {
        $AmoCRM = AmoCRMIntegration::FindByUserId();
        if(!empty($AmoCRM))
        {
            $Id = [];
            $FunnelAction = $AmoCRM->GetFunnelActions();
            $NewDialog = $AmoCRM->GetNewDialogAction();
            if(!empty($FunnelAction))
            {
                foreach($FunnelAction as $key => $obj)
                {
                    foreach($obj as $id => $funnels)
                    {
                        if($key == "WhatsApp")
                        {
                            $Id[] = [
                                "id"            => $id,
                                "type"          => "whatsapp"
                            ];
                        }
                        else if($key == "Instagram")
                        {
                            $Id[] = [
                                "instagram"     => $id,
                                "type"          => "instagram"
                            ];
                        }
                    }
                }
            }
            else if(!empty($NewDialog))
            {
                foreach($NewDialog as $key => $obj)
                {
                    foreach($obj as $id => $funnels)
                    {
                        if($key == "WhatsApp")
                        {
                            $Id[] = [
                                "id"            => $id,
                                "type"          => "whatsapp"
                            ];
                        }
                        else if($key == "Instagram")
                        {
                            $Id[] = [
                                "instagram"     => $id,
                                "type"          => "instagram"
                            ];
                        }
                    }
                }
            }
            
            PrintJson::OperationSuccessful([
                "amocrm_integration" => [
                    "amocrm_integration_id" => $AmoCRM->GetId(),
                    "id"                    => $Id
                ]
            ]);
        }
    }


    public function ActionDeleteTask(array $Parameters)
    {
        if(Validator::IsValid($Parameters["Post"], [
            ["Key" => "id"],
            ["Key" => "type"]
        ]))
        {
            $AmoCRM = AmoCRMIntegration::FindByUserId();
            if(!empty($AmoCRM))
            {
                $FunnelActions = $AmoCRM->GetFunnelActions();
                foreach($FunnelActions as $key => $obj)
                {
                    foreach($obj as $id => $funnels)
                    {
                        for($i = 0; $i < count($obj); $i++)
                        {
                            if($Parameters["Post"]["type"] == "whatsapp")
                            {
                                if($key == "WhatsApp" && $Parameters["Post"]["id"] == $id)
                                    unset($FunnelActions["WhatsApp"][$id]);
                            }
                            else if($Parameters["Post"]["type"] == "instagram")
                            {
                                if($key == "Instagram" && $Parameters["Post"]["id"] == $id)
                                    unset($FunnelActions["Instagram"][$id]);
                            }
                        }
                    }
                }
                $NewDialog = $AmoCRM->GetNewDialogAction();
                foreach($AmoCRM->GetNewDialogAction() as $key => $id)
                {
                    for($i = 0; $i < count($id); $i++)
                    {
                        if($Parameters["Post"]["type"] == "whatsapp")
                        {
                            if($key == "WhatsApp" && $Parameters["Post"]["id"] == key($id))
                                unset($NewDialog["WhatsApp"][key($id)]);
                        }
                        else if($Parameters["Post"]["type"] == "instagram")
                        {
                            if($key == "Instagram" && $Parameters["Post"]["id"] == key($id))
                                unset($NewDialog["Instagram"][key($id)]);
                        }
                    }
                }
                $AmoCRM->SetFunnelActions($FunnelActions);
                $AmoCRM->SetNewDialogAction($NewDialog);
                $AmoCRM->Save();
                PrintJson::OperationSuccessful();
            }
        }
    }


    public function ActionGetAmoCRMIntegrationById(array $Parameters)
    {
        if(Validator::IsValid($Parameters['Get'], [
            ["Key" => "id",     "Type" => "int"],
            ["Key" => "type",   "Type" => "string"]
        ]))
        {
            $AmoCRM = AmoCRMIntegration::FindByUserId();
            if(!empty($AmoCRM))
            {
                $FunnelActions = [];
                foreach($AmoCRM->GetFunnelActions() as $key => $obj)
                {
                    foreach($obj as $id => $funnels)
                    {
                        foreach($funnels as $funnel => $statuses)
                        {
                            foreach($statuses as $status => $message)
                            {
                                if($key == "WhatsApp" && $Parameters['Get']['type'] == "whatsapp" && $Parameters['Get']['id'] == $id)
                                {
                                    $FunnelActions[] = [
                                        "id"            => $id,
                                        "type"          => "whatsapp",
                                        "funnel_id"     => $funnel,
                                        "column_uid"    => (string)$status,
                                        "message"       => MessageController::MessageToArray(Message::CreateMessageObj($message)),
                                        "folder_id"     => $AmoCRM->GetFunnelActions()["Folder"][$id][$funnel][$status]["folder_id"]
                                    ];
                                }
                                else if($key == "Instagram" && $Parameters['Get']['type'] == "instagram" && $Parameters['Get']['id'] == $id)
                                {
                                    $FunnelActions[] = [
                                        "id"            => $id,
                                        "type"          => "instagram",
                                        "funnel_id"     => $funnel,
                                        "column_uid"    => (string)$status,
                                        "message"       => MessageController::MessageToArray(Message::CreateMessageObj($message)),
                                        "folder_id"     => $AmoCRM->GetFunnelActions()["Folder"][$id][$funnel][$status]["folder_id"]
                                    ];
                                }
                            }
                        }
                    }
                }
                $NewDialog = [];
                foreach($AmoCRM->GetNewDialogAction() as $key => $id)
                {
                    foreach($id as $Parameter)
                    {
                        if($key == "WhatsApp" && $Parameters['Get']['type'] == "whatsapp" && $Parameters['Get']['id'] == key($id))
                        {
                            $NewDialog = [
                                "id"            => key($id),
                                "type"          => "whatsapp",
                                "funnel_id"     => $Parameter["FunnelId"],
                                "column_uid"    => (string)$Parameter["ColumnUid"]
                            ];
                        }
                        else if($key == "Instagram" && $Parameters['Get']['type'] == "instagram" && $Parameters['Get']['id'] == key($id))
                        {
                            $NewDialog = [
                                "id"            => key($id),
                                "type"          => "instagram",
                                "funnel_id"     => $Parameter["FunnelId"],
                                "column_uid"    => (string)$Parameter["ColumnUid"]
                            ];
                        }     
                    }
                }
                PrintJson::OperationSuccessful([
                    "amocrm_integration" => [
                        "amocrm_integration_id" => $AmoCRM->GetId(),
                        "is_active"             => $AmoCRM->IsActive(),
                        "new_dialog_action"     => empty($NewDialog) ? null : $NewDialog,
                        "funnel_actions"        => empty($FunnelActions) ? null : $FunnelActions,
                    ]
                ]);
            }
            else
                PrintJson::OperationError(AmoCRMError, NOT_FOUND);
        }
    }


    public function ActionGetAmoCRMIntegration(array $Parameters)
    {
        $AmoCRM = AmoCRMIntegration::FindByUserId();
        if(!empty($AmoCRM))
        {
            $FunnelActions = [];
            foreach($AmoCRM->GetFunnelActions() as $key => $obj)
            {
                foreach($obj as $id => $funnels)
                {
                    foreach($funnels as $funnel => $statuses)
                    {
                        foreach($statuses as $status => $message)
                        {
                            if($key == "WhatsApp")
                            {
                                $FunnelActions[] = [
                                    "id"            => $id,
                                    "type"          => "whatsapp",
                                    "funnel_id"     => $funnel,
                                    "column_uid"    => (string)$status,
                                    "message"       => MessageController::MessageToArray(Message::CreateMessageObj($message)),
                                    "folder_id"     => $AmoCRM->GetFunnelActions()["Folder"][$id][$funnel][$status]["folder_id"]
                                ];
                            }
                            else if($key == "Instagram")
                            {
                                $FunnelActions[] = [
                                    "instagram"     => $id,
                                    "type"          => "instagram",
                                    "funnel_id"     => $funnel,
                                    "column_uid"    => (string)$status,
                                    "message"       => MessageController::MessageToArray(Message::CreateMessageObj($message)),
                                    "folder_id"     => $AmoCRM->GetFunnelActions()["Folder"][$id][$funnel][$status]["folder_id"]
                                ];
                            }
                        }
                    }
                }
            }
            $NewDialog = [];
            foreach($AmoCRM->GetNewDialogAction() as $key => $id)
            {
                foreach($id as $Parameters)
                {
                    if($key == "WhatsApp")
                    {
                        $NewDialog = [
                            "id"            => key($id),
                            "type"          => "whatsapp",
                            "funnel_id"     => $Parameters["FunnelId"],
                            "column_uid"    => (string)$Parameters["ColumnUid"]
                        ];
                    }
                    else if($key == "Instagram")
                    {
                        $NewDialog = [
                            "id"            => key($id),
                            "type"          => "instagram",
                            "funnel_id"     => $Parameters["FunnelId"],
                            "column_uid"    => (string)$Parameters["ColumnUid"]
                        ];
                    }     
                }
            }
            PrintJson::OperationSuccessful([
                "amocrm_integration" => [
                    "amocrm_integration_id" => $AmoCRM->GetId(),
                    "is_active"             => $AmoCRM->IsActive(),
                    "new_dialog_action"     => empty($NewDialog) ? null : $NewDialog,
                    "funnel_actions"        => empty($FunnelActions) ? null : $FunnelActions,
                ]
            ]);
        }
        else
            PrintJson::OperationError(AmoCRMError, NOT_FOUND);
    }


    public function ActionUpdateAmoCRMSettings(array $Parameters)
    {
        if(Validator::IsValid($Parameters['Post'], [
            ["Key" => "amocrm_integration_id", "Type" => "int"],
            ["Key" => "funnel_actions",        "Type" => "array",    "IsNull" => true],
            ["Key" => "new_dialog_action",     "Type" => "array",    "IsNull" => true],
        ]))
        {

            $AmoCRM = AmoCRMIntegration::FindById($Parameters['Post']['amocrm_integration_id']);

            if(!empty($AmoCRM))
            {
                if(!empty($Parameters['Post']['new_dialog_action']))
                {
                    if(Validator::IsValid($Parameters['Post']['new_dialog_action'], [
                        ["Key" => "funnel_id", "Type" => "int"],
                        ["Key" => "id", "Type" => "int"],
                        ["Key" => "type"],
                        ["Key" => "column_uid"]
                    ]))
                    {
                        if($Parameters['Post']['new_dialog_action']['type'] == "whatsapp")
                            $NewDialog['WhatsApp'][$Parameters['Post']['new_dialog_action']['id']] = ["FunnelId" => $Parameters['Post']['new_dialog_action']['funnel_id'], "ColumnUid" => $Parameters['Post']['new_dialog_action']['column_uid']];
                        else if($Parameters['Post']['new_dialog_action']['type'] == "instagram")
                            $NewDialog['Instagram'][$Parameters['Post']['new_dialog_action']['id']] = ["FunnelId" => $Parameters['Post']['new_dialog_action']['funnel_id'], "ColumnUid" => $Parameters['Post']['new_dialog_action']['column_uid']];
                        $AmoCRM->SetNewDialogAction($NewDialog);
                    }
                    else
                        return;
                }
                else
                    $AmoCRM->SetNewDialogAction([]);

                if(!empty($Parameters['Post']['funnel_actions']))
                {
                    $Actions = $AmoCRM->GetFunnelActions();

                    foreach($Parameters['Post']['funnel_actions'] as $obj)
                    {
                        if(Validator::IsValid($obj, [
                            ["Key" => "funnel_id", "Type" => "int"],
                            ["Key" => "column_uid"],
                            ["Key" => "folder_id", "IsNull" => true],
                            ["Key" => "id", "Type" => "int"],
                            ["Key" => "type"],
                            ["Key" => "message", "Type" => "array"]
                        ]))
                        { 
                            $Message = new Message();
                            $Message->SetIsMe(true);
                            $Message->SetTime(time());

                            try
                            {
                                $Message->SetContent($obj["message"]["type"], $obj["message"]["data"], $obj["message"]["caption"]);
                            }
                            catch(Exception $error)
                            {
                                PrintJson::OperationError(OperationError, REQUEST_FAILED);
                                return;
                            }
                            if($obj['type'] == "whatsapp")
                                $Actions['WhatsApp'][$obj['id']][$obj['funnel_id']][$obj['column_uid']] = $Message->ToArray();
                            if($obj['type'] == "instagram")
                                $Actions['Instagram'][$obj['id']][$obj['funnel_id']][$obj['column_uid']] = $Message->ToArray();
                            
                            $Actions["Folder"][$obj['id']][$obj['funnel_id']][$obj['column_uid']]["folder_id"] = $obj['folder_id'];

                        }
                        else
                            return;
                    }

                    $AmoCRM->SetFunnelActions($Actions);
                }
                else
                    $AmoCRM->SetFunnelActions([]);

                $AmoCRM->Save();
                PrintJson::OperationSuccessful();
            }
            else
                PrintJson::OperationError(AmoCRMError, NOT_FOUND);
        }
    }


    public function ActionDeleteIntegration(array $Parameters)
    {
        if(!empty($Find = AmoCRMIntegration::FindByUserId()))
        {
            $Find->Delete();
            PrintJson::OperationSuccessful();
        }
        else
            PrintJson::OperationError(AmoCRMError, NOT_FOUND);
    }

}
?>