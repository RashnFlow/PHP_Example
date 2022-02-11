<?php


/**
 * ENV
 */
require ROOT . '/sdk/php/vendor/autoload.php';
try
{
    $_ENV = Dotenv\Dotenv::createImmutable(ROOT)->load();
}
catch(Exception $error)
{
    $_ENV = getenv();
}


/**
 * Display errors
 */
if($_ENV["DEBUG"])
{
    ini_set('display_errors', true);
    error_reporting(E_ERROR);
}


/**
 * init
 */
require ROOT . "/classes/Loader.php";


/**
 * Load
 */
spl_autoload_register("classes\Loader::LoadClass");
classes\Loader::LoadConfig();


/**
 * init Language
 */
require ROOT . "/languages/" . LANGUAGE . ".php";


/**
 * init DataBase
 */
models\DataBase::Connect();


/**
 * init dirs
 */
define("ROOT_TEMP", ROOT . "/resources/temp");
if(!classes\Directory::Exists(ROOT_TEMP))
    classes\Directory::Create(ROOT_TEMP);