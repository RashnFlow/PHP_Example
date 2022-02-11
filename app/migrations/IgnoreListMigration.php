<?php


namespace migrations;

use models\IgnoreList;
use models\Migration;

class IgnoreListMigration extends Migration
{
    protected ?string $TableName = null;


    public function __construct()
    {
        $this->TableName = IgnoreList::$Table;
    }


    public function Create() : bool
    {
        return parent::Create([
            '"IgnoreListId"          SERIAL PRIMARY KEY',
            '"UserId"                INT NOT NULL',
            '"IgnoreParameters"      JSONB NOT NULL DEFAULT \'[]\'',
        ]);
    }
}