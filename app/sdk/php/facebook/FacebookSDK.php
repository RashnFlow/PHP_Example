<?php


namespace sdk\php\facebook;

use classes\Log;
use classes\Logger;
use Exception;
use Facebook\Exceptions\FacebookResponseException;
use Facebook\Facebook;
use Facebook\FacebookResponse;


class FacebookSDK extends Facebook
{
    private ?\models\Facebook $Facebook = null;

    public function __construct(\models\Facebook $Facebook = null)
    {
        $this->Facebook = $Facebook;
        parent::__construct(array_merge([
            'app_id'     => INSTAGRAM_API_APP_ID,
            'app_secret' => INSTAGRAM_API_APP_SECRET,
        ], empty($Facebook) ? [] : ['default_access_token' => $Facebook->AccessToken]));
    }


    /**
     * Sends a GET request to Graph and returns the result.
     *
     * @param string                  $endpoint
     * @param AccessToken|string|null $accessToken
     *
     * @throws FacebookSDKException
     */
    public function get($endpoint, $accessToken = null, int $PageId = null) : FacebookResponse
    {
        $TryCount = 2;
        for($i = 0; $i < $TryCount; $i++)
        {
            try
            {
                return parent::get($endpoint, empty($PageId) ? $accessToken : $this->Facebook->Pages[$PageId], null, null);
            }
            catch(FacebookResponseException $error)
            {
                if($error->getCode() != 190 || empty($this->Facebook) || ($TryCount - 1) == $i)
                    throw $error;

                if(empty($PageId))
                    $this->UpdateAccessToken();
                else
                    $this->UpdatePageAccessToken();

                return parent::get($endpoint, empty($PageId) ? $accessToken : $this->Facebook->Pages[$PageId], null, null);
            }
        }
    }


    /**
     * Sends a POST request to Graph and returns the result.
     *
     * @param string                  $endpoint
     * @param AccessToken|string|null $accessToken
     *
     * @return FacebookResponse
     *
     * @throws FacebookSDKException
     */
    public function post($endpoint, array $params = [], $accessToken = null, int $PageId = null) : FacebookResponse
    {
        $TryCount = 2;
        for($i = 0; $i < $TryCount; $i++)
        {
            try
            {
                return parent::post($endpoint, $params, empty($PageId) ? $accessToken : $this->Facebook->Pages[$PageId], null, null);
            }
            catch(FacebookResponseException $error)
            {
                if($error->getCode() != 190 || empty($this->Facebook) || ($TryCount - 1) == $i)
                    throw $error;

                if(empty($PageId))
                    $this->UpdateAccessToken();
                else
                    $this->UpdatePageAccessToken();
            }
        }
    }



    private function UpdateAccessToken()
    {
        if(empty($this->Facebook) || empty($this->Facebook->RefreshToken))
            throw new Exception('Facebook is empty');

        $OAuth2Client = parent::getOAuth2Client();
        try
        {
            $this->Facebook->AccessToken = $OAuth2Client->getAccessTokenFromCode($OAuth2Client->getCodeFromLongLivedAccessToken($this->Facebook->RefreshToken))->getValue();
            
            //Если RefreshToken обновлялся 40 дней назад, то обновляем его
            if(($this->Facebook->LastRefreshTokenUpdateTime + 3456000) < time())
                $this->Facebook->RefreshToken = $OAuth2Client->getLongLivedAccessToken($this->Facebook->AccessToken)->getValue();
        }
        catch(FacebookResponseException $error)
        {
            Logger::Log(Log::TYPE_FATAL_ERROR, 'Ошибка при обновлении токенов Facebook аккаунта', (string)$error);
            $this->Facebook->IsActive = false;
        }
        
        $this->Facebook->Save();
    }


    private function UpdatePageAccessToken()
    {
        if(empty($this->Facebook) || empty($this->Facebook->RefreshToken))
            throw new Exception('Facebook is empty');

        $Response = $this->get('me/accounts?fields=access_token,id')->getDecodedBody();
        $this->Facebook->Pages = array_combine(array_column($Response["data"], 'id'), array_column($Response["data"], 'access_token'));
        $this->Facebook->Save();
    }
}