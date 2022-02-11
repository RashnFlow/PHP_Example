<?php


namespace migrations;


use models\Migration;
use models\Tariff;

class TariffMigration extends Migration
{
    protected ?string $TableName = null;


    public function __construct()
    {
        $this->TableName = Tariff::$Table;
    }


    public function Create() : bool
    {
        return parent::Create([
            '"TariffId"        SERIAL PRIMARY KEY',
            '"Name"            TEXT NOT NULL',
            '"PriceForMonth"   NUMERIC',
            '"Parameters"      JSONB NOT NULL DEFAULT \'[]\''
        ]);
    }
}