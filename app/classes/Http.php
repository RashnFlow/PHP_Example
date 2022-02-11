<?php


namespace classes;

use Exception;
use models\Proxy;

class Http
{
    public Proxy    $Proxy;
    public int      $TimeOut = 60;
    
    private array $Headers = [];


    private function Send(string $Url, $Data = null, array $Headers = null) : string
    {
        ob_start();

        $Curl = curl_init();

        if(!empty($this->Proxy))
        {
            curl_setopt($Curl, CURLOPT_PROXY, $this->Proxy->HostName);
            $Type = CURLPROXY_HTTP;
            switch($this->Proxy->Protocol)
            {
                case "https":
                    $Type = CURLPROXY_HTTPS;
                    break;

                case "socks4":
                    $Type = CURLPROXY_SOCKS4;
                    break;

                case "socks4a":
                    $Type = CURLPROXY_SOCKS4A;
                    break;

                case "socks5":
                    $Type = CURLPROXY_SOCKS5;
                    break;
            }
            curl_setopt($Curl, CURLOPT_PROXYTYPE, $Type);
            if(!empty($this->Proxy->Password) && !empty($this->Proxy->Login))
                curl_setopt($Curl, CURLOPT_PROXYUSERPWD, $this->Proxy->Login . ":" . $this->Proxy->Password);
        }

        if(!empty($Data))
        {
            curl_setopt($Curl, CURLOPT_POST, true);
            $Headers[] = "Content-Type: application/json";
            curl_setopt($Curl, CURLOPT_POSTFIELDS, (is_array($Data) || is_object($Data) ? json_encode($Data) : $Data));
        }

        $Headers[] = 'User-Agent: FastLeadRuntime/7.28.4';

        $Obj = &$this;
        curl_setopt_array($Curl, array(
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_URL => $Url,

            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_TIMEOUT => $this->TimeOut,

            CURLOPT_HTTPHEADER => $Headers,

            CURLOPT_HEADERFUNCTION => function ($thisCurl, $Header) use (&$Obj) {
                $Len = strlen($Header);
                $Header = explode(':', $Header, 2);
                if (count($Header) < 2) // ignore invalid headers
                    return $Len;
        
                $Obj->Headers[strtolower(trim($Header[0]))] = trim($Header[1]);
        
                return $Len;
            }
        ));
        $Response = curl_exec($Curl);
        curl_close($Curl);

        ob_end_clean();

        if($Response === false)
            throw new Exception("Error send to url: " . curl_error($Curl));

        return $Response;
    }


    public function SendPost(string $Url, $Data) : string
    {
        return $this->Send($Url, $Data);
    }


    public function SendGet(string $Url) : string
    {
        return $this->Send($Url, null);
    }


    public function GetHeaders()
    {
        return $this->Headers;
    }
}