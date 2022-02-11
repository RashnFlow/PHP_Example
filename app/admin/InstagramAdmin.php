<?php

use controllers\InstagramSdkController;
use factories\UserFactory;
use models\Authentication;
use models\Instagram;

Authentication::SetAuthUser(UserFactory::GetSystemUser(), SYSTEM_SESSION);


if(!empty($_POST) && !empty($_POST['instagram_id']))
{
    echo '<div class="info">';
    $Instagram = Instagram::FindById((int)$_POST['instagram_id']);
    if(empty($Instagram))
        echo '<font color="#ff9900"><h5>Инстаграм не найден</h5></font>';
    else
    {
        try
        {
            $InstagramSdk = new InstagramSdkController();

            if(isset($_POST['action_start']))
                $InstagramSdk->InitInstagram($Instagram);

            if(isset($_POST['action_stop']))
                $InstagramSdk->CloseInstagram($Instagram);

            sleep(10);
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
                width: 1200px;
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
        </style>
    </head>
    <body>
        <div class="MainContainer">
            <a href="./IndexAdmin">Главная</a>
            <center><h1>Админка v0.1</h1></center>
            <center><h3>Инстаграмы</h3></center>
            <table>
                <tr>
                    <td>Id</td>
                    <td>Login</td>
                    <td>IsActive</td>
                    <td>Статус</td>
                    <td>Действия</td>
                </tr>
                <?php
                    foreach(Instagram::FindAll() as $Instagram)
                        echo '<form method="POST"><input hidden name="instagram_id" value="' . $Instagram->GetId() . '"><tr><td>' . $Instagram->GetId() . '</td><td>' . $Instagram->GetLogin() . '</td><td>' . ($Instagram->GetIsActive() ? '<font color="#33cc33">true</font>' : 'false') . '</td><td>' . $Instagram->GetStatus() . '</td><td><button name="action_' . ($Instagram->GetIsActive() ? 'stop' : 'start') . '">' . ($Instagram->GetIsActive() ? 'Остановить' : 'Запустить') . '</button></td></tr></form>';
                ?>
            </table>
            <center><h3>Логи Inst</h3></center>
            <textarea id="textarea_logs"></textarea>
        </div>

        <script>
            fetch('./LogsAdmin?filename=LogInstagram[<?php echo urldecode(date("d.m.Y"));?>].log&only_read=true').then((response) => {
                response.text().then((body) => {
                    var textarea = document.getElementById('textarea_logs');
                    textarea.value = body;
                    textarea.scrollTop = textarea.scrollHeight;
                })
            });
        </script>
    </body>
</html>