<?php

ini_set('error_reporting', 0);
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);

require_once __DIR__ . '/../sdk/php/vendor/autoload.php';
require_once __DIR__ . '/../classes/Directory.php';
require_once __DIR__ . '/../classes/File.php';
require_once __DIR__ . '/../classes/Log.php';

use classes\Directory;
use classes\File;
use classes\Log;
use Workerman\Worker;

set_time_limit(0);
define("ROOT", str_replace("\\", "/", __DIR__ . "/.."));
const LOGS_DIRECTORY = ROOT . "/logs_dump";


try
{
    $_ENV = Dotenv\Dotenv::createImmutable(ROOT)->load();
}
catch(Exception $error)
{
    $_ENV = getenv();
}


$Logs = array();
$Date = 0;


/**
 * Events
 */
function onConnect($Connection) {}

function onMessage($Connection, $Request)
{
    global $Logs;
    $Connection->close();

    if(!empty($Request))
    {
        $Log = new Log();
        try
        {
            $Request = json_decode($Request, true);
            
            if(!empty($Request["Type"]))
                $Log->SetType($Request["Type"]);

            if(!empty($Request["Message"]))
                $Log->SetMessage($Request["Message"]);

            if(!empty($Request["SessionUid"]))
                $Log->SetSessionUid($Request["SessionUid"]);

            if(!empty($Request["Data"]))
                $Log->SetData($Request["Data"]);

            if(!empty($Request["Source"]))
                $Log->SetSource($Request["Source"]);

            if(!empty($Request["UserId"]))
                $Log->SetUserId($Request["UserId"]);

                
        }
        catch(Throwable $error)
        {
            $Log->SetMessage("Не удалось создать лог: " . $error->getMessage());
            $Log->SetData($Request);
            $Log->SetType(Log::TYPE_FATAL_ERROR);
        }

        if($_ENV["DEBUG"] || $Log->GetSource() == 'Venom' || $Log->GetSource() == 'Instagram')
        {
            $Temp = [$Log];
            WriteLogs($Temp);
        }
        else if(!empty($Log->GetSessionUid()))
            $Logs[$Log->GetSessionUid()][] = $Log;

        if(!$_ENV["DEBUG"] && ($Log->GetType() == Log::TYPE_FATAL_ERROR || $Log->GetType() == Log::TYPE_ERROR))
        {
            if(!empty($Log->GetSessionUid()))
                WriteLogs($Logs[$Log->GetSessionUid()], true);
            else
            {
                $Temp = [$Log];
                WriteLogs($Temp);
            }

            try
            {
                SendLogToBot($Log);
            }
            catch(Throwable $error) {}
        }
    }
}

function onClose($Connection) {}

function onWorkerStart() {}

function WriteLogs(array &$WriteLogs, bool $Stack = false)
{
    global $Date;

    if(!Directory::Exists(LOGS_DIRECTORY))
        Directory::Create(LOGS_DIRECTORY);

    $LastLog = end($WriteLogs);
    if($Stack)
    {
        array_walk($WriteLogs, function (&$Item)
        {
            $Item = "   =>> " . str_replace("\n", "\n   =>> ", (string)$Item);
        });
        array_unshift($WriteLogs, date("[H:i:s]") . " [" . $LastLog->GetType() . " Stack] {");
        $WriteLogs[] = "}";
    }

    File::AppendLines(LOGS_DIRECTORY . "/Log" . $LastLog->GetSource() . "[" . date("d.m.Y") . "].log", $WriteLogs);
    $WriteLogs = array();

    //Чистка логов
    if($Date != date('d'))
    {
        $Date = date('d');
        foreach(Directory::GetFiles(LOGS_DIRECTORY) as $File)
        {
            preg_match("/\[(\d+\.\d+\.\d+)\].log/", $File, $Matches);
            if(!empty($Matches[1]) && strtotime($Matches[1]) < (time() - 259200))
                File::Delete(LOGS_DIRECTORY . "/$File");
        }
    }
}

function SendLogToBot($Log)
{
    $Curl = curl_init();
    curl_setopt_array($Curl, array(
        CURLOPT_SSL_VERIFYPEER => 0,
        CURLOPT_URL => "https://api.telegram.org/bot1942395917:AAETEv5O9DmwdUUXhgCO_NOlbwrsRaSXKk0/sendmessage?chat_id=-565760675&text=" . urlencode((string)$Log),

        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_SSL_VERIFYHOST => 0
    ));
    curl_exec($Curl);
    curl_close($Curl);
}



/**
 * Init
*/
$Socket                 = new Worker('tcp://localhost:1436');
$Socket->onConnect      = "onConnect";
$Socket->onMessage      = "onMessage";
$Socket->onClose        = "onClose";
$Socket->onWorkerStart  = "onWorkerStart";
$Socket->count          = 1;


/**
 * Run
 */
ob_start();
Worker::runAll();