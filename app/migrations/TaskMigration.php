<?php


namespace migrations;


use models\Migration;


class TaskMigration extends Migration
{
    protected ?string $TableName = TASK_TABLE;


    public function Create() : bool
    {
        return parent::Create([
            'task_id            SERIAL PRIMARY KEY',
            'type               VARCHAR(255) NOT NULL',
            'data               TEXT NOT NULL',
            'response           TEXT NULL',
            'is_running         BOOLEAN NOT NULL DEFAULT FALSE',
            'is_completed       BOOLEAN NOT NULL DEFAULT FALSE',
            "created_at         TIMESTAMP NOT NULL",
            "updated_at         TIMESTAMP NOT NULL",
            "failed             INT NULL"
        ]);
    }
}