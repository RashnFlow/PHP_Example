<?php

use factories\UserFactory;
use models\Authentication;
use models\Message;
use models\QueryCreator;

set_time_limit(0);
Authentication::SetAuthUser(UserFactory::GetSystemUser(), SYSTEM_SESSION);

try
{
    foreach(QueryCreator::Find(DIALOG_TABLE)->Run() as $Obj)
    {
        foreach(json_decode($Obj->messages, true) as $ObjMessage)
        {
            $Message = Message::FindById($ObjMessage);
            if(empty($Message))
                continue;
            $Message->SetDialogId((int)$Obj->dialog_id);
            $Message->Save();
        }
        echo "Convert: " . $Obj->dialog_id . "<br>";
    }
    echo "<span style='color: green;'>Complete</span>";
}
catch(Throwable $error)
{
    var_dump($error);
}