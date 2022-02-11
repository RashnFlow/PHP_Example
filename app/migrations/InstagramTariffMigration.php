<?php


namespace migrations;

use models\InstagramTariff;
use models\Migration;

class InstagramTariffMigration extends Migration
{
    protected ?string $TableName = null;


    public function __construct()
    {
        $this->TableName = InstagramTariff::$Table;
    }


    public function Create() : bool
    {
        return parent::Create([
            '"InstagramTariffId"    SERIAL PRIMARY KEY',
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