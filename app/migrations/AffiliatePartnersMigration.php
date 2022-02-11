<?php


namespace migrations;

use models\Affiliate;
use models\AffiliatePartners;
use models\Migration;

class AffiliatePartnersMigration extends Migration
{
    protected ?string $TableName = null;


    public function __construct()
    {
        $this->TableName = AffiliatePartners::$Table;
    }


    public function Create() : bool
    {
        return parent::Create([
            '"AffiliatePartnersId"      SERIAL PRIMARY KEY',
            '"UserId"                   INT NOT NULL',
            '"PartnerName"              TEXT NOT NULL',
            '"PartnerSale"              INT NOT NULL',
            '"RegistrationUsersId"      JSONB DEFAULT \'[]\'',
        ]);
    }
}