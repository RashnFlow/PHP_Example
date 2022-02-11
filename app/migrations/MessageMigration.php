<?php


namespace migrations;


use models\Migration;


class MessageMigration extends Migration
{
    protected ?string $TableName = MESSAGE_TABLE;


    public function Create() : bool
    {
        return parent::Create([
            'message_id     SERIAL PRIMARY KEY',
            'dialog_id      INT NULL',
            'status_id      INT NULL',
            'message_uid    VARCHAR(255) NULL',
            'text           TEXT NULL',
            'document       VARCHAR(255) NULL',
            'video          VARCHAR(255) NULL',
            'type           VARCHAR(255) NULL',
            'img            VARCHAR(255) NULL',
            'caption        TEXT NULL',
            'time           INT NULL',
            'is_me          BOOLEAN NOT NULL DEFAULT FALSE'
        ]);
    }
}