<?php


namespace migrations;


use models\Migration;


class WhatsappMigration extends Migration
{
    protected ?string $TableName = WHATSAPP_TABLE;


    public function Create() : bool
    {
        return parent::Create([
            'whatsapp_id                SERIAL PRIMARY KEY',
            'user_id                    INT NOT NULL',
            'phone                      VARCHAR(50) NULL UNIQUE',
            'venom_session              JSONB NULL',
            'status                     VARCHAR(255) NULL',
            'name                       VARCHAR(255) NULL',
            'status_id                  INT NULL',
            'location_type              VARCHAR(255) NOT NULL',
            'is_active                  BOOLEAN NOT NULL DEFAULT FALSE',
            'device_id                  INT NULL',
            'w_app_id                   INT NULL',
            'is_dynamic                 BOOLEAN NOT NULL DEFAULT FALSE',
            'is_banned                  BOOLEAN NOT NULL DEFAULT FALSE',
            'send_count_to_day          JSONB NULL',
            'send_count_day             INT NULL DEFAULT 20',
            'avatar                     BYTEA NULL',
            'company_name               VARCHAR(255) NULL',
            'activity_id                INT NULL',
            'default_folder             INT NULL',
            'proxy_id                   INT NULL',
            'is_business                BOOLEAN NOT NULL DEFAULT FALSE',
            'updated_at                 TIMESTAMP DEFAULT \'NOW()\'',
            'created_at                 TIMESTAMP DEFAULT \'NOW()\''
        ]);
    }
}