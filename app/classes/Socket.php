<?php


namespace classes;


use Exception;


class Socket
{
    private function __construct() {}


    static public function Send(string $RemoteSocket, array $Data) : bool
    {
        $Socket = stream_socket_client($RemoteSocket);
        if($Socket === false)
            throw new Exception("Connect error to socket");
        fwrite($Socket, json_encode($Data));
        return true;
    }


    static public function SendAwaitResponse(string $RemoteSocket, array $Data) : string
    {
        $Socket = stream_socket_client($RemoteSocket);
        if ($Socket)
        {
            $Sent = stream_socket_sendto($Socket, json_encode($Data));
            if ($Sent > 0)
                return fread($Socket, 4096);
            else
                return "";
        }
        throw new Exception("Connect error to socket");
    }
}