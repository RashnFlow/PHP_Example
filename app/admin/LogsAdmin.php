<?php

use classes\Directory;
use classes\File;
use factories\UserFactory;
use models\Authentication;

Authentication::SetAuthUser(UserFactory::GetSystemUser(), SYSTEM_SESSION);

if(!empty($_GET) && !empty($_GET['filename']))
{
    try
    {
        $FileAllText = File::ReadAllText(ROOT . '/logs_dump/' . $_GET['filename']);
    }
    catch(Throwable $error)
    {
        echo '<div class="info"><font color="#ff9900"><h5>Ошибка при выполнении операции</h5><p>' . $error . '</p></font></div>';
    }

    if($_GET['only_read'] == 'true')
    {
        echo $FileAllText;
        return;
    }
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
                width: 600px;
                padding: 10px;

                position: absolute;
                left: 50%;
                transform: translateX(-50%);
            }

            textarea {
                width: 100%;
                height: 500px;
                white-space: nowrap;

                resize: none;
            }

            body {
                background-color: rgb(230, 230, 230);
            }
        </style>
    </head>
    <body>
        <div class="MainContainer">
            <a href="./IndexAdmin">Главная</a>
            <center><h1>Админка v0.1</h1></center>
            <center><h3>Логи</h3></center>
            <ul>
                <?php
                    foreach(Directory::GetFiles( ROOT . '/logs_dump') as $File)
                        echo "<li><a href='./LogsAdmin?filename=" . urldecode($File) . "'>$File</a></li>";
                ?>
            </ul>
            <?php
            if(!empty($FileAllText))
                echo '<center><h3> ' . $_GET['filename'] .  ' </h3></center><textarea id="textarea_logs"> ' . $FileAllText .  ' </textarea>';
            ?>
        </div>

        <script>
            var textarea = document.getElementById('textarea_logs');
            textarea.scrollTop = textarea.scrollHeight;
        </script>
    </body>
</html>