<?php


namespace migrations;

use models\MassSending;
use models\Migration;


class MassSendingMigration extends Migration
{
    protected ?string $TableName = null;


    public function __construct()
    {
        $this->TableName = MassSending::$Table;
    }


    public function Create() : bool
    {
        return parent::Create([
            '"MassSendingId"      SERIAL PRIMARY KEY',
            '"UserId"             INT NOT NULL',
            '"Name"               VARCHAR(50) NOT NULL',
            '"Send"               JSONB NOT NULL DEFAULT \'[]\'',
            '"Message"            JSONB NOT NULL DEFAULT \'[]\'',
            '"TimeStart"          INT NOT NULL',
            '"Random"             BOOLEAN NOT NULL DEFAULT FALSE',
            '"Status"             VARCHAR(50) NULL',
            '"SentCount"          INT NOT NULL',
            '"IsEnable"           BOOLEAN NOT NULL DEFAULT FALSE',
            '"Sent"               JSONB NOT NULL  DEFAULT \'[]\'',
            '"SendDay"            INT NULL',
            '"RangeWork"          JSONB NOT NULL DEFAULT \'[]\'',
            '"Interval"           INT NULL',
            '"OnMessage"          JSONB NULL',
            '"CreatedAt"          INT NOT NULL',
            '"UpdatedAt"          INT NOT NULL'
        ]);
    }
}