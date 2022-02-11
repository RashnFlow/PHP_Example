<?php


namespace migrations;


use models\Migration;


class YclientsIntegrationMigration extends Migration
{
    protected ?string $TableName = YCLIENTS_INTEGRATION_TABLE;


    public function Create() : bool
    {
        return parent::Create([
            'yclients_integration_id    SERIAL PRIMARY KEY',
            'user_id                    INT NULL',
            'login                      TEXT NOT NULL',
            'password                   TEXT NOT NULL',
            'user_token                 TEXT NOT NULL',
            'tasks                      JSONB NOT NULL DEFAULT \'[]\'',
            'cache                      JSONB NOT NULL DEFAULT \'[]\'',
            "is_active                  BOOLEAN NOT NULL DEFAULT TRUE",
        ]);
    }
}

