<?php

use classes\File;
use classes\Log;
use classes\Logger;
use factories\UserFactory;
use models\Authentication;


/**
 * Init
 */
set_time_limit(0);
define("ROOT", str_replace("\\", "/", __DIR__ . "/.."));
require ROOT."/init.php";


function DirectoryStructureScan($Dir = ROOT)
{
    $Out = [];
    foreach(scandir($Dir) as $obj)
    {
        if($obj == "." || $obj == "..")
            continue;

        $FileInfo = pathinfo("$Dir/$obj");

        if(is_dir("$Dir/$obj") && $FileInfo["filename"] != "vendor" && $FileInfo["filename"] != "node_modules")
            $Out = array_merge($Out, DirectoryStructureScan("$Dir/$obj"));
        else
        {
            if($FileInfo["extension"] != "php" && $FileInfo["extension"] != "js")
                continue;

            $Out["$Dir/$obj"] = filemtime("$Dir/$obj");
        }
    }

    return $Out;
}



/**
 * Service
 */
Authentication::SetAuthUser(UserFactory::GetSystemUser(), SYSTEM_SESSION);

echo "Initialization complete\n";

$DirectoryStructure = [];
if(File::Exists(ROOT_TEMP . "/UpdateService_DirectoryStructure.temp"))
{
    try
    {
        $DirectoryStructure = json_decode(File::ReadAllText(ROOT_TEMP . "/UpdateService_DirectoryStructure.temp"), true);
    }
    catch(Throwable $error)
    {
        Logger::Log(Log::TYPE_ERROR, "Ошибка при выгрузки временной файловой структуры проекта. Структура была сброшена", (string)$error);
    }
}

while(true)
{
    echo "==================================================================================\n";
    echo "Сканирование...\n";
    $DirectoryStructureTEMP = DirectoryStructureScan();
    echo "Завершено\n";
    echo "Получено " . count($DirectoryStructureTEMP) . " элементов\n";
    if($DirectoryStructure != $DirectoryStructureTEMP)
    {
        echo "Зафиксировано обновление проекта. Выполняется запуск скриптов...\n";
        try
        {
            migrations\MigrationCollection::Create();

            echo "Выполнено\n";
            $DirectoryStructure = $DirectoryStructureTEMP;
            File::WriteAllText(ROOT_TEMP . "/UpdateService_DirectoryStructure.temp", json_encode($DirectoryStructure));
        }
        catch(Throwable $error)
        {
            echo "Ошибка при запуске скриптов\n";
            var_dump($error);
            Logger::Log(Log::TYPE_ERROR, "Ошибка! Не удалось корректно выполнить запуск скриптов после обновления", (string)$error);
        }
    }
    else
        echo "Обновление проекта не зафиксированы\n";

    echo "Ожидание 1 мин\n";
    echo "==================================================================================\n";
    sleep(60);
}