<?php


namespace migrations;


use models\Migration;


class BitrixIntegrationMigration extends Migration
{
    protected ?string $TableName = BITRIX_INTEGRATION_TABLE;


    public function Create() : bool
    {
        return parent::Create([
            'bitrix_integration_id   SERIAL PRIMARY KEY',
            'user_id                 INT NULL',
            'profile_url             TEXT NOT NULL UNIQUE',
            'access_token            TEXT NOT NULL',
            'refresh_token           TEXT NOT NULL',
            'application_token       TEXT NOT NULL',
            'funnel_actions          JSONB NOT NULL DEFAULT \'[]\'',
            'new_dialog_action       JSONB NOT NULL DEFAULT \'[]\'',
            'cache                   JSONB NOT NULL DEFAULT \'[]\'',
            'accounts                JSONB NOT NULL DEFAULT \'[]\''
        ]);
    }
}