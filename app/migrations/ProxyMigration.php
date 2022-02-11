<?php


namespace migrations;


use models\Migration;
use models\Proxy;


class ProxyMigration extends Migration
{
    protected ?string $TableName = null;


    public function __construct()
    {
        $this->TableName = Proxy::$Table;
    }


    public function Create() : bool
    {
        return parent::Create([
            '"ProxyId"  SERIAL PRIMARY KEY',
            '"Host"     VARCHAR(255) NOT NULL UNIQUE',
            '"Port"     INT NOT NULL',
            '"Password" VARCHAR(255) NULL',
            '"Login"    VARCHAR(255) NULL',
            '"IsActive" BOOLEAN NOT NULL DEFAULT TRUE',
            '"IsBusy"   BOOLEAN NOT NULL DEFAULT FALSE',
            '"Protocol" VARCHAR(255) NULL'
        ]);
    }
}