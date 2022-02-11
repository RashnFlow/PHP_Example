<?php


namespace migrations;


use models\Migration;


class ApiTokenMigration extends Migration
{
    protected ?string $TableName = API_TOKEN_TABLE;


    public function Create() : bool
    {
        return parent::Create([
            'api_token_id   SERIAL PRIMARY KEY',
            'user_id        INT NOT NULL',
            'token          VARCHAR(255) NOT NULL',
            "created_at     TIMESTAMP NOT NULL",
            "updated_at     TIMESTAMP NOT NULL",
            "example        INT NULL"
        ]);
    }
}