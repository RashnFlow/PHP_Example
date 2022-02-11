<?php


namespace migrations;

use models\Autoresponder;
use models\Migration;


class AutoresponderMigration extends Migration
{
    protected ?string $TableName = null;


    public function __construct()
    {
        $this->TableName = Autoresponder::$Table;
    }


    public function Create() : bool
    {
        return parent::Create([
            '"AutoresponderId"  SERIAL PRIMARY KEY',
            '"Name"             VARCHAR(255) NOT NULL',
            '"Event"            VARCHAR(255) NOT NULL',
            '"Status"           VARCHAR(255) NOT NULL',
            '"FolderIds"        JSONB NOT NULL DEFAULT \'[]\'',
            '"UserId"           INT NOT NULL',
            '"Message"          JSONB NOT NULL DEFAULT \'[]\'',
            '"IsEnable"         BOOLEAN NOT NULL DEFAULT FALSE',
            '"RangeWork"        JSONB NULL',
            '"Sent"             JSONB NOT NULL DEFAULT \'[]\'',
        ]);
    }
}