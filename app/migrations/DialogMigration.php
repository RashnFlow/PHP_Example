<?php


namespace migrations;


use models\Migration;


class DialogMigration extends Migration
{
    protected ?string $TableName = DIALOG_TABLE;


    public function Create() : bool
    {
        return parent::Create([
            "dialog_id          SERIAL PRIMARY KEY",
            "folder_id          INT NULL",
            "name               VARCHAR(50) NULL",
            "type               VARCHAR(255) NOT NULL",
            "avatar             BYTEA NULL",
            "is_active          BOOLEAN NOT NULL DEFAULT TRUE",
            "is_online          BOOLEAN NOT NULL DEFAULT FALSE",
            "properties         JSONB NOT NULL",
            "tags               JSONB NOT NULL",
            "whitelist          JSONB NOT NULL",
            "created_at         TIMESTAMP NOT NULL",
            "updated_at         TIMESTAMP NOT NULL"
        ]);
    }
}