<?php


use models\Authorization;
use models\User;

Authorization::SetAccesses(User::USER_TYPE_CUSTOMER, [
    "GetDialog" => -1,
    "SetDialog" => -1,
    
    "GetWhatsapp" => 0,
    "SetWhatsapp" => 0,

    "GetFolder" => -1,
    "SetFolder" => -1,

    "GetMassSending" => -1,
    "SetMassSending" => -1,

    "GetAutoresponder" => -1,
    "SetAutoresponder" => -1,

    "GetDynamicResources" => -1,
    "SetDynamicResources" => -1,

    "GetDynamicMassSending" => -1,
    "SetDynamicMassSending" => -1,

    "GetTask" => -1,
    "SetTask" => -1,

    "GetInstagram" => 0,
    "SetInstagram" => 0,

    "GetWhatsappDialog" => -1,
    "SetWhatsappDialog" => -1,

    "GetLocalDialog" => -1,
    "SetLocalDialog" => -1,

    "SetAmoCRMIntegration" => -1,
    "GetAmoCRMIntegration" => -1,

    "GetInstagramDialog" => -1,
    "SetInstagramDialog" => -1,

    "GetBitrixIntegration" => -1,
    "SetBitrixIntegration" => -1,

    "GetMegaplanIntegration" => -1,
    "SetMegaplanIntegration" => -1,

    "GetYclientsIntegration" => -1,
    "SetYclientsIntegration" => -1,

    "GetYclientsTasks" => -1,
    "SetYclientsTasks" => -1,

    "GetEmail" => -1,
    "SetEmail" => -1,

    "GetApiToken" => -1,
    "SetApiToken" => -1,

    "GetProxy" => -1,
    "SetProxy" => -1,

    "GetMessage" => -1,
    "SetMessage" => -1,

    "GetPurse" => -1,
    "SetPurse" => -1,

    "GetOperation" => -1,
    "SetOperation" => -1,

    "GetTariff" => -1,

    "GetSalesTariff" => -1,

    "GetUserTariff" => -1,
    "SetUserTariff" => -1,

    "GetAffiliate" => -1,
    "SetAffiliate" => -1,

    "GetAffiliatePartners" => -1,
    "SetAffiliatePartners" => -1,
  
    "GetInstagramApi" => -1,
    "SetInstagramApi" => -1,

    "GetFacebook" => -1,
    "SetFacebook" => -1,

    "GetInstagramApiDialog" => -1,
    "SetInstagramApiDialog" => -1,

    "GetIgnoreList" => -1,
    "SetIgnoreList" => -1,

    "GetWhatsAppTariff" => -1,
    "SetWhatsAppTariff" => -1,

    "GetInstagramTariff" => -1,
    "SetInstagramTariff" => -1
]);

Authorization::SetAccesses(User::USER_TYPE_SUPPORT, [], [User::USER_TYPE_CUSTOMER]);

Authorization::SetAccesses(User::USER_TYPE_ADMIN, [
    "GetDevice" => -1,
    "SetDevice" => -1,
    
    "GetExternalPhone" => -1,
    "SetExternalPhone" => -1,

    "SetTariff" => -1,

    "SetSalesTariff" => -1,

    "GetWhatsapp" => -1,
    "SetWhatsapp" => -1,

    "GetInstagram" => -1,
    "SetInstagram" => -1,

], [User::USER_TYPE_CUSTOMER]);