<?php


namespace migrations;


use models\Migration;


class ExternalPhoneMigration extends Migration
{
    protected ?string $TableName = EXTERNAL_PHONE;


    public function Create() : bool
    {
        return parent::Create([
            'external_phone_id  SERIAL PRIMARY KEY',
            'phone              VARCHAR(50) NOT NULL UNIQUE',
            'ids_use            JSONB NULL'
        ]);
    }
}