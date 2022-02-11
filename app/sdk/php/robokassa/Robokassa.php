<?php

namespace sdk\php\robokassa;

class Robokassa
{
    /**
     * @param $OutSum - цена тарифа пользователя
     * @param $SignatureValue - md5 хэш-сумма
     * @param $InvoiceId - id тарифа пользователя
    */
    public static function PayMentUrl($OutSum = null, $InvoiceId, $SignatureValue, $Shp, $Value) : string
    {
        return "https://auth.robokassa.ru/Merchant/Index.aspx?MerchantLogin=". ROBOKASSA_LOGIN ."&InvId=$InvoiceId&Culture=ru&Encoding=utf-8&OutSum=$OutSum&SignatureValue=$SignatureValue&$Shp=$Value";
    }
}