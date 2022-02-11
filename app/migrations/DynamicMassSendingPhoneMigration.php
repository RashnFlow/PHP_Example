<?php


namespace migrations;


use models\Migration;


class DynamicMassSendingPhoneMigration extends Migration
{
    protected ?string $TableName = DYNAMIC_MASS_SENDING_PHONE;


    public function Create() : bool
    {
        return parent::Create([
            'dynamic_mass_sending_phone_id  SERIAL PRIMARY KEY',
            'dynamic_mass_sending_id        INT NOT NULL',
            'whatsapp_id                    INT NOT NULL',
            'phone                          VARCHAR(50) NULL',
            'name                           VARCHAR(100) NULL',
            'status                         VARCHAR(255) NULL',
            'is_read                        BOOLEAN NOT NULL DEFAULT FALSE',
            'is_response                    BOOLEAN NOT NULL DEFAULT FALSE',
            'is_sent                        BOOLEAN NOT NULL DEFAULT FALSE',
            'is_done                        BOOLEAN NOT NULL DEFAULT FALSE',
            'is_busy                        BOOLEAN NOT NULL DEFAULT FALSE'
        ]);
    }
}