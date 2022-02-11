<?php


namespace migrations;


use models\Migration;
use models\YclientsRecords;

class YclientsRecordsMigration extends Migration
{
    protected ?string $TableName = null;


    public function __construct()
    {
        $this->TableName = YclientsRecords::$Table;
    }


    public function Create() : bool
    {
        return parent::Create([
            '"YclientsRecordId"     SERIAL PRIMARY KEY',
            '"UserId"               INT NOT NULL',
            '"RecordId"             INT NOT NULL',
            '"RecordDate"           TEXT NOT NULL',
            '"MasterId"             INT NULL',
            '"RecordStatus"         INT NULL DEFAULT 0',
            '"RecordCategory"       JSONB NOT NULL DEFAULT \'[]\'',
            '"RecordServices"       JSONB NOT NULL DEFAULT \'[]\'',
        ]);
    }
}