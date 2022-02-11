<?php

use controllers\VenomBotController;
use factories\UserFactory;
use models\ApiToken;
use models\Authentication;
use models\User;
use models\Whatsapp;

Authentication::SetAuthUser(UserFactory::GetSystemUser(), SYSTEM_SESSION);


if(!empty($_POST) && !empty($_POST['user_id']))
{
    echo '<div class="info">';
    $User = User::FindById((int)$_POST['user_id']);
    if(empty($User))
        echo '<font color="#ff9900"><h5>Пользователь не найден</h5></font>';
    else
    {
        try
        {
            if(isset($_POST['action_disable']))
                $User->SetIsActive(false);

            if(isset($_POST['action_enable']))
                $User->SetIsActive(true);

            if(isset($_POST['action_logout']))
                foreach(ApiToken::FindAllByUserId($User->GetId()) as $ApiToken)
                    $ApiToken->Delete();

            $User->Save();
            echo '<font color="#33cc33"><h5>Операция выполнена</h5></font>';
        }
        catch(Throwable $error)
        {
            echo '<font color="#ff9900"><h5>Ошибка при выполнении операции</h5><p>' . $error . '</p></font>';
        }
    }
    echo '</div>';
}
?>

<!DOCTYPE html>
<html>
    <head>
        <title>Админка</title>

        <style>
            * {
                box-sizing: border-box;
            }

            .MainContainer {
                background-color: #fff;
                width: 1000px;
                padding: 10px;

                position: absolute;
                left: 50%;
                transform: translateX(-50%);
            }

            body {
                background-color: rgb(230, 230, 230);
            }

            td {
                padding: 2px 5px;
            }

            textarea {
                width: 100%;
                height: 500px;
                white-space: nowrap;

                resize: none;
            }

            .info {
                max-width: 700px;
            }

            .TableContainer {
                width: 100%;

                overflow-x: auto;
            }
        </style>
    </head>
    <body>
        <div class="MainContainer">
            <a href="./IndexAdmin">Главная</a>
            <center><h1>Админка v0.1</h1></center>
            <center><h3>Пользователи</h3></center>
            <div class="TableContainer">
                <table>
                    <tr>
                        <td>Id</td>
                        <td>Login</td>
                        <td>Name</td>
                        <td>Phone</td>
                        <td>Email</td>
                        <td>UserType</td>
                        <td>IsActive</td>
                        <td>CreatedAt</td>
                        <td>Действия</td>
                    </tr>
                    <?php
                        foreach(User::FindAll() as $User)
                            echo '<form method="POST"><input hidden name="user_id" value="' . $User->GetId() . '"><tr><td>' . $User->GetId() . '</td><td>' . $User->GetLogin() .  '</td><td>' . $User->GetName() . '</td><td>' . $User->GetPhone() . '</td><td>' . $User->GetEmail() . '</td><td>' . $User->GetUserType() . '</td><td>' . ($User->GetIsActive() ? 'true' : 'false') . '</td><td>' . gmdate("d.m.Y H:i:s", $User->GetCreatedAt()) . '</td><td><button name="action_' . ($User->GetIsActive() ? 'disable' : 'enable') . '">' . ($User->GetIsActive() ? 'Отключить' : 'Включить') . '</button></td><td><button name="action_logout">Закрыть все сессии</button></td></tr></form>';
                    ?>
                </table>
            </div>
        </div>
    </body>
</html>