<?php


namespace migrations;

use models\Affiliate;
use models\Migration;

class AffiliateMigration extends Migration
{
    protected ?string $TableName = null;


    public function __construct()
    {
        $this->TableName = Affiliate::$Table;
    }


    public function Create() : bool
    {
        return parent::Create([
            '"AffiliateId"              SERIAL PRIMARY KEY',
            '"UserId"                   INT NOT NULL',
            '"Url"                      TEXT NOT NULL',
            '"UrlName"                  TEXT NOT NULL',
            '"Clicks"                   INT NOT NULL',
        ]);
    }
}