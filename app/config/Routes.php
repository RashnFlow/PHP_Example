<?php


use classes\Router;
use models\Authentication;
use models\User;
use views\View;

//Config
Router::Add("^/$", function($Parameters)
{
    echo "<center><h1>Главная страница</h1></center>";
});


/**
 * CSRF
 */
Router::Add("^/get/csrf/{0,1}", function($Parameters)
{
    views\PrintJson::OperationSuccessful(["csrf_token" => classes\CSRF::Generate()]);
});


/**
 * Auth
 */
Router::Add("^/get/user/token/{0,1}", function($Parameters)
{
    (new controllers\AuthController())->ActionGetUserToken($Parameters);
}, true);

Router::Add("^/user/logout/{0,1}", function($Parameters)
{
    (new controllers\AuthController())->ActionUserLogout($Parameters);
}, false, true);


/**
 * Dialogues
 */
Router::Add("^/get/dialog/avatar/{0,1}", function($Parameters)
{
    (new controllers\DialogController())->ActionGetAvatar($Parameters);
}, false, true);

Router::Add("^/get/dialog/messages/{0,1}", function($Parameters)
{
    (new controllers\DialogController())->ActionGetMessages($Parameters);
}, false, true);

Router::Add("^/delete/dialog/{0,1}", function($Parameters)
{
    (new controllers\DialogController())->ActionDeleteDialog($Parameters);
}, false, true);

Router::Add("^/update/dialog/move/{0,1}", function($Parameters)
{
    (new controllers\DialogController())->ActionMoveDialog($Parameters);
}, false, true);

Router::Add("^/get/dialog/{0,1}", function($Parameters)
{
    (new controllers\DialogController())->ActionGetDialog($Parameters);
}, false, true);

Router::Add("^/get/dialog/search/{0,1}", function($Parameters)
{
    (new controllers\DialogController())->ActionSearchDialog($Parameters);
}, false, true);

Router::Add("^/set/dialog/read/{0,1}", function($Parameters)
{
    (new controllers\DialogController())->ActionDialogSetRead($Parameters);
}, false, true);

Router::Add("^/update/dialog/discharge/folder/{0,1}", function($Parameters)
{
    (new controllers\DialogController())->ActionDischargeFolderDialog($Parameters);
}, false, true);

Router::Add("^/create/dialog/{0,1}", function($Parameters)
{
    (new controllers\DialogController())->ActionCreateDialog($Parameters);
}, false, true);

Router::Add("^/import/dialog/file/{0,1}", function($Parameters)
{
    (new controllers\DialogController())->ActionImportBase($Parameters);
}, false, true);

Router::Add("^/get/dialog/status/{0,1}", function($Parameters)
{
    (new controllers\DialogController())->ActionGetDialogStatus($Parameters);
}, false, true);

Router::Add("^/create/dialog/support/{0,1}", function($Parameters)
{
    (new controllers\LocalDialogController())->ActionCreateSupportDialog($Parameters);
}, false, true);


/**
 * Messages
 */
Router::Add("^/send/message/{0,1}", function($Parameters)
{
    (new controllers\MessageController())->ActionSendMessage($Parameters);
}, false, true);

Router::Add("^/send/test/message/{0,1}", function($Parameters)
{
    (new controllers\MessageController())->ActionSendTestMessage($Parameters);
}, false, true);


/**
 * Whatsapp
 */
Router::Add("^/get/whatsapp/all/{0,1}", function($Parameters)
{
    (new controllers\WhatsappController())->ActionGetAllWhatsapps($Parameters);
}, false, true);

Router::Add("^/get/whatsapp/qr/{0,1}", function($Parameters)
{
    (new controllers\WhatsappController())->ActionGetQR($Parameters);
}, false, true);

Router::Add("^/get/whatsapp/avatar/{0,1}", function($Parameters)
{
    $IsRun = false;
    try
    {
        Authentication::IsAuthAccess();
        $IsRun = true;
    }
    catch(Exception $error){}

    try
    {
        $Token = (!empty($Parameters["Get"]["token"]) ? $Parameters["Get"]["token"] : $Parameters["Post"]["token"]);
        if(controllers\VenomBotController::CheckAccess($Token))
            $IsRun = true;
    }
    catch(Exception $error){}

    if($IsRun)
    {
        Authentication::SetAuthUser(factories\UserFactory::GetSystemUser(), SYSTEM_SESSION);
        (new controllers\WhatsappController())->ActionGetWhatsappAvatar($Parameters);
    }
});

Router::Add("^/create/whatsapp/{0,1}", function($Parameters)
{
    (new controllers\WhatsappController())->ActionCreateWhatsapp($Parameters);
}, false, true);

Router::Add("^/update/whatsapp/{0,1}", function($Parameters)
{
    (new controllers\WhatsappController())->ActionUpdateWhatsapp($Parameters);
}, false, true);

Router::Add("^/get/whatsapp-activities/all/{0,1}", function($Parameters)
{
    (new controllers\WhatsappController())->ActionGetAllWhatsappActivities($Parameters);
}, false, true);

Router::Add("^/activate/whatsapp/{0,1}", function($Parameters)
{
    (new controllers\WhatsappController())->ActionActivateWhatsapp($Parameters);
}, false, true);

Router::Add("^/delete/whatsapp/{0,1}", function($Parameters)
{
    (new controllers\WhatsappController())->ActionDeleteWhatsapp($Parameters);
}, false, true);

Router::Add("^/whatsapp/synchronization/{0,1}", function($Parameters)
{
    (new controllers\WhatsappController())->ActionSynchronizationWhatsapp($Parameters);
}, false, true);


/**
 * Instagram
 */
Router::Add("^/get/instagram/all/{0,1}", function($Parameters)
{
    (new controllers\InstagramController())->ActionGetAllInstagrams($Parameters);
}, false, true);

Router::Add("^/create/instagram/{0,1}", function($Parameters)
{
    (new controllers\InstagramController())->ActionCreateInstagram($Parameters);
}, false, true);

Router::Add("^/update/instagram/{0,1}", function($Parameters)
{
    (new controllers\InstagramController())->ActionUpdateInstagram($Parameters);
}, false, true);

Router::Add("^/activate/instagram/{0,1}", function($Parameters)
{
    (new controllers\InstagramController())->ActionActivateInstagram($Parameters);
}, false, true);

Router::Add("^/delete/instagram/{0,1}", function($Parameters)
{
    (new controllers\InstagramController())->ActionDeleteInstagram($Parameters);
}, false, true);

Router::Add("^/two-factor/instagram/{0,1}", function($Parameters)
{
    (new controllers\InstagramController())->ActionTwoFactor($Parameters);
}, false, true);

Router::Add("^/event/instagram-sdk/status/{0,1}", function($Parameters)
{
    $Token = (!empty($Parameters["Get"]["token"]) ? $Parameters["Get"]["token"] : $Parameters["Post"]["token"]);
    if(controllers\InstagramSdkController::CheckAccess($Token))
        (new controllers\InstagramSdkController())->ActionEventStatus($Parameters);
});

Router::Add("^/event/instagram-sdk/new-subscriber/{0,1}", function($Parameters)
{
    $Token = (!empty($Parameters["Get"]["token"]) ? $Parameters["Get"]["token"] : $Parameters["Post"]["token"]);
    if(controllers\InstagramSdkController::CheckAccess($Token))
        (new controllers\InstagramSdkController())->ActionOnNewSubscriber($Parameters);
});

Router::Add("^/event/instagram-sdk/comment/{0,1}", function($Parameters)
{
    $Token = (!empty($Parameters["Get"]["token"]) ? $Parameters["Get"]["token"] : $Parameters["Post"]["token"]);
    if(controllers\InstagramSdkController::CheckAccess($Token))
        (new controllers\InstagramSdkController())->ActionOnComment($Parameters);
});

Router::Add("^/update/instagram-sdk/session/{0,1}", function($Parameters)
{
    $Token = (!empty($Parameters["Get"]["token"]) ? $Parameters["Get"]["token"] : $Parameters["Post"]["token"]);
    if(controllers\InstagramSdkController::CheckAccess($Token))
        (new controllers\InstagramSdkController())->ActionUpdateSession($Parameters);
});

/**
 * AmoCRM
 */
Router::Add("^/connection/amocrm/{0,1}", function($Parameters)
{
    (new controllers\AmoCRMIntegrationController())->ActionAmoCRMInstall($Parameters);
}, false, true);

Router::Add("^/webhook/amocrm/{0,1}", function($Parameters)
{
    $Token = (!empty($Parameters["Get"]["token"]) ? $Parameters["Get"]["token"] : $Parameters["Post"]["token"]);
    if(controllers\AmoCRMIntegrationController::CheckAccess($Token))
        (new controllers\AmoCRMIntegrationController())->ActionAmoCRMWebHook($Parameters);
});

Router::Add("^/webhook/bot/amocrm/{0,1}", function($Parameters)
{
    (new controllers\AmoCRMIntegrationController())->ActionAmoCRMBotWebHook($Parameters);
});

Router::Add("^/get/amocrm/funnels/{0,1}", function($Parameters)
{
    (new controllers\AmoCRMIntegrationController())->ActionGetPipelines($Parameters);
}, false, true);

Router::Add("^/connect/onlinechat/amocrm/{0,1}", function($Parameters)
{
    (new controllers\AmoCRMIntegrationController())->ActionConnectToOnlineChat($Parameters);
}, false, true);

Router::Add("^/disconnect/onlinechat/amocrm/{0,1}", function($Parameters)
{
    (new controllers\AmoCRMIntegrationController())->ActionDisconnectOnlineChat($Parameters);
}, false, true);

Router::Add("^/get/amocrm/channel/{0,1}", function($Parameters)
{
    (new controllers\AmoCRMIntegrationController())->ActionGetAmoCRMIntegrationById($Parameters);
}, false, true);

Router::Add("^/update/amocrm/{0,1}", function($Parameters)
{
    (new controllers\AmoCRMIntegrationController())->ActionUpdateAmoCRMSettings($Parameters);
}, false, true);

Router::Add("^/get/amocrm/{0,1}", function($Parameters)
{
    (new controllers\AmoCRMIntegrationController())->ActionGetAmoCRMIntegration($Parameters);
}, false, true);

Router::Add("^/delete/amocrm/{0,1}", function($Parameters)
{
    (new controllers\AmoCRMIntegrationController())->ActionDeleteIntegration($Parameters);
}, false, true);

Router::Add("^/get/id/amocrm/{0,1}", function($Parameters)
{
    (new controllers\AmoCRMIntegrationController())->ActionGetAllTask($Parameters);
}, false, true);

Router::Add("^/delete/id/amocrm/{0,1}", function($Parameters)
{
    (new controllers\AmoCRMIntegrationController())->ActionDeleteTask($Parameters);
}, false, true);


/**
 * Megaplan
 */
Router::Add("^/webhook/megaplan/{0,1}", function($Parameters)
{
    $Token = (!empty($Parameters["Get"]["token"]) ? $Parameters["Get"]["token"] : $Parameters["Post"]["token"]);
    if(controllers\MegaplanIntegrationController::CheckAccess($Token))
        (new controllers\MegaplanIntegrationController)->ActionMegaplanWebhook($Parameters);
});


Router::Add("^/connect/megaplan/{0,1}", function($Parameters)
{
    $Token = (!empty($Parameters["Get"]["token"]) ? $Parameters["Get"]["token"] : $Parameters["Post"]["token"]);
    if(controllers\MegaplanIntegrationController::CheckAccess($Token))
        (new controllers\MegaplanIntegrationController)->ActionConnectMegaplan($Parameters);
});


Router::Add("^/get/megaplan/statuses/{0,1}", function($Parameters)
{
    (new controllers\MegaplanIntegrationController())->ActionGetFunnels($Parameters);
}, false, true);


Router::Add("^/get/megaplan/{0,1}", function($Parameters)
{
    (new controllers\MegaplanIntegrationController())->ActionGetMegaplamIntegration($Parameters);
}, false, true);


Router::Add("^/update/megaplan/{0,1}", function($Parameters)
{
    (new controllers\MegaplanIntegrationController())->ActionUpdateMegaplanSettings($Parameters);
}, false, true);


/**
 * Yclients
 */
Router::Add("^/connection/yclients/{0,1}", function($Parameters)
{
    (new controllers\YclientsController())->ActionConnectYclients($Parameters);
}, false, true);

Router::Add("^/webhook/yclients/{0,1}", function($Parameters)
{
    $Token = (!empty($Parameters["Get"]["token"]) ? $Parameters["Get"]["token"] : $Parameters["Post"]["token"]);
    if(controllers\YclientsController::CheckAccess($Token))
        (new controllers\YclientsController())->ActionYclientsWebhook($Parameters);
});

Router::Add("^/create/yclients/task/{0,1}", function($Parameters)
{
    (new controllers\YclientsController())->ActionCreateNewTask($Parameters);
}, false, true);

Router::Add("^/update/yclients/task/{0,1}", function($Parameters)
{
    (new controllers\YclientsController())->ActionUpdateTask($Parameters);
}, false, true);

Router::Add("^/get/yclients/task/{0,1}", function($Parameters)
{
    (new controllers\YclientsController())->ActionGetTask($Parameters);
}, false, true);

Router::Add("^/get/all/yclients/task/{0,1}", function($Parameters)
{
    (new controllers\YclientsController())->ActionGetAllTask();
}, false, true);

Router::Add("^/get/yclients/services/{0,1}", function($Parameters)
{
    (new controllers\YclientsController())->ActionGetServices($Parameters);
}, false, true);

Router::Add("^/get/yclients/clients/category/{0,1}", function($Parameters)
{
    (new controllers\YclientsController())->ActionGetClientsCategory($Parameters);
}, false, true);

Router::Add("^/get/yclients/masters/{0,1}", function($Parameters)
{
    (new controllers\YclientsController())->ActionGetMasters($Parameters);
}, false, true);

Router::Add("^/get/yclients/records/category/{0,1}", function($Parameters)
{
    (new controllers\YclientsController())->ActionGetRecordsCategory($Parameters);
}, false, true);

Router::Add("^/delete/yclients/task/{0,1}", function($Parameters)
{
    (new controllers\YclientsController())->ActionDeleteTask($Parameters);
}, false, true);

Router::Add("^/delete/yclients/{0,1}", function($Parameters)
{
    (new controllers\YclientsController())->ActionDeleteIntegration();
}, false, true);


Router::Add("^/get/yclients/{0,1}", function($Parameters)
{
    (new controllers\YclientsController())->ActionGetYclients();
}, false, true);

Router::Add("^/update/yclients/{0,1}", function($Parameters)
{
    (new controllers\YclientsController())->ActionUpdateYclientsSettings($Parameters);
}, false, true);


/**
 * Bitrix
 */
Router::Add("^/install/bitrix/{0,1}", function($Parameters)
{
    $Token = (!empty($Parameters["Get"]["token"]) ? $Parameters["Get"]["token"] : $Parameters["Post"]["token"]);
    if(controllers\BitrixIntegrationController::CheckAccess($Token))
        (new controllers\BitrixIntegrationController())->ActionInstallApp($Parameters);
});


Router::Add("^/webhooks/bitrix/deal/update{0,1}", function($Parameters)
{
    $Token = (!empty($Parameters["Get"]["token"]) ? $Parameters["Get"]["token"] : $Parameters["Post"]["token"]);
    if(controllers\BitrixIntegrationController::CheckAccess($Token))
        (new controllers\BitrixIntegrationController())->ActionWebhooks($Parameters);
});

Router::Add("^/webhooks/bitrix/deal/add{0,1}", function($Parameters)
{
    $Token = (!empty($Parameters["Get"]["token"]) ? $Parameters["Get"]["token"] : $Parameters["Post"]["token"]);
    if(controllers\BitrixIntegrationController::CheckAccess($Token))
        (new controllers\BitrixIntegrationController())->ActionWebhooks($Parameters);
});


Router::Add("^/get/bitrix/funnels/{0,1}", function($Parameters)
{
    (new controllers\BitrixIntegrationController())->ActionGetFunnels($Parameters);
}, false, true);


Router::Add("^/get/bitrix/{0,1}", function($Parameters)
{
    (new controllers\BitrixIntegrationController())->ActionGetBitrixIntegration($Parameters);
}, false, true);


Router::Add("^/update/bitrix/{0,1}", function($Parameters)
{
    (new controllers\BitrixIntegrationController())->ActionUpdateBitrixIntegration($Parameters);
}, false, true);


Router::Add("^/connection/bitrix/{0,1}", function($Parameters)
{
    (new controllers\BitrixIntegrationController())->ActionConnectUserToBitrix($Parameters);
}, false, true);


Router::Add("^/delete/bitrix/{0,1}", function($Parameters)
{
    (new controllers\BitrixIntegrationController())->ActionDeleteBitrixIntegration($Parameters);
}, false, true);


/**
 * Finance
 */
Router::Add("^/add/tariff/{0,1}", function($Parameters)
{
    (new controllers\TariffController())->ActionAddTariff($Parameters);
}, false, true);

Router::Add("^/update/tariff/{0,1}", function($Parameters)
{
    (new controllers\TariffController())->ActionUpdateTariff($Parameters);
}, false, true);

Router::Add("^/get/tariff/{0,1}", function($Parameters)
{
    (new controllers\TariffController())->ActionGetTariff($Parameters);
}, false, true);

Router::Add("^/delete/tariff/{0,1}", function($Parameters)
{
    (new controllers\TariffController())->ActionDeleteTariff($Parameters);
}, false, true);


Router::Add("^/add/sales/{0,1}", function($Parameters)
{
    (new controllers\TariffController())->ActionAddSales($Parameters);
}, false, true);

Router::Add("^/update/sales/{0,1}", function($Parameters)
{
    (new controllers\TariffController())->ActionUpdateSales($Parameters);
}, false, true);

Router::Add("^/get/sales/{0,1}", function($Parameters)
{
    (new controllers\TariffController())->ActionGetSales($Parameters);
}, false, true);

Router::Add("^/delete/sales/{0,1}", function($Parameters)
{
    (new controllers\TariffController())->ActionDeleteSales($Parameters);
}, false, true);


Router::Add("^/create/user-tariff/{0,1}", function($Parameters)
{
    (new controllers\UserTariffController())->ActionCreateUserTariff($Parameters);
}, false, true);

Router::Add("^/delete/user-tariff/{0,1}", function($Parameters)
{
    (new controllers\UserTariffController())->ActionDeleteUserTariff($Parameters);
}, false, true);

Router::Add("^/get/user-tariff/{0,1}", function($Parameters)
{
    (new controllers\UserTariffController())->ActionGetTariff($Parameters);
}, false, true);

Router::Add("^/get/all/user-tariff/{0,1}", function($Parameters)
{
    (new controllers\UserTariffController())->ActionGetAllUserTariff($Parameters);
}, false, true);

Router::Add("^/update/user-tariff/{0,1}", function($Parameters)
{
    (new controllers\UserTariffController())->ActionUpdateTariff($Parameters);
}, false, true);

Router::Add("^/extention/user-tariff/{0,1}", function($Parameters)
{
    (new controllers\UserTariffController())->ActionUpdateTariffAfterPay($Parameters);
}, false, true);


/**
 * Robokassa
 */
Router::Add("^/link/robokassa/{0,1}", function($Parameters)
{
    (new controllers\RobokassaController())->ActionCreateLink($Parameters);
}, false, true);

Router::Add("^/result/robokassa/{0,1}", function($Parameters)
{
    $Token = (!empty($Parameters["Get"]["token"]) ? $Parameters["Get"]["token"] : $Parameters["Post"]["token"]);
    if(controllers\RobokassaController::CheckAccess($Token))
        (new controllers\RobokassaController())->ActionResultUrl($Parameters);
});


/**
 * Allifiate
 */
Router::Add("^/create/affiliate/url/{0,1}", function($Parameters)
{
    (new controllers\AffiliateController())->ActionCreateUrl($Parameters);
}, false, true);

Router::Add("^/get/all/affiliate/{0,1}", function($Parameters)
{
    (new controllers\AffiliateController())->ActionGetAllAffiliate($Parameters);
}, false, true);

Router::Add("^/delete/affiliate/{0,1}", function($Parameters)
{
    (new controllers\AffiliateController())->ActionDeleteAffiliate($Parameters);
}, false, true);

Router::Add("^/get/affiliate/{0,1}", function($Parameters)
{
    (new controllers\AffiliateController())->ActionGetAffiliateReferral($Parameters);
}, false, true);

Router::Add("^/update/affiliate/{0,1}", function($Parameters)
{
    (new controllers\AffiliateController())->ActionUpdateAffiliate($Parameters);
}, false, true);

Router::Add("^/payment/affiliate/{0,1}", function($Parameters)
{
    (new controllers\AffiliateController())->ActionCreateOperationOnPay($Parameters);
}, false, true);

Router::Add("^/get/all/payment/affiliate/{0,1}", function($Parameters)
{
    (new controllers\AffiliateController())->ActionGetAllOperation($Parameters);
}, false, true);

Router::Add("^/count/clicks/{0,1}", function($Parameters)
{
    $Token = (!empty($Parameters["Get"]["token"]) ? $Parameters["Get"]["token"] : $Parameters["Post"]["token"]);
    if(controllers\AffiliateController::CheckAccess($Token))
        (new controllers\AffiliateController())->ActionCountClicks($Parameters);
});

Router::Add("^/get/patrner/affiliate/{0,1}", function($Parameters)
{
    (new controllers\AffiliateController())->ActionGetAffiliateStatuses($Parameters);
}, false, true);


/**
 * IgnoreList
 */
Router::Add("^/add/phone/ignore/{0,1}", function($Parameters)
{
    (new controllers\IgnoreListController())->ActionAddPhone($Parameters);
}, false, true);

Router::Add("^/update/phone/ignore/{0,1}", function($Parameters)
{
    (new controllers\IgnoreListController())->ActionUpdatePhone($Parameters);
}, false, true);


Router::Add("^/delete/phone/ignore/{0,1}", function($Parameters)
{
    (new controllers\IgnoreListController())->ActionDeleteIgnorePhone($Parameters);
}, false, true);

Router::Add("^/get/all/phone/ignore/{0,1}", function($Parameters)
{
    (new controllers\IgnoreListController())->ActionGetAllPhone($Parameters);
}, false, true);

Router::Add("^/get/phone/ignore/{0,1}", function($Parameters)
{
    (new controllers\IgnoreListController())->ActionGetPhone($Parameters);
}, false, true);


/**
 * Folder
 */
Router::Add("^/get/folder/all/{0,1}", function($Parameters)
{
    (new controllers\FolderController())->ActionGetAllFolders($Parameters);
}, false, true);

Router::Add("^/create/folder/{0,1}", function($Parameters)
{
    (new controllers\FolderController())->ActionCreateFolder($Parameters);
}, false, true);

Router::Add("^/delete/folder/{0,1}", function($Parameters)
{
    (new controllers\FolderController())->ActionDeleteFolder($Parameters);
}, false, true);

Router::Add("^/get/folder/dialogues/{0,1}", function($Parameters)
{
    (new controllers\FolderController())->ActionGetDialoguesInFolder($Parameters);
}, false, true);

Router::Add("^/update/folder/name/{0,1}", function($Parameters)
{
    (new controllers\FolderController())->ActionRenameFolder($Parameters);
}, false, true);


/**
 * User
 */
Router::Add("^/get/user/{0,1}", function($Parameters)
{
    (new controllers\UserController())->ActionGetUser($Parameters);
}, false, true);

Router::Add("^/create/user/{0,1}", function($Parameters)
{
    (new controllers\UserController())->ActionUserRegistration($Parameters);
}, true);

Router::Add("^/get/user/avatar/{0,1}", function($Parameters)
{
    (new controllers\UserController())->ActionGetUserAvatar($Parameters);
}, false, true);

Router::Add("^/set/user/password/{0,1}", function($Parameters)
{
    (new controllers\UserController())->ActionSetUserPassword($Parameters);
}, false, true);

Router::Add("^/set/user/avatar/{0,1}", function($Parameters)
{
    (new controllers\UserController())->ActionSetUserAvatar($Parameters);
}, false, true);

Router::Add("^/user/grant/rights/{0,1}", function($Parameters)
{
    (new controllers\UserController())->ActionGrantRights($Parameters);
}, false, true);


/**
 * VenomBot
 */
Router::Add("^/get/venombot/whatsapps/{0,1}", function($Parameters)
{
    $Token = (!empty($Parameters["Get"]["token"]) ? $Parameters["Get"]["token"] : $Parameters["Post"]["token"]);
    if(controllers\VenomBotController::CheckAccess($Token))
        (new controllers\VenomBotController())->ActionGetWhatsapps($Parameters);
});

Router::Add("^/get/venombot/affordable-phone-check/{0,1}", function($Parameters)
{
    $Token = (!empty($Parameters["Get"]["token"]) ? $Parameters["Get"]["token"] : $Parameters["Post"]["token"]);
    if(controllers\VenomBotController::CheckAccess($Token))
        (new controllers\VenomBotController())->ActionAffordablePhoneCheck($Parameters);
});

Router::Add("^/event/venombot/whatsapp-session-invalid/{0,1}", function($Parameters)
{
    $Token = (!empty($Parameters["Get"]["token"]) ? $Parameters["Get"]["token"] : $Parameters["Post"]["token"]);
    if(controllers\VenomBotController::CheckAccess($Token))
        (new controllers\VenomBotController())->EventWhatsappSessionInvalid($Parameters);
});

Router::Add("^/event/venombot/message-status-update/{0,1}", function($Parameters)
{
    $Token = (!empty($Parameters["Get"]["token"]) ? $Parameters["Get"]["token"] : $Parameters["Post"]["token"]);
    if(controllers\VenomBotController::CheckAccess($Token))
        (new controllers\VenomBotController())->EventMessageStatusUpdate($Parameters);
});

Router::Add("^/event/venombot/whatsapp-disconnect/{0,1}", function($Parameters)
{
    $Token = (!empty($Parameters["Get"]["token"]) ? $Parameters["Get"]["token"] : $Parameters["Post"]["token"]);
    if(controllers\VenomBotController::CheckAccess($Token))
        (new controllers\VenomBotController())->EventWhatsappDisconnect($Parameters);
});

Router::Add("^/event/venombot/whatsapp-connected/{0,1}", function($Parameters)
{
    $Token = (!empty($Parameters["Get"]["token"]) ? $Parameters["Get"]["token"] : $Parameters["Post"]["token"]);
    if(controllers\VenomBotController::CheckAccess($Token))
        (new controllers\VenomBotController())->EventWhatsappConnected($Parameters);
});

Router::Add("^/update/venombot/whatsapps/session/{0,1}", function($Parameters)
{
    $Token = (!empty($Parameters["Get"]["token"]) ? $Parameters["Get"]["token"] : $Parameters["Post"]["token"]);
    if(controllers\VenomBotController::CheckAccess($Token))
        (new controllers\VenomBotController())->ActionUpdateSessionWhatsapp($Parameters);
});

Router::Add("^/put/venombot/whatsapps/message/{0,1}", function($Parameters)
{
    $Token = (!empty($Parameters["Get"]["token"]) ? $Parameters["Get"]["token"] : $Parameters["Post"]["token"]);
    if(controllers\VenomBotController::CheckAccess($Token))
        (new controllers\VenomBotController())->ActionOnMessage($Parameters);
});

Router::Add("^/get/venombot/resource/{0,1}", function($Parameters)
{
    $Token = (!empty($Parameters["Get"]["token"]) ? $Parameters["Get"]["token"] : $Parameters["Post"]["token"]);
    if(controllers\VenomBotController::CheckAccess($Token))
        (new controllers\VenomBotController())->ActionGetResouce($Parameters);
});


/**
 * Facebook
 */
Router::Add("^/create/facebook/{0,1}", function($Parameters)
{
    (new controllers\FacebookController())->ActionCreate($Parameters);
}, false, true);

Router::Add("^/get/facebook/all/{0,1}", function($Parameters)
{
    (new controllers\FacebookController())->ActionGetAllModels($Parameters);
}, false, true);


/**
 * InstagramApi
 */
Router::Add("^/get/instagram-api/accounts/{0,1}", function($Parameters)
{
    (new controllers\InstagramApiController())->ActionGetAccounts($Parameters);
}, false, true);

Router::Add("^/get/instagram-api/all/{0,1}", function($Parameters)
{
    (new controllers\InstagramApiController())->ActionGetAllModels($Parameters);
}, false, true);

Router::Add("^/connect/instagram-api/{0,1}", function($Parameters)
{
    (new controllers\InstagramApiController())->ActionConnectAccount($Parameters);
}, false, true);

Router::Add("^/update/instagram-api/{0,1}", function($Parameters)
{
    (new controllers\InstagramApiController())->ActionEditInstagramApi($Parameters);
}, false, true);

Router::Add("^/delete/instagram-api/{0,1}", function($Parameters)
{
    (new controllers\InstagramApiController())->ActionDeleteInstagramApi($Parameters);
}, false, true);

Router::Add("^/webhooks/instagram-api/{0,1}", function($Parameters)
{
    $Token = (!empty($Parameters["Get"]["token"]) ? $Parameters["Get"]["token"] : $Parameters["Post"]["token"]);
    if(controllers\InstagramApiController::CheckAccess($Token))
        (new controllers\InstagramApiController())->ActionWebhooks($Parameters);
});


/**
 * MassSending
 */

Router::Add("^/create/mass-sending/{0,1}", function($Parameters)
{
    (new controllers\MassSendingController())->ActionCreateMassSending($Parameters);
}, false, true);

Router::Add("^/mass-sending/start/{0,1}", function($Parameters)
{
    (new controllers\MassSendingController())->ActionStartMassSending($Parameters);
}, false, true);

Router::Add("^/mass-sending/stop/{0,1}", function($Parameters)
{
    (new controllers\MassSendingController())->ActionStopMassSending($Parameters);
}, false, true);

Router::Add("^/delete/mass-sending/{0,1}", function($Parameters)
{
    (new controllers\MassSendingController())->ActionDeleteMassSending($Parameters);
}, false, true);

Router::Add("^/get/mass-sending/all/{0,1}", function($Parameters)
{
    (new controllers\MassSendingController())->ActionGetAllMassSendings($Parameters);
}, false, true);

Router::Add("^/get/mass-sending/{0,1}", function($Parameters)
{
    (new controllers\MassSendingController())->ActionGetMassSending($Parameters);
}, false, true);

Router::Add("^/update/mass-sending/{0,1}", function($Parameters)
{
    (new controllers\MassSendingController())->ActionUpdateMassSending($Parameters);
}, false, true);


/**
 * DynamicMassSending
 */

Router::Add("^/create/dynamic-mass-sending/{0,1}", function($Parameters)
{
    (new controllers\DynamicMassSendingController())->ActionCreateDynamicMassSending($Parameters);
}, false, true);

Router::Add("^/dynamic-mass-sending/start/{0,1}", function($Parameters)
{
    (new controllers\DynamicMassSendingController())->ActionStartDynamicMassSending($Parameters);
}, false, true);

Router::Add("^/dynamic-mass-sending/stop/{0,1}", function($Parameters)
{
    (new controllers\DynamicMassSendingController())->ActionStopDynamicMassSending($Parameters);
}, false, true);

Router::Add("^/delete/dynamic-mass-sending/{0,1}", function($Parameters)
{
    (new controllers\DynamicMassSendingController())->ActionDeleteDynamicMassSending($Parameters);
}, false, true);

Router::Add("^/get/dynamic-mass-sending/all/{0,1}", function($Parameters)
{
    (new controllers\DynamicMassSendingController())->ActionGetAllDynamicMassSendings($Parameters);
}, false, true);

Router::Add("^/get/dynamic-mass-sending/{0,1}", function($Parameters)
{
    (new controllers\DynamicMassSendingController())->ActionGetDynamicMassSending($Parameters);
}, false, true);

Router::Add("^/get/dynamic-mass-sending/avatar/{0,1}", function($Parameters)
{
    (new controllers\DynamicMassSendingController())->ActionGetDynamicMassSendingAvatar($Parameters);
}, false, true);

Router::Add("^/update/dynamic-mass-sending/{0,1}", function($Parameters)
{
    (new controllers\DynamicMassSendingController())->ActionUpdateDynamicMassSending($Parameters);
}, false, true);


/**
 * Upload
 */
Router::Add("^/upload/file/{0,1}", function($Parameters)
{
    $Run = false;
    try
    {
        if(Authentication::IsAuthAccess())
        {
            (new controllers\UploadController())->ActionUploadFile($Parameters);
            $Run = true;
        }
    }
    catch(Exception $error)
    {
        if($error->getCode() != ACCESS_DENIED && $error->getCode() != USER_NOT_AUTH)
            throw $error;
    }

    try
    {
        if(!$Run && (controllers\UploadController::CheckAccess(classes\Headers::GetHeader("Authorization"))))
        {
            Authentication::SetAuthUser(models\User::FindById(classes\Headers::GetHeader("User-Id")), SYSTEM_SESSION);
            (new controllers\UploadController())->ActionUploadFile($Parameters);
            $Run = true;
        }
    }
    catch(Exception $error)
    {
        if($error->getCode() != ACCESS_DENIED && $error->getCode() != USER_NOT_AUTH)
            throw $error;
    }

    if(!$Run)
        throw new Exception("Access is denied", ACCESS_DENIED);
});


/**
 * File
 */
Router::Add("^/get/dynamic/resource/{0,1}", function($Parameters)
{
    $Run = false;
    try
    {
        if(Authentication::IsAuthAccess())
        {
            (new controllers\DynamicResourceController())->ActionGetDynamicResource($Parameters);
            $Run = true;
        }
    }
    catch(Exception $error)
    {
        if($error->getCode() != ACCESS_DENIED && $error->getCode() != USER_NOT_AUTH)
            throw $error;
    }

    try
    {
        if(!$Run && (controllers\DynamicResourceController::CheckAccess(empty($Parameters['Get']['token']) ? classes\Headers::GetHeader("Authorization") : $Parameters['Get']['token'], $Parameters['Get']['uid'])))
        {
            Authentication::SetAuthUser(models\User::FindById(empty($Parameters['Get']['user-id']) ? classes\Headers::GetHeader("User-Id") : (int)$Parameters['Get']['user-id']), SYSTEM_SESSION);
            (new controllers\DynamicResourceController())->ActionGetDynamicResource($Parameters);
            $Run = true;
        }
    }
    catch(Exception $error)
    {
        if($error->getCode() != ACCESS_DENIED && $error->getCode() != USER_NOT_AUTH)
            throw $error;
    }

    if(!$Run)
        throw new Exception("Access is denied", ACCESS_DENIED);
});

Router::Add("^/get/static/resource/{0,1}", function($Parameters)
{
    (new controllers\StaticResourceController())->ActionGetImageResource($Parameters);
}, false, true);


/**
 * InstagramSDK
 */
Router::Add("^/get/instagram-sdk/instagrams/{0,1}", function($Parameters)
{
    $Token = (!empty($Parameters["Get"]["token"]) ? $Parameters["Get"]["token"] : $Parameters["Post"]["token"]);
    if(controllers\InstagramSdkController::CheckAccess($Token))
        (new controllers\InstagramSdkController())->ActionGetInstagrams($Parameters);
});


Router::Add("^/put/instagram-sdk/instagrams/message/{0,1}", function($Parameters)
{
    $Token = (!empty($Parameters["Get"]["token"]) ? $Parameters["Get"]["token"] : $Parameters["Post"]["token"]);
    if(controllers\InstagramSdkController::CheckAccess($Token))
        (new controllers\InstagramSdkController())->ActionOnMessage($Parameters);
});


Router::Add("^/get/instagram-sdk/resource/{0,1}", function($Parameters)
{
    $Token = (!empty($Parameters["Get"]["token"]) ? $Parameters["Get"]["token"] : $Parameters["Post"]["token"]);
    if(controllers\InstagramSdkController::CheckAccess($Token))
        (new controllers\InstagramSdkController())->ActionGetResouce($Parameters);
});


Router::Add("^/put/instagram-sdk/sync-dialogues/{0,1}", function($Parameters)
{
    $Token = (!empty($Parameters["Get"]["token"]) ? $Parameters["Get"]["token"] : $Parameters["Post"]["token"]);
    if(controllers\InstagramSdkController::CheckAccess($Token))
        (new controllers\InstagramSdkController())->ActionSyncDialogues($Parameters);
});



/**
 * MAIL
 */
Router::Add("^/webhooks/smtp/{0,1}", function($Parameters)
{
    (new controllers\MailController())->ActionSMTPWebhooks($Parameters);
});

Router::Add("^/email/confirmation/{0,1}", function($Parameters)
{
    (new controllers\MailController())->ActionEmailConfirmation($Parameters);
});

Router::Add("^/email/unsubscribe/{0,1}", function($Parameters)
{
    (new controllers\MailController())->ActionEmailUnsubscribe($Parameters);
});


/**
 * AUTORESPONDER
 */
Router::Add("^/create/autoresponder/{0,1}", function($Parameters)
{
    controllers\AutoresponderController::ActionCreate($Parameters);
}, false, true);

Router::Add("^/update/autoresponder/{0,1}", function($Parameters)
{
    controllers\AutoresponderController::ActionEdit($Parameters);
}, false, true);

Router::Add("^/delete/autoresponder/{0,1}", function($Parameters)
{
    controllers\AutoresponderController::ActionDelete($Parameters);
}, false, true);

Router::Add("^/autoresponder/stop/{0,1}", function($Parameters)
{
    controllers\AutoresponderController::ActionStop($Parameters);
}, false, true);

Router::Add("^/autoresponder/start/{0,1}", function($Parameters)
{
    controllers\AutoresponderController::ActionStart($Parameters);
}, false, true);

Router::Add("^/get/autoresponder/all/{0,1}", function($Parameters)
{
    controllers\AutoresponderController::ActionGetAll($Parameters);
}, false, true);

Router::Add("^/get/autoresponder/{0,1}", function($Parameters)
{
    controllers\AutoresponderController::ActionGet($Parameters);
}, false, true);







//Delete on release
Router::Add("^/admin", function($Parameters)
{
    if(!empty(Authentication::GetAuthUser()) && Authentication::GetAuthUser()->GetUserType() == User::USER_TYPE_ADMIN)
        require_once ROOT."/admin/".$Parameters["Uri"][0].".php";
    else
        View::Print("CodeErrors", ["Code" => NOT_FOUND]);
});