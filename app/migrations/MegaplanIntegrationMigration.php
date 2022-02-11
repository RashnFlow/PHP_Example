<?php


namespace migrations;


use models\Migration;


class MegaplanIntegrationMigration extends Migration
{
    protected ?string $TableName = MEGAPLAN_INTEGRATION_TABLE;


    public function Create() : bool
    {
        return parent::Create([
            'megaplan_integration_id   SERIAL PRIMARY KEY',
            'user_id                 INT NULL',
            'accounts                JSONB NOT NULL DEFAULT \'[]\'',
            'profile_url             TEXT NOT NULL UNIQUE',
            'access_token            TEXT NOT NULL',
            'refresh_token           TEXT NOT NULL',
            'new_dialog_action       JSONB NOT NULL DEFAULT \'[]\'',
            'funnel_action           JSONB NOT NULL DEFAULT \'[]\'',
            'cache                   JSONB NOT NULL DEFAULT \'[]\'',
            "is_active               BOOLEAN NOT NULL DEFAULT TRUE",
        ]);
    }
}

