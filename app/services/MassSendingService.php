<?php


namespace services;

use factories\UserFactory;
use models\Authentication;
use models\MassSending;


/**
 * Init
 */
set_time_limit(0);
define("ROOT", str_replace("\\", "/", __DIR__ . "/.."));
require ROOT."/init.php";







/**
 * Service
 */
Authentication::SetAuthUser(UserFactory::GetSystemUser(), SYSTEM_SESSION);

echo "Initialization complete";

while(true)
{
    foreach (MassSending::FindAllEnable() as $MassSending)
        $MassSending->Send();

    sleep(1);
}