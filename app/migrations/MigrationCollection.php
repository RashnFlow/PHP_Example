<?php


namespace migrations;


class MigrationCollection
{
    private function __construct() {}


    static private function GetMigrations() : array
    {
        $Migrations = [];
        foreach(scandir(ROOT . "/migrations") as $FileName)
            if(strripos($FileName, "Migration.php") !== false)
                $Migrations[] = "migrations\\" . basename($FileName, ".php");
        
        return $Migrations;
    }


    static public function Create()
    {
        foreach(self::GetMigrations() as $Migration)
            (new $Migration())->Create();
    }


    static public function Delete()
    {
        foreach(self::GetMigrations() as $Migration)
            (new $Migration())->Delete();
    }
}
