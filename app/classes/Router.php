<?php


namespace classes;


use Exception;
use classes\CSRF;
use models\ApiToken;
use models\Authentication;
use Throwable;
use views\PrintJson;
use views\View;


class Router
{
    private function __construct() {}


    /**
     * Finding and executing a route
     */
    static public function Run()
    {
        $Uri = self::GetUri();
        Logger::Log(Log::TYPE_INFO, "Обработка uri", $Uri);

        if(!empty($Uri) && !empty($GLOBALS["RoutsCfg"]))
        {
            uasort($GLOBALS["RoutsCfg"], function ($Path1, $Path2) {
                return strlen($Path2["Route"]) - strlen($Path1["Route"]);
            });
            
            foreach($GLOBALS["RoutsCfg"] as $Path)
            {
                if(preg_match("~".$Path["Route"]."~", (explode("?", $Uri))[0]))
                {
                    //Run callback
                    try
                    {
                        self::AccessCheck($Path);

                        $Parameters = self::GetParameters(preg_replace("~".$Path["Route"]."~", "", $Uri));

                        Logger::Log(Log::TYPE_INFO, "Обработка с параметрами", $Parameters);

                        call_user_func($Path["callback"], $Parameters);
                    }
                    catch(Throwable $error)
                    {
                        if($error->getCode() == USER_NOT_AUTH || $error->getCode() == ACCESS_DENIED)
                            View::Print("CodeErrors", ["Code" => $error->getCode()]);
                        else
                        {
                            Logger::Log(Log::TYPE_FATAL_ERROR, "Ошибка при обработке запроса", (string)$error);
                            View::Print("CodeErrors", ["Code" => SERVER_ERROR]);
                        }
                    }
                    return;
                }
            }
        }
        View::Print("CodeErrors", ["Code" => NOT_FOUND]);
    }


    static private function AccessCheck($Path) : bool
    {
        try
        {
            if($Path["CheckApiToken"] == true && self::ApiTokenCheck())
                return true;
        }
        catch(Exception $error)
        {
            PrintJson::OperationAccessDenied(ApiTokenInvalid);
            throw $error;
        }


        try
        {
            if($Path["CheckCSRFToken"] == true && self::CSRFCheck())
                return true;
        }
        catch(Exception $error)
        {
            PrintJson::OperationAccessDenied(CSRFTokenInvalid);
            throw $error;
        }

        return true;
    }


    /**
     * Check acces Token
     */
    static private function ApiTokenCheck() : bool
    {
        try
        {
            $Token = Headers::GetAuth();
            $ApiToken = ApiToken::FindByToken($Token, false);
            if(!empty($ApiToken))
            {
                Authentication::SetAuthUser(Authentication::GetUserByToken($ApiToken), $Token);
                return true;
            }
        }
        catch(Exception $error) {}
        
        throw new Exception("Token invalid", ACCESS_DENIED);
    }


    /**
     * Check CSRF Token
     */
    static private function CSRFCheck() : bool
    {
        $CSRF = $_POST["csrf_token"];
        unset($_POST["csrf_token"]);
        
        if(CSRF::Check($CSRF))
            return true;

        throw new Exception("Token invalid", ACCESS_DENIED);
    }


    /**
     * Return uri Parameters
     */
    static private function GetParameters(string $Uri) : array
    {
        return [
            "Uri"       => array_values(array_diff(explode("/", explode("?", $Uri)[0]), [''])),
            "Get"       => $_GET,
            "Post"      => $_POST,
            "Files"     => $_FILES,
            "Headers"   => getallheaders()
        ];
    }


    /**
     * Return uri request
     */
    static private function GetUri() : string
    {
        $Uri = str_replace(parse_url(DOMAIN_API_URL)["path"], "", trim($_SERVER["REQUEST_URI"]));
        return empty($Uri) ? "/" : $Uri;
    }

    
    /**
     * @param callback  $callback
     */
    static public function Add(string $Route, $callback, bool $CheckCSRFToken = false, bool $CheckApiToken = false)
    {
        if(empty($GLOBALS["RoutsCfg"])) $GLOBALS["RoutsCfg"] = [];

        if(array_search($Route, array_column($GLOBALS["RoutsCfg"], "Route")) > -1)
            throw new Exception("Route exists");

        $GLOBALS["RoutsCfg"][] = ["Route" => $Route, "callback" => $callback, "CheckCSRFToken" => $CheckCSRFToken, "CheckApiToken" => $CheckApiToken];
    }
}