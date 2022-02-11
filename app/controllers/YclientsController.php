<?php

namespace controllers;

use classes\Http;
use classes\Log;
use classes\Logger;
use classes\Tag;
use classes\Validator;
use DateTime;
use DateTimeZone;
use Exception;
use factories\UserFactory;
use models\Authentication;
use models\dialogues\Dialog;
use models\dialogues\InstagramDialog;
use models\Message;
use models\Whatsapp;
use models\dialogues\WhatsappDialog;
use models\Instagram;
use models\Yclients;
use models\YclientsRecords;
use models\YclientsTasks;
use views\PrintJson;

class YclientsController
{
    /*
     * URL для RestAPI
     */
    const URL = 'https://api.yclients.com/api/v1';

    /*
     * Методы используемые в API
     */
    const METHOD_GET = 'GET';
    const METHOD_POST = 'POST';
    const METHOD_PUT = 'PUT';
    const METHOD_DELETE = 'DELETE';

    const TypeTask = [
        "Birthday" => "Поздравление с днём рождения",
        "Review" => "Запрос отзыва после визита",
        "RecordRemindAtTime" => "Напоминание о записи в заданное время",
        "PostCreatedNotification" => "Уведомление о созданной записи",
        "PostChangedNotification" => "Уведомление об изменении записи",
        "PostDeleteNotification" => "Уведомление об удалении записи",
        "RevisitReminder" => "Напоминание о повторном визите",
        "CrossSelling" => "Кросс-продажи",
        "ModuleNotifications" => "Интеграция с модулем-уведомлений Yclients",
        "RecordRemind" => "Напоминание о записи"
    ];


    static public function CheckAccess(?string $Token) : bool
    {
        if(md5($Token) == YCLIENTS_API_KEY)
            return true;
        else
            throw new Exception("Access is denied", ACCESS_DENIED);
    }


    public static function requestCurl($url, $parameters = [], $method = 'GET', $headers = [], $timeout = 30)
    {
        $ch = curl_init();

        if (count($parameters)) {
            if ($method === self::METHOD_GET) {
                $url .= '?' . http_build_query($parameters);
            } else {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($parameters));
            }
        }

        if ($method === self::METHOD_POST) {
            curl_setopt($ch, CURLOPT_POST, true);
        } elseif ($method === self::METHOD_PUT) {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, self::METHOD_PUT);
        } elseif ($method === self::METHOD_DELETE) {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, self::METHOD_DELETE);
        }

        curl_setopt($ch, CURLOPT_URL, self::URL . '/' . $url);
        curl_setopt($ch, CURLOPT_FAILONERROR, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_HEADER, false);

        if (!empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        $response = curl_exec($ch);

        $errno = curl_errno($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($errno) {
            throw new Exception('Запрос произвести не удалось: ' . $error, $errno);
        }
        return json_decode($response, true);
    }


    /**
     * ActionConnectYclients
     * 
     * Подключение к Yclient.
     * 
     * @param array $Parameters Массив параметров содержайщий логин и пароль
     */
    public function ActionConnectYclients(array $Parameters)
    {
        if(Validator::IsValid($Parameters["Post"], [
            ["Key" => "login"],
            ["Key" => "password"]
        ]))
        {
            if(empty(Yclients::FindByLoginAndUserId($Parameters["Post"]["login"], (Authentication::GetAuthUser())->GetId())))
            {
                $Yclients = new Yclients();
                $Yclients->SetLogin($Parameters["Post"]["login"]);
                $Yclients->SetPassword($Parameters["Post"]["password"]);
                $Yclients->SetUserId((Authentication::GetAuthUser())->GetId());
                $response = $this->requestCurl('auth', 
                    [
                    'login' => $Parameters["Post"]["login"],
                    'password' => $Parameters["Post"]["password"]
                    ], 
                    'POST',  
                    [
                        'Authorization: Bearer ' . YCLIENTS_BEARER_KEY,
                        'Content-Type: application/json',
                        'Accept: application/vnd.yclients.v2+json'
                    ]);
                if(!$response["success"])
                {
                    PrintJson::OperationError(AuthError, REQUEST_FAILED);
                    return;
                }
                $Yclients->SetUserToken($response["data"]["user_token"]);
                $Yclients->Save();
                PrintJson::OperationSuccessful();
            }
            else
                PrintJson::OperationError(IntegrationError, REQUEST_FAILED);
        }
    }


    /**
     * ActionCreateNewTask
     * 
     * Создает и записывает в БД новую задачу по Yclient.
     * 
     * @param array $Parameters Массив параметров содержайщий тип задачи, ее имя и параметры
     */
    public function ActionCreateNewTask(array $Parameters)
    {
        if(Validator::IsValid($Parameters["Post"], [
            ["Key" => "type"],
            ["Key" => "task_name"],
            ["Key" => "parameters", "Type" => "array"],
        ]))
        {
            if(!empty($Yclients = Yclients::FindByUserId()))
            {
                $YclientsTask = new YclientsTasks();
                $YclientsTask->SetYclientsIntegrationId($Yclients->GetId());
                $YclientsTask->SetType($Parameters["Post"]["type"]);
                $YclientsTask->SetTaskName($Parameters["Post"]["task_name"]);
                $Message = new Message();
                $Message->SetIsMe(true);
                $Message->SetTime(time());
                try
                {
                    $Message->SetContent($Parameters["Post"]["parameters"][0]["message"]["type"], $Parameters["Post"]["parameters"][0]["message"]["data"], $Parameters["Post"]["parameters"][0]["message"]["caption"]);
                }
                catch(Exception $error)
                {
                    PrintJson::OperationError(OperationError, REQUEST_FAILED);
                    return;
                }
                $Parameters["Post"]["parameters"][0]["message"] = $Message->ToArray();
                $YclientsTask->SetParameters($Parameters["Post"]["parameters"]);
                switch($Parameters["Post"]["type"])
                {
                    case "PostCreatedNotification":
                    case "PostChangedNotification":
                    case "PostDeleteNotification" :
                        $this->requestCurl("hooks_settings/" . $Parameters["Post"]["parameters"][0]["company_id"],
                        [
                        "urls" => [
                            DOMAIN_API_URL . "/" . YCLIENTS_WEBHOOK . "?token=" . YCLIENTS_API_TOKEN
                        ],
                        "active" => 1,
                        "record" => 1
                        ], 
                        "POST",
                        [
                            "Authorization: Bearer " . YCLIENTS_BEARER_KEY . ", User " . $Yclients->GetUserToken(),
                            "Content-Type: application/json",
                            "Accept: application/vnd.yclients.v2+json"
                        ]);
                        break;
                    default:
                        break;
                }
                $YclientsTask->Save();
                PrintJson::OperationSuccessful();
            }
        }
    }


    /**
     * ActionUpdateTask
     * 
     * Получение задачи по Yclient.
     * 
     * @param array $Parameters Массив параметров содержайщий id задачи по Ycient, ее имя и параметры
     */
    public function ActionUpdateTask(array $Parameters)
    {
        if(Validator::IsValid($Parameters["Post"], [
            ["Key" => "task_id"],
            ["Key" => "task_name"],
            ["Key" => "parameters", "Type" => "array"],
        ]))
        {
            if(!empty($YclientsTask = YclientsTasks::FindById($Parameters["Post"]["task_id"])))
            {
                $YclientsTask->SetTaskName($Parameters["Post"]["task_name"]);
                $Message = new Message();
                $Message->SetIsMe(true);
                $Message->SetTime(time());
                try
                {
                    $Message->SetContent($Parameters["Post"]["parameters"][0]["message"]["type"], $Parameters["Post"]["parameters"][0]["message"]["data"], $Parameters["Post"]["parameters"][0]["message"]["caption"]);
                }
                catch(Exception $error)
                {
                    PrintJson::OperationError(OperationError, REQUEST_FAILED);
                    return;
                }
                $Parameters["Post"]["parameters"][0]["message"] = $Message->ToArray();
                $YclientsTask->SetParameters($Parameters["Post"]["parameters"]);
                $YclientsTask->Save();
                PrintJson::OperationSuccessful();
            }
            else
                PrintJson::OperationError(TaskError, NOT_FOUND);
        }
    }


    /**
     * ActionGetTask
     * 
     * Получение всех задач по Yclient.
     */
    public function ActionGetAllTask()
    {
        $Yclient = Yclients::FindByUserId();
        if (!empty($Tasks = YclientsTasks::FindAllByYclientId($Yclient->GetId())))
        {
            $Out = [];
            foreach ($Tasks as $Task)
            {
                $Parameters = $Task->GetParameters();
                $Parameters[0]['message'] = MessageController::MessageToArray(Message::CreateMessageObj($Parameters[0]['message']));
                $NewParameters = $Task->GetParameters();
                $NewParameters[0]['message']['file_name'] = $Parameters[0]['message']['file_name'];
                $Out["yclients_task"][] = [
                    "task_id"                   => $Task->GetId(),
                    "yclients_integration_id"   => $Task->GetYclientsIntegrationId(),
                    "type"                      => self::TypeTask[$Task->GetType()],
                    "task_name"                 => $Task->GetTaskName(),
                    "parameters"                => $NewParameters,
                ];
            }
            PrintJson::OperationSuccessful($Out);
        }
        else
            PrintJson::OperationError(TaskError, NOT_FOUND);
    }


    /**
     * ActionGetTask
     * 
     * Получение задачи по Yclient.
     * 
     * @param array $Parameters Массив параметров содержайщий id задачи по Ycient
     */
    public function ActionGetTask(array $Parameters)
    {
        if(Validator::IsValid($Parameters["Get"], [
            ["Key" => "task_id"]
        ]))
        {
           if(!empty($YclientsTask = YclientsTasks::FindById($Parameters["Get"]["task_id"])))
           {
               $Parameters = $YclientsTask->GetParameters();
               $Parameters[0]['message'] = MessageController::MessageToArray(Message::CreateMessageObj($Parameters[0]['message']));
                PrintJson::OperationSuccessful([
                    "yclients_task" => [
                        "task_id"                   => $YclientsTask->GetId(),
                        "yclients_integration_id"   => $YclientsTask->GetYclientsIntegrationId(),
                        "type"                      => $YclientsTask->GetType(),
                        "task_name"                 => $YclientsTask->GetTaskName(),
                        "parameters"                => $Parameters,
                    ]
                ]);
           }
           else
                PrintJson::OperationError(TaskError, NOT_FOUND);
        }
    }


    /**
     * ActionGetMasters
     * 
     * Получение id и имена мастеров в Yclient.
     * 
     * @param array $Parameters Массив параметров содержайщий id филиала из Ycient
     */
    public function ActionGetMasters(array $Parameters)
    {
        if(Validator::IsValid($Parameters["Post"], [
            ["Key" => "company_id"]
        ]))
        {
            if(!empty($Yclients = Yclients::FindByUserId()))
            {
                $Masters = YclientsController::requestCurl("company/" . $Parameters["Post"]["company_id"] . "/staff",
                [],
                'GET',
                [
                    "Authorization: Bearer " . YCLIENTS_BEARER_KEY . ", User " . $Yclients->GetUserToken(),
                    "Content-Type: application/json",
                    "Accept: application/vnd.yclients.v2+json"
                ]);

                if($Masters["success"])
                {
                    $Out = [];
                    foreach($Masters["data"] as $Master)
                        $Out["Masters"][] = ["id" => $Master["id"], "name" => $Master["name"]];
                    PrintJson::OperationSuccessful($Out);
                }
                else
                    PrintJson::OperationError(OperationError, REQUEST_FAILED);
            }
            else
                PrintJson::OperationError(AmoCRMError, ACCESS_DENIED);
        }
    }


    /**
     * ActionGetClientsCategory
     * 
     * Получение id и названия категорий клиентов в Yclient.
     * 
     * @param array $Parameters Массив параметров содержайщий id филиала из Ycient
     */
    public function ActionGetClientsCategory(array $Parameters)
    {
        if(Validator::IsValid($Parameters["Post"], [
            ["Key" => "company_id"]
        ]))
        {
            if(!empty($Yclients = Yclients::FindByUserId()))
            {
                $ClientsCategory = YclientsController::requestCurl("labels/" . $Parameters["Post"]["company_id"] . "/clients",
                [],
                'GET',
                [
                    "Authorization: Bearer " . YCLIENTS_BEARER_KEY . ", User " . $Yclients->GetUserToken(),
                    "Content-Type: application/json",
                    "Accept: application/vnd.yclients.v2+json"
                ]);

                if($ClientsCategory["success"])
                {
                    $Out = [];
                    foreach($ClientsCategory["data"] as $ClientCategory)
                        $Out["ClientsCategory"][] = ["id" => $ClientCategory["id"], "name" => $ClientCategory["title"]];
                    PrintJson::OperationSuccessful($Out);
                }
                else
                    PrintJson::OperationError(OperationError, REQUEST_FAILED);
            }
            else
                PrintJson::OperationError(AmoCRMError, ACCESS_DENIED);
        }
    }


    /**
     * ActionGetRecordsCategory
     * 
     * Получение категорий записи в Yclient.
     * 
     * @param array $Parameters Массив параметров содержайщий id филиала из Ycient
     */
    public function ActionGetRecordsCategory(array $Parameters)
    {
        if(Validator::IsValid($Parameters["Post"], [
            ["Key" => "company_id"]
        ]))
        {
            if(!empty($Yclients = Yclients::FindByUserId()))
            {
                $RecordsCategory = YclientsController::requestCurl("labels/" . $Parameters["Post"]["company_id"] . "/2",
                [],
                'GET',
                [
                    "Authorization: Bearer " . YCLIENTS_BEARER_KEY . ", User " . $Yclients->GetUserToken(),
                    "Content-Type: application/json",
                    "Accept: application/vnd.yclients.v2+json"
                ]);

                if($RecordsCategory["success"])
                {
                    $Out = [];
                    foreach($RecordsCategory["data"] as $RecordCategory)
                        $Out["ClientsCategory"][] = ["id" => $RecordCategory["id"], "name" => $RecordCategory["title"]];
                    PrintJson::OperationSuccessful($Out);
                }
                else
                    PrintJson::OperationError(OperationError, REQUEST_FAILED);
            }
            else
                PrintJson::OperationError(AmoCRMError, ACCESS_DENIED);
        }
    }


    /**
     * ActionGetServices
     * 
     * Получение id и названия предоставляемых услуг в Yclient.
     * 
     * @param array $Parameters Массив параметров содержайщий id филиала из Ycient
     */
    public function ActionGetServices(array $Parameters)
    {
        if(Validator::IsValid($Parameters["Post"], [
            ["Key" => "company_id"]
        ]))
        {
            if(!empty($Yclients = Yclients::FindByUserId()))
            {
                $Services = YclientsController::requestCurl("company/" . $Parameters["Post"]["company_id"] . "/services",
                [],
                'GET',
                [
                    "Authorization: Bearer " . YCLIENTS_BEARER_KEY . ", User " . $Yclients->GetUserToken(),
                    "Content-Type: application/json",
                    "Accept: application/vnd.yclients.v2+json"
                ]);

                $ServicesCategory = YclientsController::requestCurl("company/" . $Parameters["Post"]["company_id"] . "/service_categories",
                [],
                'GET',
                [
                    "Authorization: Bearer " . YCLIENTS_BEARER_KEY . ", User " . $Yclients->GetUserToken(),
                    "Content-Type: application/json",
                    "Accept: application/vnd.yclients.v2+json"
                ]);

                if($Services["success"] && $ServicesCategory["success"])
                {
                    $Out = [];
                    $Category = [];
                    foreach($ServicesCategory["data"] as $ServiceCategory)
                    {
                        foreach($Services["data"] as $Service)
                            if($ServiceCategory["id"] == $Service["category_id"])
                                $Category[] = ["id" => $Service["id"], "name" => $Service["title"]];
                        $Out[] = ["name" => $ServiceCategory["title"], 'id' => $ServiceCategory["id"], 'services' => $Category];
                    }
                    
                    PrintJson::OperationSuccessful(['filters' => $Out]);
                }
                else
                    PrintJson::OperationError(OperationError, REQUEST_FAILED);
            }
            else
                PrintJson::OperationError(AmoCRMError, ACCESS_DENIED);
        }
    }


    /**
     * ActionDeleteTask
     * 
     * Удаление задачи по Yclient.
     * 
     * @param array $Parameters Массив параметров содержайщий id задачи
     */
    public function ActionDeleteTask(array $Parameters)
    {
        if(Validator::IsValid($Parameters["Post"], [
            ["Key" => "task_id"]
        ]))
        {
            if(!empty($YclientsTask = YclientsTasks::FindById($Parameters["Post"]["task_id"])))
            {
                $YclientsTask->Delete();
                PrintJson::OperationSuccessful();
            }
        }
    }


    public function ActionYclientsWebhook()
    {
        Authentication::SetAuthUser(UserFactory::GetSystemUser(), SYSTEM_SESSION);

        if(!Validator::IsValid($_POST["data"]["client"], [
            ["Key" => "phone", "StrMin" => 1]
        ]))
            return;

        if(!empty($YclientsTasks = YclientsTasks::FindAll()))
        {
            foreach($YclientsTasks as $YclientsTask)
            {
                try
                {
                    switch($YclientsTask->GetType())
                    {
                        case "PostCreatedNotification":
                            if($_POST["resource"] == "record" && $_POST["status"] == "create" && $_POST["company_id"] == $YclientsTask->GetParameters()[0]["company_id"])
                            {
                                $Parameters = $YclientsTask->GetParameters();
                                $Yclients = Yclients::FindById($YclientsTask-> GetYclientsIntegrationId());
                                $YclientsRecord = new YclientsRecords();
                                $YclientsRecord->RecordId = $_POST["data"]["id"];
                                $YclientsRecord->UserId = $Yclients->GetUserId();
                                $YclientsRecord->RecordDate = $_POST["data"]["date"];
                                $YclientsRecord->MasterId = $_POST["data"]["staff"]["id"];
                                $YclientsRecord->RecordStatus = $_POST["data"]["visit_attendance"];
                                if(!empty($_POST["data"]["record_labels"]))
                                {   $RecordCategory = [];
                                    foreach($_POST["data"]["record_labels"] as $RecordLabels)
                                        $RecordCategory[] = ["id" => $RecordLabels["id"]];

                                    $YclientsRecord->RecordCategory = $RecordCategory;
                                }
                                if(!empty($_POST["data"]["services"]))
                                {
                                    $RecordServices = [];
                                    foreach($_POST["data"]["services"] as $Services)
                                        $RecordServices[] = ["id" => $Services["id"]];

                                    $YclientsRecord->RecordServices = $RecordServices;
                                }
                                $YclientsRecord->Save();
                            }
                            break;
                        case "PostChangedNotification":
                            if($_POST["resource"] == "record" && $_POST["status"] == "update" && $_POST["company_id"] == $YclientsTask->GetParameters()[0]["company_id"])
                            {
                                if(!empty($YclientsRecord = YclientsRecords::FindByRecordId($_POST["data"]["id"])) && $YclientsRecord->RecordDate != $_POST["data"]["date"])
                                {
                                    $Parameters = $YclientsTask->GetParameters();
                                    $YclientsRecord->RecordDate = $_POST["data"]["date"];
                                    $YclientsRecord->Save();
                                }
                            }
                            break;
                        case "PostDeleteNotification":
                            if($_POST["resource"] == "record" && $_POST["status"] == "delete" && $_POST["company_id"] == $YclientsTask->GetParameters()[0]["company_id"])
                                $Parameters = $YclientsTask->GetParameters();
                            break;
                    }
                    if(!empty($Parameters))
                    {
                        $Message = Message::CreateMessageObj($Parameters[0]["message"]);
                        $Message->SetSource("Yclients");
                        //FixDialogues
                        if(!empty($Parameters[0]["whatsapp_id"]))
                        {
                            $Whatsapp = Whatsapp::FindById($Parameters[0]["whatsapp_id"]);
                            $Dialog = WhatsappDialog::FindByPhoneAndWhatsappId(Validator::NormalizePhone($_POST["data"]["client"]["phone"]), $Whatsapp->GetId());
                            if(empty($Dialog))
                                $Dialog = new WhatsappDialog();
                            $Dialog->SetWhatsappId($Whatsapp->GetId());
                            $Dialog->SetPhone(Validator::NormalizePhone($_POST["data"]["client"]["phone"]));
                        }
                        else if(!empty($Parameters[0]["instagram_id"]))
                        {
                            //Login?
                            $Instagram = Instagram::FindById($Parameters[0]["instagram_id"]);
                            $Dialog = InstagramDialog::FindByLoginAndInstagramId(Validator::NormalizePhone($_POST["data"]["client"]["phone"]), $Instagram->GetId());
                            if(empty($Dialog))
                                $Dialog = new InstagramDialog();
                            $Dialog->SetLogin($Instagram->GetId());
                        }
                        if(!empty($Whatsapp) || !empty($Instagram) || !empty($_POST["data"]["client"]["phone"]))
                        {
                            if(!empty($Dialog))
                            {
                                if(!empty($_POST["data"]["client"]["name"]))
                                    $Dialog->SetName($_POST["data"]["client"]["name"]);
                                $Dialog->Save();
                                try
                                {
                                    $serviceName = "";
                                    foreach($_POST["data"]["services"] as $Service)
                                    {
                                        $serviceName .= $Service["title"] . " ";
                                    }
                                    if($Message->GetType() == Message::MESSAGE_TYPE_TEXT)
                                    {
                                        $Message->SetText(Tag::ReplaceTag([ "clientName"     => $_POST["data"]["client"]["name"],
                                                                            "dateAndTime"    => $_POST["data"]["date"],
                                                                            "masterName"     => $_POST["data"]["staff"]["name"],
                                                                            "serviceName"    => $serviceName], 
                                                                            $Message->GetText()));
                                    }
                                    (new MessageController())->SendMessage($Dialog, $Message);
                                    return;
                                }
                                catch(Exception $error) {}
                            }
                        }
                    }

                }
                catch(Exception $e) {
                    Logger::Log(Log::TYPE_ERROR, "YclientWebhook", (string)$e);
                }
            }
        }
        else
            PrintJson::OperationError(AmoCRMError, REQUEST_FAILED);
    }


    /**
     * ActionGetYclients
     * 
     * Получение интеграции с Yclient.
     * 
     */
    public function ActionGetYclients()
    {
        $Yclients = Yclients::FindByUserId();
        if(!empty($Yclients))
        {
            $Tasks = [];
            foreach($Yclients->GetTaskss() as $key => $obj)
            {
                    $Tasks[] = [
                        "task"    => $key,
                        "message" => MessageController::MessageToArray(Message::CreateMessageObj($obj))
                    ];
                
            }
            PrintJson::OperationSuccessful([
                "yclients_integration_id" => [
                    "yclients_integration_id"   => $Yclients->GetId(),
                    "is_active"                 => $Yclients->IsActive(),
                    "tasks"                     => empty($Tasks) ? null : $Tasks,
                ]
            ]);
        }
        else
            PrintJson::OperationError(AmoCRMError, NOT_FOUND);
    }


    /**
     * ActionUpdateYclientsSettings
     * 
     * Обновление настроек интеграции с Yclient.
     * 
     * @param array $Parameters Массив параметров содержайщий изменения в задачи и id интеграции
     */
    public function ActionUpdateYclientsSettings(array $Parameters)
    {
        if(Validator::IsValid($Parameters['Post'], [
            ["Key" => "yclients_integration_id", "Type" => "int"],
            ["Key" => "tasks",                   "Type" => "array",    "IsNull" => true]
        ]))
        {
            $Yclients = Yclients::FindById($Parameters["Post"]["yclients_integration_id"]);

            if(!empty($Yclients))
            {
                if(!empty($Parameters["Post"]["tasks"]))
                {
                    $Actions = [];

                    foreach($Parameters["Post"]["tasks"] as $obj)
                    {
                        if(Validator::IsValid($obj, [
                            ["Key" => "task"],
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

                            $Actions[$obj['task']] = $Message->ToArray();
                        }
                    else
                        return;
                    }

                    $Yclients->SetTaskss($Actions);
                }
                else
                    $Yclients->SetTaskss([]);

                $Yclients->Save();
                PrintJson::OperationSuccessful();
            }
        }
        else
            PrintJson::OperationError(AmoCRMError, NOT_FOUND);
    }


    /**
     * ActionDeleteIntegration
     * 
     * Удаляет интеграцию с Yclient.
     */
    public function ActionDeleteIntegration()
    {
        if(!empty($Find = Yclients::FindByUserId()))
        {
            $YclientsTasks = YclientsTasks::FindAllByYclientId($Find->GetId());
            foreach($YclientsTasks as $YclientsTask)
                $YclientsTask->Delete();
            $Find->Delete();
            PrintJson::OperationSuccessful();
        }
        else
            PrintJson::OperationError(AmoCRMError, NOT_FOUND);
    }


    /**
     * ChangeTimeZone
     * 
     * Меняет часовой пояс на тот, который выбрал пользователь для выполнения задачи.
     * 
     * @param string $TimeZone Константа временного пояса
     * @return DateTime Новое представление даты и времени
     */
    static public function ChangeTimeZone(string $TimeZone) : DateTime
    {
        $Date = new DateTime('now', new DateTimeZone($TimeZone));

        return $Date;
    }


    /**
     * FindDialogWithClient
     * 
     * Ищет диалог с клиентом в FastLead.
     * 
     * @param objcet $Messenger Модель мессенджера
     * @param string $Phone Телефон клиента
     * @return Dialog Диалог с клиентом
     */
    static public function FindDialogWithClient(object $Messenger, string $Phone) : ?Dialog
    {
        try
        {
            //FixDialogues
            if($Messenger instanceof Whatsapp)
            {
                $Dialog = WhatsappDialog::FindByPhoneAndWhatsappId(Validator::NormalizePhone($Phone), $Messenger->GetId());
                if(empty($Dialog))
                    $Dialog = new WhatsappDialog();
                $Dialog->SetWhatsappId($Messenger->GetId());
                $Dialog->SetPhone(Validator::NormalizePhone($Phone));
            }
            else if($Messenger instanceof Instagram)
            {
                //Login?
                $Dialog = InstagramDialog::FindByLoginAndInstagramId(Validator::NormalizePhone($Phone), $Messenger->GetId());
                if(empty($Dialog))
                    $Dialog = new InstagramDialog();
                $Dialog->SetLogin($Messenger->GetLogin());
            }
        }
        catch(Exception $error) {
            if($error->getCode() != 400)
                throw $error;
        }

        return $Dialog;
    }


    /**
     * AdditionalFilters
     * 
     * Применяет допольнительные фильтры из Yclients.
     * 
     * @param array $Filters Массив выбранных фильтров
     * @param array $Record Массив, содержащий данные о записи клиента
     * @param string $CompanyId ID филиала компании, введеное пользователем
     * @param object $Yclients Модель интеграции
     * @param string $TaskType Тип задачи
     * @param object $YclientRecord Модель с записью клиента, используется только при типе задачи PostChangedNotification
     * @return bool
     */
    static public function AdditionalFilters($Filters = [], $Record = [], string $CompanyId, object $Yclients, string $TaskType, object $YclientRecord = null)
    {
        if(empty($Filters))
            return false;
        
        switch($TaskType)
        {
            case 'Birthday':
                foreach($Filters as $Filter => $Number)
                {
                    switch($Filter)
                    {
                        case "visited_start" :
                            if($Record["visits"] < $Number)
                                return true;
                            break;
                        case "visited_end" :
                            if($Record["visits"] > $Number)
                                return true;
                            break;
                        case "sum_start" :
                            if($Record["balance"] < $Number)
                                return true;
                            break;
                        case "sum_end" :
                            if($Record["balance"] > $Number)
                                return true;
                            break;
                    }
                }
                break;
                
            case "Review":
            case "PostCreatedNotification" :
            case "PostDeleteNotification":
            case "RecordRemindAtTime" :
            case "RecordRemind" :
            case "PostChangedNotification" :
                foreach($Filters as $Filter => $Number)
                {
                    switch($Filter)
                    {
                        case "sum_start" :
                            $Sum = 0;
                            foreach($Record["services"] as $Service)
                                $Sum += $Service['cost'];

                            if($Sum < $Number)
                                return true;
                            break;
                        case "sum_end" :
                            $Sum = 0;
                            foreach($Record["services"] as $Service)
                                $Sum += $Service['cost'];

                            if($Sum > $Number)
                                return true;
                            break;
                        case "visited_start" :
                            $Response = YclientsController::requestCurl("client/" . $CompanyId . "/" . $Record['client']['id'], 
                            [], 
                            "GET",
                            [
                                "Authorization: Bearer " . YCLIENTS_BEARER_KEY . ", User " . $Yclients->GetUserToken(),
                                "Content-Type: application/json",
                                "Accept: application/vnd.yclients.v2+json"
                            ]);


                            if($Response['data']['visits'] < $Number)
                                return true;
                            break;
                        case "visited_end" :
                            $Response = YclientsController::requestCurl("client/" . $CompanyId . "/" . $Record['client']['id'], 
                            [], 
                            "GET",
                            [
                                "Authorization: Bearer " . YCLIENTS_BEARER_KEY . ", User " . $Yclients->GetUserToken(),
                                "Content-Type: application/json",
                                "Accept: application/vnd.yclients.v2+json"
                            ]);


                            if($Response['data']['visits'] > $Number)
                                return true;
                            break;
                        case "record_status" :
                            if(!in_array($Record['visit_attendance'], $Number))
                                return true;
                            break;
                        case "record_type" :
                            if(($Record["online"] && !in_array("online", $Number)) || (!$Record["online"] && !in_array("offline", $Number)))
                                return true;
                            break;
                        case "record_category" :
                            foreach($Record["record_labels"] as $RecordCategory)
                                if(!in_array($RecordCategory["id"], $Number))
                                    return true;
                            break;
                        case "record_service" :
                            foreach($Record["services"] as $Service)
                                if(!in_array($Service["id"], $Number))
                                    return true;
                            break;
                        case "master_id" :
                            foreach($Record["staff"] as $Master)
                                if(!in_array($Master["id"], $Number))
                                    return true;
                            break;
                        case "start_date" :
                            if(strtotime($Number) > strtotime(date("G:i", $Record["date"])))
                                return true;
                            break;
                        case "end_date" :
                            if(strtotime($Number) < strtotime(date("G:i", $Record["date"])))
                                return true;
                            break;
                        case "send_parameters" :
                            if(!empty($YclientRecord))
                            {
                                foreach($Number as $Type => $Value)
                                {
                                    switch($Type)
                                    {
                                        case "change_datetime":
                                            if($Value && $YclientRecord->RecordDate == $Record["date"])
                                                return true;
                                            break;
                                        case "change_master":
                                            if($Value && $YclientRecord->MasterId == $Record["staff"]["id"])
                                            return true;
                                            break;
                                        case "change_status":
                                            if($Value && $YclientRecord->RecordStatus == $Record["visit_attendance"])
                                            return true;
                                            break;
                                        case "change_service":
                                            $FLag = false;
                                            if($Value)
                                                foreach($Record["services"] as $Service)
                                                    foreach($YclientRecord->RecordServices as $RecordService)
                                                        if(in_array($RecordService, $Service))
                                                            $Flag = true;
                                                        else
                                                            $Flag = false;
                                            if($Flag)
                                                return true;
                                            break;
                                    }
                                }
                            }
                            
                            break;
                    }
                }
                break;
        }
        return false;

    }
    
}
?>