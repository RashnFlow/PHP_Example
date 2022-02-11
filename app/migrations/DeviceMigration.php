<?php


namespace migrations;


use models\Migration;


class DeviceMigration extends Migration
{
    protected ?string $TableName = DEVICE_TABLE;


    public function Create() : bool
    {
        return parent::Create([
            'device_id      SERIAL PRIMARY KEY',
            'status         VARCHAR(255) NULL',
            'is_active      BOOLEAN NOT NULL DEFAULT FALSE',
            'device_uid     VARCHAR(255) NULL UNIQUE'
        ]);
    }
}