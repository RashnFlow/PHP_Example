<?php


namespace migrations;


use models\Migration;
use models\Operation;


class OperationMigration extends Migration
{
    protected ?string $TableName = null;


    public function __construct()
    {
        $this->TableName = Operation::$Table;
    }


    public function Create() : bool
    {
        return parent::Create([
            '"OperationId"      SERIAL PRIMARY KEY',
            '"Sum"              INT NOT NULL',
            '"CreatedAt"        INT NOT NULL',
            '"PayDate"          INT NULL',
            '"UserId"           INT NOT NULL',
            '"IsPaid"           BOOLEAN NOT NULL DEFAULT FALSE',
            '"Data"             TEXT NULL',
        ]);
    }
}