<?php


/**
 * DataBase
 */
define("DB_IP", $_ENV["DB_IP"]);
define("DB_PORT", $_ENV["DB_PORT"]);
define("DB_NAME", $_ENV["DB_NAME"]);
define("DB_USER", $_ENV["DB_USER"]);
define("DB_PASSWORD", $_ENV["DB_PASSWORD"]);


/**
 * [USER]
 */
define("USER_UNKNOWN_AVATAR", "DialogUnknownAvatar.jpg");


/**
 * [WHATSAPP]
 */
define("WHATSAPP_UNKNOWN_QR_CODE", "WhatsappUnknownQRCode.jpg");


/**
 * [DIALOG]
 */
define("DIALOG_UNKNOWN_AVATAR", "DialogUnknownAvatar.jpg");


/**
 * [Language]
 */
define("LANGUAGE", $_ENV["LANGUAGE"]);


/**
 * [VenomBot]
 */
define("VENOM_BOT_API_KEY", $_ENV["SDK_API_KEY"]);
define("VENOM_BOT_IP", $_ENV["VENOM_BOT_IP"]);
define("VENOM_BOT_PORT", $_ENV["VENOM_BOT_PORT"]);


/**
 * [InstagramSdk]
 */
define("INSTAGRAM_SDK_API_KEY", $_ENV["SDK_API_KEY"]);
define("INSTAGRAM_SDK_IP", $_ENV["INSTAGRAM_SDK_IP"]);
define("INSTAGRAM_SDK_PORT", $_ENV["INSTAGRAM_SDK_PORT"]);


/**
 * [InstagramApi]
 */
define("INSTAGRAM_API_APP_ID", $_ENV["INSTAGRAM_API_APP_ID"]);
define("INSTAGRAM_API_APP_SECRET", $_ENV["INSTAGRAM_API_APP_SECRET"]);
define("INSTAGRAM_API_WEBHOOKS_KEY", $_ENV["INSTAGRAM_API_WEBHOOKS_KEY"]);


/**
 * [Mail]
 */
define("MAIL_API_USER_ID", $_ENV["MAIL_API_USER_ID"]);
define("MAIL_API_SECRET", $_ENV["MAIL_API_SECRET"]);


/**
 * [Bitrix]
 */
define("BITRIX_API_KEY", $_ENV["BITRIX_API_KEY"]);
define("BITRIX_CLIENT_SECRET", $_ENV["C_REST_CLIENT_SECRET"]);
define("BITRIX_CLIENT_ID", $_ENV["C_REST_CLIENT_ID"]);
define("BITRIX_EVENT_URL", $_ENV["EVENT_URL"]);
define("BITRIX_API_TOKEN", $_ENV["BITRIX_API_TOKEN"]);

/**
 * [AMOCRM]
 */
define('AMOCRM_WEBHOOK', $_ENV["AMOCRM_WEBHOOK"]);
define('AMOCRM_CLIENT_SECRET', $_ENV["AMOCRM_CLIENT_SECRET"]);
define('AMOCRM_CLIENT_ID', $_ENV["AMOCRM_CLIENT_ID"]);
define('AMOCRM_REDDIRECT_URI', $_ENV["AMOCRM_REDDIRECT_URI"]);
define("AMOCRM_API_KEY", $_ENV["AMOCRM_API_KEY"]);
define("AMOCRM_API_TOKEN", $_ENV["AMOCRM_API_TOKEN"]);
define("AMOCRM_BOT_SECRET", $_ENV["AMOCRM_BOT_SECRET"]);
define("AMOCRM_BOT_ID", $_ENV["AMOCRM_BOT_ID"]);

/**
 * [Megaplan]
 */
define("MEGAPLAN_API_KEY", $_ENV["MEGAPLAN_API_KEY"]);
define("MEGAPLAN_ACCESS_ID", $_ENV["MEGAPLAN_ACCESS_ID"]);
define("MEGAPLAN_SECRET_KEY", $_ENV["MEGAPLAN_SECRET_KEY"]);


/**
 * [Yclients]
 */
define("YCLIENTS_API_KEY", $_ENV["YCLIENTS_API_KEY"]);
define("YCLIENTS_WEBHOOK", $_ENV["YCLIENTS_WEBHOOK"]);
define("YCLIENTS_API_TOKEN", $_ENV["YCLIENTS_API_TOKEN"]);
define("YCLIENTS_BEARER_KEY", $_ENV["YCLIENTS_BEARER_KEY"]);


/**
 * [Robokassa]
 */
define("ROBOKASSA_API_KEY", $_ENV["ROBOKASSA_API_KEY"]);
define("ROBOKASSA_API_TOKEN", $_ENV["ROBOKASSA_API_TOKEN"]);
define("ROBOKASSA_PASSWORD_ONE", $_ENV["ROBOKASSA_PASSWORD_ONE"]);
define("ROBOKASSA_LOGIN", $_ENV["ROBOKASSA_LOGIN"]);


/**
 * [Affiliate]
 */
define("AFFILIATE_API_KEY", $_ENV["AFFILIATE_API_KEY"]);
define("AFFILIATE_API_TOKEN", $_ENV["AFFILIATE_API_TOKEN"]);


/**
 * [Upload Files]
 */
define("UPLOAD_FILE_API_KEY", $_ENV["UPLOAD_FILE_API_KEY"]);


/**
 * [Get Files]
 */
define("GET_FILE_API_KEY", $_ENV["GET_FILE_API_KEY"]);


/**
 * [MessageSocket]
 */
define("MESSAGE_SOCKET", $_ENV["MESSAGE_SOCKET"]);


/**
 * SystemSession
 */
define("SYSTEM_SESSION", "System");


/**
 * DOMAIN
 */
define("DOMAIN_API", $_ENV["DOMAIN_API"]);
define("DOMAIN_FRONT", $_ENV["DOMAIN_FRONT"]);
define("DOMAIN_HTTP", $_ENV["HTTP_SSL"] ? "https://" : "http://");
define("DOMAIN_API_URL", DOMAIN_HTTP . DOMAIN_API);
define("DOMAIN_FRONT_URL", DOMAIN_HTTP . DOMAIN_FRONT);
define("SERVER_IP", $_ENV["SERVER_IP"]);