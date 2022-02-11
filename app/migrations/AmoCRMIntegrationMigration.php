<?php


namespace migrations;


use models\Migration;


class AmoCRMIntegrationMigration extends Migration
{
    protected ?string $TableName = AMOCRM_INTEGRATION_TABLE;


    public function Create() : bool
    {
        return parent::Create([
            'amocrm_integration_id   SERIAL PRIMARY KEY',
            'user_id                 INT NULL',
            'accounts                JSONB NOT NULL DEFAULT \'[]\'',
            'profile_url             TEXT NOT NULL UNIQUE',
            'access_token            TEXT NOT NULL',
            'refresh_token           TEXT NOT NULL',
            'account_id              TEXT NULL',
            'scope_id                TEXT NULL',
            'bot_settings            JSONB NOT NULL DEFAULT \'[]\'',
            'new_dialog_action       JSONB NOT NULL DEFAULT \'[]\'',
            'funnel_action           JSONB NOT NULL DEFAULT \'[]\'',
            'cache                   JSONB NOT NULL DEFAULT \'[]\'',
            "is_active               BOOLEAN NOT NULL DEFAULT TRUE",
        ]);
    }
}

