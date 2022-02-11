<?php


namespace migrations;


use models\Migration;


class EmailMigration extends Migration
{
    protected ?string $TableName = EMAIL_TABLE;


    public function Create() : bool
    {
        return parent::Create([
            'email_id                  SERIAL PRIMARY KEY',
            'clicks_count              INT NOT NULL',
            'opens_count               INT NOT NULL',
            'sents_count               INT NOT NULL',
            'spams_count               INT NOT NULL',
            'failed_sents_count        INT NOT NULL',
            'is_send                   BOOLEAN NOT NULL DEFAULT TRUE',
            'email                     VARCHAR(255) NOT NULL UNIQUE',
            'email_uid                 VARCHAR(50) NOT NULL UNIQUE'
        ]);
    }
}