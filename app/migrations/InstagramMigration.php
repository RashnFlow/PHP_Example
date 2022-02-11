<?php


namespace migrations;


use models\Migration;


class InstagramMigration extends Migration
{
    protected ?string $TableName = INSTAGRAM_TABLE;


    public function Create() : bool
    {
        return parent::Create([
            'instagram_id               SERIAL PRIMARY KEY',
            'user_id                    INT NOT NULL',
            'login                      VARCHAR(255) NOT NULL UNIQUE',
            'password                   TEXT NOT NULL',
            'status                     VARCHAR(255) NULL',
            'status_id                  INT NULL',
            'is_active                  BOOLEAN NOT NULL DEFAULT FALSE',
            'is_banned                  BOOLEAN NOT NULL DEFAULT FALSE',
            'session                    TEXT NULL',
            'proxy_id                   INT NULL',
            'default_folder             INT NULL',
            'comment_tracking           BOOLEAN NOT NULL DEFAULT FALSE',
            'subscriber_tracking        BOOLEAN NOT NULL DEFAULT FALSE'
        ]);
    }
}