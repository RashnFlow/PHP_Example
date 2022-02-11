<?php


namespace migrations;


use models\Migration;
use models\Purse;

class PurseMigration extends Migration
{
    protected ?string $TableName = null;


    public function __construct()
    {
        $this->TableName = Purse::$Table;
    }


    public function Create() : bool
    {
        return parent::Create([
            '"PurseId"          SERIAL PRIMARY KEY',
            '"Balance"          NUMERIC NULL',
            '"UserId"           INT NOT NULL',
            '"ReplenishmentAt"  INT NULL'
        ]);
    }
}