<?php


namespace migrations;


use models\Migration;
use models\WhatsAppTariff;

class WhatsAppTariffMigration extends Migration
{
    protected ?string $TableName = null;


    public function __construct()
    {
        $this->TableName = WhatsAppTariff::$Table;
    }


    public function Create() : bool
    {
        return parent::Create([
            '"WhatsAppTariffId"     SERIAL PRIMARY KEY',
            '"UserId"               INT NOT NULL',
            '"SaleId"               INT NOT NULL',
            '"OldPrice"             NUMERIC NULL',
            '"Price"                NUMERIC',
            '"EndDate"              INT',
            '"Status"               TEXT NOT NULL',
            '"PayDate"              INT',
            '"Access"               JSONB DEFAULT \'[]\'',
            '"AllPrice"             NUMERIC NULL',
            '"IsCheking"            BOOLEAN NOT NULL DEFAULT FALSE',
        ]);
    }
}