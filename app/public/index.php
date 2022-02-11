<?php

use classes\Freeze;
use classes\Log;
use classes\Logger;
use classes\Router;

/**
 * Constant to horse folder
 */
define("ROOT", str_replace("\\", "/", __DIR__ . "/.."));


if($_SERVER["CONTENT_TYPE"] == 'application/json')
{
    $_POST = json_decode(file_get_contents('php://input'), true);
    $_POST = $_POST == null ? [] : $_POST;
}

/**
 * Initializing settings and constants
 */
require "../init.php";


Logger::Log(Log::TYPE_INFO, "Входящий запрос", array_merge(getallheaders(), ["REMOTE_ADDR" => $_SERVER['REMOTE_ADDR'], "REQUEST_URI" => $_SERVER["REQUEST_URI"]]));


/**
 * Running routes
 */
Router::Run();


/**
 * Clear freeze
 */
Freeze::Clear();