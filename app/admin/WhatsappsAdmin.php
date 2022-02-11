<?php

use controllers\VenomBotController;
use factories\UserFactory;
use models\Authentication;
use models\Whatsapp;

Authentication::SetAuthUser(UserFactory::GetSystemUser(), SYSTEM_SESSION);


if(!empty($_POST) && !empty($_POST['whatsapp_id']))
{
    echo '<div class="info">';
    $Whatsapp = Whatsapp::FindById((int)$_POST['whatsapp_id']);
    if(empty($Whatsapp))
        echo '<font color="#ff9900"><h5>Вацап не найден</h5></font>';
    else
    {
        try
        {
            $Venom = new VenomBotController();
            if(isset($_POST['action_restart']))
                $Venom->RestartWhatsapp($Whatsapp);

            if(isset($_POST['action_start']))
                $Venom->InitWhatsapp($Whatsapp);

            if(isset($_POST['action_stop']))
                $Venom->CloseWhatsapp($Whatsapp);

            if(isset($_POST['action_delete_session']))
            {
                $Whatsapp->SetVenomSessions([]);
                $Whatsapp->Save();
            }

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
                max-width: 1000px;
            }
        </style>
    </head>
    <body>
        <div class="MainContainer">
            <a href="./IndexAdmin">Главная</a>
            <center><h1>Админка v0.1</h1></center>
            <center><h3>Вацапы</h3></center>
            <table>
                <tr>
                    <td>Id</td>
                    <td>Name</td>
                    <td>Phone</td>
                    <td>IsActive</td>
                    <td>Status</td>
                    <td>Действия</td>
                </tr>
                <?php
                    foreach(Whatsapp::FindAll() as $Whatsapp)
                        echo '<form method="POST"><input hidden name="whatsapp_id" value="' . $Whatsapp->GetId() . '"><tr><td>' . $Whatsapp->GetId() . '</td><td>' . $Whatsapp->GetName() . '</td><td>' . $Whatsapp->GetPhone() . '</td><td>' . ($Whatsapp->GetIsActive() ? '<font color="#33cc33">true</font>' : 'false') . '</td><td>' . $Whatsapp->GetStatus() . '</td><td><button name="action_' . ($Whatsapp->GetIsActive() ? 'stop' : 'start') . '">' . ($Whatsapp->GetIsActive() ? 'Остановить' : 'Запустить') . '</button></td><td><button name="action_restart">Перезагрузить</button></td><td><button name="action_delete_session">Удалить сессию</button></td></tr></form>';
                ?>
            </table>
            <center><h3>Логи Venom</h3></center>
            <textarea id="textarea_logs"></textarea>
        </div>

        <script>
            fetch('./LogsAdmin?filename=LogVenom[<?php echo urldecode(date("d.m.Y"));?>].log&only_read=true').then((response) => {
                response.text().then((body) => {
                    var textarea = document.getElementById('textarea_logs');
                    textarea.value = body;
                    textarea.scrollTop = textarea.scrollHeight;
                })
            });
        </script>
    </body>
</html>