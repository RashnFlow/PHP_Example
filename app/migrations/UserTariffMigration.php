<?php


namespace migrations;


use models\Migration;
use models\UserTariff;

class UserTariffMigration extends Migration
{
    protected ?string $TableName = null;


    public function __construct()
    {
        $this->TableName = UserTariff::$Table;
    }


    public function Create() : bool
    {
        return parent::Create([
            '"UserTariffId"         SERIAL PRIMARY KEY',
            '"UserId"               INT NOT NULL',
            '"WhatsAppTariffId"     INT NOT NULL',
            '"InstagramTariffId"    INT NOT NULL',
            '"SaleId"               INT NOT NULL',
            '"Price"                NUMERIC',
        ]);
    }
}