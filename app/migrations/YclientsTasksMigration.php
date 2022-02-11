<?php


namespace migrations;


use models\Migration;


class YclientsTasksMigration extends Migration
{
    protected ?string $TableName = YCLIENTS_INTEGRATION_TASK_TABLE;


    public function Create() : bool
    {
        return parent::Create([
            'task_id                    SERIAL PRIMARY KEY',
            'yclients_integration_id    INT NULL',
            'type                       TEXT NOT NULL',
            'task_name                  TEXT NOT NULL',
            'parameters                 JSONB NOT NULL DEFAULT \'[]\'',
            'ignore_phone               JSONB NOT NULL DEFAULT \'[]\''
        ]);
    }
}

