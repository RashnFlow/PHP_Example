<?php


namespace migrations;


use models\Migration;
use models\SalesTariff;

class SalesTariffMigration extends Migration
{
    protected ?string $TableName = null;


    public function __construct()
    {
        $this->TableName = SalesTariff::$Table;
    }


    public function Create() : bool
    {
        return parent::Create([
            '"SaleId"   SERIAL PRIMARY KEY',
            '"Month"    INT NOT NULL',
            '"Sale"     INT'
        ]);
    }
}