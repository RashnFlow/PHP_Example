<?php


namespace migrations;


use models\Migration;


class UserMigration extends Migration
{
    protected ?string $TableName = USER_TABLE;


    public function Create() : bool
    {
        return parent::Create([
            'user_id            SERIAL PRIMARY KEY',
            'login              VARCHAR(50)  NOT NULL UNIQUE',
            'name               VARCHAR(255)',
            'password           VARCHAR(255) NULL',
            'email              VARCHAR(255) NULL',
            'user_type          VARCHAR(30) NOT NULL',
            "avatar             BYTEA NULL",
            "is_active          BOOLEAN NOT NULL DEFAULT FALSE",
            'rules              JSONB NOT NULL DEFAULT \'[]\'',
            'phone              VARCHAR(50) NULL',
            'permissions        JSONB NOT NULL DEFAULT \'[]\'',
            "created_at         TIMESTAMP NOT NULL",
            "updated_at         TIMESTAMP NOT NULL"
        ]);
    }
}