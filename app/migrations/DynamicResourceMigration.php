<?php


namespace migrations;


use models\Migration;


class DynamicResourceMigration extends Migration
{
    protected ?string $TableName = DYNAMIC_RESOURCE_TABLE;


    public function Create() : bool
    {
        return parent::Create([
            'dynamic_resource_id       SERIAL PRIMARY KEY',
            'dynamic_resource_uid      VARCHAR(50) NOT NULL UNIQUE',
            'user_id                   INT NOT NULL',
            'type                      VARCHAR(255) NULL',
            'extension                 VARCHAR(255) NULL',
            'name                      TEXT NULL'
        ]);
    }
}