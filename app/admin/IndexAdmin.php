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
                width: 400px;
                padding: 10px;

                position: absolute;
                left: 50%;
                transform: translateX(-50%);
            }

            body {
                background-color: rgb(230, 230, 230);
            }
        </style>
    </head>
    <body>
        <div class="MainContainer">
            <center><h1>Админка v0.1</h1></center>
            <ul>
                <li><a href="./WhatsappsAdmin">Вацапы</a></li>
                <li><a href="./InstagramAdmin">Инстаграмы</a></li>
                <li><a href="./LogsAdmin">Логи</a></li>
                <li><a href="./UsersAdmin">Пользователи</a></li>
            </ul>
        </div>
    </body>
</html>