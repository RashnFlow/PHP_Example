<?php

    namespace sdk\php\amocrm;

    class AmoCurl
    {
        public static function SendDataToAmoCRM ($data, $link) : array
        {
            $curl = curl_init();
            curl_setopt($curl,CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl,CURLOPT_TIMEOUT, 30);
            curl_setopt($curl,CURLOPT_USERAGENT,'amoCRM-oAuth-client/1.0');
            curl_setopt($curl,CURLOPT_URL, $link);
            curl_setopt($curl,CURLOPT_HTTPHEADER, ['Content-Type:application/json']);
            curl_setopt($curl,CURLOPT_HEADER, false);
            curl_setopt($curl,CURLOPT_CUSTOMREQUEST, 'POST');
            curl_setopt($curl,CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($curl,CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($curl,CURLOPT_SSL_VERIFYHOST, 0);
            $out = curl_exec($curl);
            curl_close($curl);
            $response = json_decode($out, true);

            return $response;
        }

        public static function SendHeaderToAmoCRM ($headers, $link) : array
        {
            $curl = curl_init();
            curl_setopt($curl,CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl,CURLOPT_TIMEOUT, 30);
            curl_setopt($curl,CURLOPT_USERAGENT,'amoCRM-oAuth-client/1.0');
            curl_setopt($curl,CURLOPT_URL, $link);
            curl_setopt($curl,CURLOPT_HTTPHEADER, $headers);
            curl_setopt($curl,CURLOPT_HEADER, false);
            curl_setopt($curl,CURLOPT_SSL_VERIFYPEER, 1);
            curl_setopt($curl,CURLOPT_SSL_VERIFYHOST, 2);
            $out = curl_exec($curl);
            curl_close($curl);
            $response = json_decode($out, true);

            return $response;
        }

        public static function SendDataAndHeaderToAmoCRM ($data, $headers, $link) : array
        {
            $curl = curl_init();
            curl_setopt($curl,CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl,CURLOPT_TIMEOUT, 30);
            curl_setopt($curl,CURLOPT_USERAGENT,'amoCRM-oAuth-client/1.0');
            curl_setopt($curl,CURLOPT_URL, $link);
            curl_setopt($curl,CURLOPT_HTTPHEADER, $headers);
            curl_setopt($curl,CURLOPT_HEADER, false);
            curl_setopt($curl,CURLOPT_CUSTOMREQUEST, 'POST');
            curl_setopt($curl,CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($curl,CURLOPT_SSL_VERIFYPEER, 1);
            curl_setopt($curl,CURLOPT_SSL_VERIFYHOST, 2);
            $out = curl_exec($curl);
            curl_close($curl);
            $response = json_decode($out, true);

            return $response;
        }

        public static function SendDataToChatAmoCRM ($data, $headers, $link) : array
        {
            $curl = curl_init();
            curl_setopt($curl,CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl,CURLOPT_TIMEOUT, 30);
            curl_setopt($curl,CURLOPT_URL, $link);
            curl_setopt($curl,CURLOPT_HTTPHEADER, $headers);
            curl_setopt($curl,CURLOPT_HEADER, false);
            curl_setopt($curl,CURLOPT_CUSTOMREQUEST, 'POST');
            curl_setopt($curl,CURLOPT_POSTFIELDS, $data);
            $out = curl_exec($curl);
            curl_close($curl);
            $response = json_decode($out, true);

            return $response;
        }
    }
?>