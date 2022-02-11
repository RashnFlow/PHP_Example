<?php


namespace controllers;

use classes\Tag;
use classes\Validator;
use Exception;
use factories\UserFactory;
use models\Authentication;
use models\BitrixIntegration;
use models\dialogues\Dialog;
use models\Instagram;
use models\dialogues\InstagramDialog;
use models\Message;
use views\PrintJson;
use models\User;
use models\Whatsapp;
use models\dialogues\WhatsappDialog;
use sdk\php\crest\CRest;


class BitrixIntegrationController
{
    static public function CheckAccess(?string $Token) : bool
    {
        if(md5($Token) == BITRIX_API_KEY)
            return true;
        else
            throw new Exception("Access is denied", ACCESS_DENIED);
    }


    public function ActionInstallApp(array $Parameters)
    {
        Authentication::SetAuthUser(UserFactory::GetSystemUser(), SYSTEM_SESSION);
            
        $BitrixIntegration = BitrixIntegration::FindByProfileUrl($_REQUEST["DOMAIN"]);
        if(empty($BitrixIntegration))
            $BitrixIntegration = new BitrixIntegration();

        $CRest = new CRest($BitrixIntegration);
        $link = DOMAIN_API_URL . "/" . BITRIX_EVENT_URL;
        $CRest->installApp();
        $CRest->Call('event.bind', [
            'event' => 'onCrmDealAdd',
            'handler' => $link . '/deal/add?token=' . BITRIX_API_TOKEN
        ]);
        $CRest->Call('event.bind', [
            'event' => 'onCrmDealUpdate',
            'handler' => $link . '/deal/update?token=' . BITRIX_API_TOKEN
        ]);
        PrintJson::OperationSuccessful();
    }


    public function OnMessage(Dialog $Dialog)
    {
        if(!$Dialog->IsNew())
            return;

        $BitrixIntegration = BitrixIntegration::FindByUserId();
        if(empty($BitrixIntegration))
            return;
        
        if(empty($BitrixIntegration->GetNewDialogAction()))
            return;

        $Request = [
            "NAME"              => $Dialog->GetName(),
            "OPENED"            => "Y",
            "TYPE_ID"           => "CLIENT",
            "SOURCE_ID"         => "SELF"
        ];

        $ClassName = get_class($Dialog);
        switch($ClassName)
        {
            case WhatsappDialog::class:
                $Request["PHONE"] =  [ 
                    ["VALUE" => $Dialog->GetPhone(), "VALUE_TYPE" => "WORK"]
                ];
                break;

            case InstagramDialog::class:
                $Request["IM"] = [ 
                    ["VALUE" => $Dialog->GetLogin(), "VALUE_TYPE" => "INSTAGRAM"]
                ];
                break;
        }

        $CRest = new CRest($BitrixIntegration);
        $ContactId = $this->Call($CRest, "crm.contact.add", ["fields" => $Request]);
        $this->Call($CRest, "crm.deal.add", ["fields" => [
            "TITLE"             => "Сделка (FastLead)",
            "STAGE_ID"          => $BitrixIntegration->GetNewDialogAction()["ColumnUid"], 					
            "COMPANY_ID"        => 0,
            "CONTACT_ID"        => $ContactId,
            "OPENED"            => "Y", 
            "CURRENCY_ID"       => "RUB",
            "CATEGORY_ID"       => $BitrixIntegration->GetNewDialogAction()["FunnelId"]
        ]]);
    }


    private function Call(CRest $CRest, string $Command, array $Data = [])
    {
        static $counter;
        $Response = $CRest->call($Command, $Data);
        if(empty($Response["result"]))
        {
            $CRest->GetNewTokens();
            $counter++;
            if($counter > 5)
                throw new Exception("Error Call data: " . json_encode($Data) . "; response: " . json_encode($Response));
            $this->Call($CRest, $Command, $Data);
        } 
        return $Response["result"];
    }


    public function ActionWebhooks(array $Parameters)
    {
        Authentication::SetAuthUser(UserFactory::GetSystemUser(), SYSTEM_SESSION);
        
        $BitrixIntegration = BitrixIntegration::FindByProfileUrl($_REQUEST['auth']["domain"]);
        if(!empty($BitrixIntegration))
        {
            $CRest = new CRest($BitrixIntegration);
            $Deal = $this->Call($CRest, "crm.deal.get", ["id" => $_REQUEST['data']["FIELDS"]["ID"]]);

            switch($_REQUEST["event"])
            {
                case 'ONCRMDEALADD': // Создали
                case 'ONCRMDEALUPDATE': // Обновили
                    if($Deal["STAGE_ID"] != $BitrixIntegration->GetCache("STAGE_ID_" . $Deal["ID"]))
                    {
                        $BitrixIntegration->SetCache("STAGE_ID_" . $Deal["ID"], $Deal["STAGE_ID"]);
                        $BitrixIntegration->Save();
                        $MessArray = $BitrixIntegration->GetFunnelActions()[$Deal["CATEGORY_ID"]][$Deal["STAGE_ID"]];
                        if(!empty($MessArray))
                        {
                            $Message = Message::CreateMessageObj($MessArray);

                            $Contact = ($this->Call($CRest, "crm.contact.list", [
                                'filter' => [
                                    "ID" => $Deal["CONTACT_ID"]
                                ],
                                'select' => [
                                    "PHONE",
                                    "NAME",
                                    "IM"
                                ]
                            ]))[0];
                            
                            if(empty($Contact["IM"]))
                                $Contact["IM"] = [];
                            
                            if(!empty($Contact["PHONE"][0]))
                            {
                                $Contact["PHONE"][0]["VALUE_TYPE"] = 'MOBILE';
                                array_push($Contact["IM"], $Contact["PHONE"][0]);
                            }

                            foreach($Contact["IM"] as $obj)
                            {
                                $Dialog = null;
                                switch($obj["VALUE_TYPE"])
                                {
                                    case 'MOBILE':
                                        $Whatsapp = Whatsapp::FindAllByUserId($BitrixIntegration->GetUserId())[0];
                                        if(!empty($Whatsapp))
                                        {
                                            $Dialog = WhatsappDialog::FindByPhoneAndWhatsappId(Validator::NormalizePhone($obj["VALUE"]), $Whatsapp->GetId());
                                            if(empty($Dialog))
                                                $Dialog = new WhatsappDialog();
                                            $Dialog->SetPhone(Validator::NormalizePhone($obj["VALUE"]));
                                            $Dialog->SetWhatsappId($Whatsapp->GetId());
                                        }
                                        break;

                                    case 'INSTAGRAM':
                                        $Instagram = Instagram::FindAllByUserId($BitrixIntegration->GetUserId())[0];
                                        if(!empty($Instagram))
                                        {
                                            $Dialog = InstagramDialog::FindByLoginAndInstagramId($obj["VALUE"], $Instagram->GetId());
                                            if(empty($Dialog))
                                                $Dialog = new InstagramDialog();
                                            $Dialog->SetLogin($obj["VALUE"]);
                                            $Dialog->SetInstagramId($Instagram->GetId());
                                        }
                                        break;
                                }

                                if(!empty($Dialog))
                                {
                                    if(!empty($Contact["NAME"]))
                                        $Dialog->SetName($Contact["NAME"]);
                                    else
                                        $Dialog->SetName($obj["VALUE"]);

                                    $Dialog->Save();
                                    try
                                    {
                                        if($Message->GetType() == Message::MESSAGE_TYPE_TEXT)
                                        {
                                            $Message->SetText(Tag::ReplaceTag(["clientName" => $Contact["NAME"],
                                                                               "clientPhone" =>$Contact["PHONE"],], 
                                                                               $Message->GetText()));
                                        }
                                        (new MessageController())->SendMessage($Dialog, $Message);
                                    }
                                    catch(Exception $error) {}
                                }

                                PrintJson::OperationSuccessful();
                            }
                        }
                    }
                    break;

                default:
                    throw new Exception("Invalid event");
                break;
            }
        }
        else
            PrintJson::OperationError(BitrixNotFound, NOT_FOUND);
    }
    
    
    public function ActionConnectUserToBitrix(array $Parameters)
    {
        if(Validator::IsValid($Parameters['Post'], [
            ["Key" => "bitrix_url"]
        ]))
        {
            if (empty(BitrixIntegration::FindByUserId()))
            {
                $domain = parse_url($Parameters["Post"]["bitrix_url"], PHP_URL_HOST);
                $Find = BitrixIntegration::FindByProfileUrl($domain, false);

                if(!empty($Find))
                {
                    if(empty($Find->GetUserId()))
                    {
                        $Find->SetUserId((Authentication::GetAuthUser())->GetId());
                        $Find->Save();
                        PrintJson::OperationSuccessful();
                    }
                    else
                        PrintJson::OperationError(BitrixConnectionError, REQUEST_FAILED);
                }
                else
                    PrintJson::OperationError(BitrixNotFound, REQUEST_FAILED);
            }
            else  
                PrintJson::OperationError(IntegrationError, REQUEST_FAILED);
        }
    }


    public function ActionGetFunnels(array $Parameters)
    {
        $Find = BitrixIntegration::FindByUserId();

        if(!empty($Find))
        {
            if($Find->IsActive())
            {
                $CRest = new CRest($Find);
                $Out = [];
                $Response = $this->Call($CRest, 'crm.dealcategory.default.get');
                if(is_array($Response))
                    $Out[] = ["funnel_id" => (int)$Response["ID"], "name" => $Response["NAME"], "columns" => $this->GetFunnelColumns($CRest, (int)$Response["ID"])];

                foreach((new CRest($Find))->call('crm.dealcategory.list')["result"] as $data)
                    $Out[] = ["funnel_id" => (int)$data["ID"], "name" => $data["NAME"], "columns" => $this->GetFunnelColumns($CRest, (int)$data["ID"])];
                
                PrintJson::OperationSuccessful(["funnels" => $Out]);
            }
            else
                PrintJson::OperationError(NotActive, REQUEST_FAILED);
        }
        else
            PrintJson::OperationError(BitrixNotFound, NOT_FOUND);
    }


    private function GetFunnelColumns(CRest $CRest, int $FunnelId)
    {
        $Out = [];
        foreach($this->Call($CRest, 'crm.dealcategory.stage.list', ["id" => $FunnelId]) as $data)
            $Out[] = ["column_uid" => $data["STATUS_ID"], "name" => $data["NAME"]];
        
        return $Out;
    }


    public function ActionGetBitrixIntegration(array $Parameters)
    {
        $BitrixIntegration = BitrixIntegration::FindByUserId();

        if(!empty($BitrixIntegration))
        {
            $FunnelActions = [];
            foreach($BitrixIntegration->GetFunnelActions() as $key => $obj)
            {
                foreach($obj as $k => $o)
                {
                    $FunnelActions[] = [
                        "funnel_id"     => $key,
                        "column_uid"    => $k,
                        "message"       => MessageController::MessageToArray(Message::CreateMessageObj($o))
                    ];
                }
            }
            PrintJson::OperationSuccessful([
                "bitrix_integration" => [
                    "bitrix_integration_id" => $BitrixIntegration->GetId(),
                    "is_active"             => $BitrixIntegration->IsActive(),
                    "new_dialog_action"     => empty($BitrixIntegration->GetNewDialogAction()) ? null : Validator::ArrayKeyPascalCaseToSnakeCase($BitrixIntegration->GetNewDialogAction()),
                    "funnel_actions"        => empty($FunnelActions) ? null : $FunnelActions,
                ]
            ]);
        }
        else
            PrintJson::OperationError(BitrixNotFound, NOT_FOUND);
    }


    public function ActionUpdateBitrixIntegration(array $Parameters)
    {
        if(Validator::IsValid($Parameters['Post'], [
            ["Key" => "funnel_actions", "Type" => "array", "IsNull" => true],
            ["Key" => "new_dialog_action", "Type" => "array", "IsNull" => true]
        ]))
        {
            $BitrixIntegration = BitrixIntegration::FindByUserId();

            if(!empty($BitrixIntegration))
            {
                if(!empty($Parameters['Post']['new_dialog_action']))
                {
                    if(Validator::IsValid($Parameters['Post']['new_dialog_action'], [
                        ["Key" => "funnel_id", "Type" => "int"],
                        ["Key" => "column_uid"]
                    ]))
                    {
                        $BitrixIntegration->SetNewDialogAction([
                            "FunnelId" => $Parameters['Post']['new_dialog_action']['funnel_id'],
                            "ColumnUid" => $Parameters['Post']['new_dialog_action']['column_uid'],
                        ]);
                    }
                    else
                        return;
                }
                else
                    $BitrixIntegration->SetNewDialogAction([]);

                if(!empty($Parameters['Post']['funnel_actions']))
                {
                    $Actions = [];

                    foreach($Parameters['Post']['funnel_actions'] as $obj)
                    {
                        if(Validator::IsValid($obj, [
                            ["Key" => "funnel_id", "Type" => "int"],
                            ["Key" => "column_uid"],
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

                            $Actions[$obj['funnel_id']][$obj['column_uid']] = $Message->ToArray();
                        }
                        else
                            return;
                    }

                    $BitrixIntegration->SetFunnelActions($Actions);
                }
                else
                    $BitrixIntegration->SetFunnelActions([]);


                $BitrixIntegration->Save();

                PrintJson::OperationSuccessful();
            }
            else
                PrintJson::OperationError(BitrixNotFound, NOT_FOUND);
        }
    }


    public function ActionDeleteBitrixIntegration(array $Parameters)
    {
        $BitrixIntegration = BitrixIntegration::FindByUserId();

        if(!empty($BitrixIntegration))
        {
            $BitrixIntegration->Delete();
            PrintJson::OperationSuccessful();
        }
        else
            PrintJson::OperationError(BitrixNotFound, NOT_FOUND);
    }
}