<?php


namespace migrations;


use models\Migration;


class DynamicMassSendingMigration extends Migration
{
    protected ?string $TableName = DYNAMIC_MASS_SENDING;


    public function Create() : bool
    {
        return parent::Create([
            'dynamic_mass_sending_id    SERIAL PRIMARY KEY',
            'user_id                    INT NOT NULL',
            'name                       VARCHAR(50) NOT NULL',
            'send_file_uid              VARCHAR(50) NULL',
            'message                    JSONB NOT NULL',
            'time_start                 INT NOT NULL',
            'status                     VARCHAR(50) NOT NULL',
            'count_sent                 INT NOT NULL',
            'count_sent_to_day          JSONB NOT NULL',
            'is_enable                  BOOLEAN NOT NULL DEFAULT FALSE',
            'sent                       JSONB NOT NULL',
            'send_day                   INT NULL',
            'range_work                 JSONB NULL',
            'on_message                 JSONB NOT NULL',
            "created_at                 TIMESTAMP NOT NULL",
            "updated_at                 TIMESTAMP NOT NULL",
            'whatsapp_ids_reserve       JSONB NULL',
            'avatar                     BYTEA NULL',
            'company_name               VARCHAR(255) NULL',
            'activity_id                INT NULL',
            'folder_id                  INT NOT NULL',
            'dialog_folder_id           INT NOT NULL',
            'count_send                 INT NULL'
        ]);
    }
}