<?php

ini_set('error_reporting', 0);
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);

require_once __DIR__ . '/../sdk/php/vendor/autoload.php';


use Workerman\Worker;


$Freeze = array();


/**
 * Events
 */
function onConnect($Connection) {}

function onMessage($Connection, $Request)
{
    global $Freeze;

    if(!empty($Request))
    {
        $Request = json_decode($Request, true);

        $echo = array();
        switch($Request["command"])
        {
            case "check":
                if($Freeze[$Request["uid"]] + 300 < time())
                    unset($Freeze[$Request["uid"]]);

                $echo["check"] = !empty($Freeze[$Request["uid"]]);
                break;

            case "set":
                if(empty($Freeze[$Request["uid"]]))
                {
                    $Freeze[$Request["uid"]] = time();
                    $echo["set"] = "ok";
                }
                else
                    $echo["error"] = "uid exists";
                break;

            case "delete":
                unset($Freeze[$Request["uid"]]);
                $echo["delete"] = "ok";
                break;

            default:
                $echo["error"] = "command not found";
            break;
        }

        if(empty($echo["error"])) $echo["status"] = "ok";
        else                      $echo["status"] = "error";

        $Connection->send(json_encode($echo));
    }

    $Connection->close();
}

function onClose($Connection) {}

function onWorkerStart() {}


/**
 * Init
*/
$Socket                 = new Worker('tcp://localhost:3120');
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