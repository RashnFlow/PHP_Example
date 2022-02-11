<?php


namespace models;


use classes\Cache;
use classes\Headers;
use Exception;
use factories\UserFactory;

class Authentication
{
    /**
     * @return string Api token
     */
    static public function AuthUser(string $Login, string $Password) :  string
    {
        $User = self::GetUser($Login, $Password);
        if(!empty($User) && $User->GetIsActive())
        {
            self::SetAuthUser(UserFactory::GetSystemUser(), SYSTEM_SESSION);
            $Token = new ApiToken();
            $Token->SetUserId($User->GetId());
            $Token->GenerateToken();
            $Token->Save();

            self::SetAuthUser($User, $Token->GetToken());
        
            //Ограничение по токенам
            $Tokens = array_reverse(ApiToken::FindAllByUserId());
            if(count($Tokens) > 5)
                for($i = 5; $i < count($Tokens); $i++)
                    $Tokens[$i]->Delete();

            return $Token->GetToken();
        }
        else
            throw new Exception("User is not found", NOT_FOUND);
    }


    static public function GetUserByToken(ApiToken $Token) : User
    {
        if($Token->GetUpdatedAt() + (2592000000) > time())
        {
            $Token->Save(false);
            return User::FindById($Token->GetUserId());
        }
        else
        {
            $Token->Delete();
            throw new Exception("User is not found", NOT_FOUND);
        }
    }


    static private function GetUser(string $Login, string $Password) : User
    {
        $User = User::FindByLogin($Login);
        if(!empty($User))
        {
            if(password_verify($Password, $User->GetPassword()))
                return $User;
            else
                throw new Exception("Wrong password", REQUEST_FAILED);
        }
        else
            throw new Exception("User is not found", NOT_FOUND);
    }


    static public function SetAuthUser(User $User, string $SessionKey)
    {
        if(empty($User->GetId())) throw new Exception("UserId is empty");
        $User->SetSession($SessionKey);
        Cache::Set("AuthUser", ["UserId" => $User->GetId(), "SessionKey" => $SessionKey, "User" => $User]);
    }


    static public function GetAuthUser() : ?User
    {
        $Find = null;
        $Cache = Cache::Get("AuthUser");
        if(empty($Cache))
        {
            try
            {
                $Token = ApiToken::FindByToken(Headers::GetAuth(), false);
                if(!empty($Token))
                {
                    $Find = self::GetUserByToken($Token);
                    Cache::Set("AuthUser", ["UserId" => $Find->GetId(), "SessionKey" => $Token->GetToken(), "User" => $Find]);
                }
            }
            catch(Exception $error){}
        }
        else
            $Find = $Cache["User"];
        return $Find;
    }


    static public function CheckAuth() : bool
    {
        return !empty(self::GetAuthUser());
    }


    /**
     * @return true|Exception
     */
    static public function IsAuthAccess() : bool
    {
        if(!self::CheckAuth())
            throw new Exception("User is not authenticated", USER_NOT_AUTH);
        
        return true;
    }


    static private function IsValidTimeSession(int $Time) : bool
    {
        return ($Time + (60*60*24*7)) > time();
    }
}