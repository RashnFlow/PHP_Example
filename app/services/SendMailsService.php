<?php


namespace services;


use Exception;
use factories\UserFactory;
use models\Authentication;
use models\Email;
use models\Task;
use models\User;
use views\View;
use Sendpulse\RestApi\ApiClient;
use Sendpulse\RestApi\Storage\FileStorage;


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
$SPApiClient = new ApiClient(MAIL_API_USER_ID, MAIL_API_SECRET, new FileStorage());

echo "Initialization complete";
while(true)
{
    $Task = Task::Next("SendMail");
    if(!empty($Task))
    {
        try
        {
            foreach($Task->GetData()["Emails"] as $Email)
            {
                $EmailObj = Email::FindByEmail($Email);
                if(empty($EmailObj))
                {
                    $EmailObj = new Email();
                    $EmailObj->SetEmail($Email);
                    $EmailObj->Save();
                }

                if($EmailObj->GetIsSend())
                {
                    $Mail = View::Generate($Task->GetData()["Mail"]["Template"], array_merge(
                        $Task->GetData()["Mail"]["Parameters"],
                        [
                            "Unsubscribe" => DOMAIN_API_URL . "/email/unsubscribe/" . $EmailObj->GetUid()
                        ]
                    ));

                    $Out = $SPApiClient->smtpSendMail([
                        'html' => $Mail,
                        'subject' => $Task->GetData()["Subject"],
                        'from' => [
                            'name' => 'Fastlead',
                            'email' => 'info@fastlead.app',
                        ],
                        'to' => [
                            [
                                "email" => $EmailObj->GetEmail()
                            ]
                        ]
                    ]);
                }
            }

            $Task->Delete();
        }
        catch(Exception $error)
        {
            $Task->Fail();
            $Task->SetIsRunning(false);
            $Task->Save();
        }
    }
    sleep(1);
}