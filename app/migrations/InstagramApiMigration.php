<?php


namespace migrations;

use models\InstagramApi;
use models\Migration;


class InstagramApiMigration extends Migration
{
    protected ?string $TableName;


    public function __construct()
    {
        $this->TableName = InstagramApi::$Table;
    }


    public function Create() : bool
    {
        return parent::Create([
            '"InstagramApiId"           SERIAL PRIMARY KEY',
            '"FacebookId"               INT NOT NULL',
            '"InstagramUserId"          BIGINT NOT NULL',
            '"PageId"                   BIGINT NOT NULL',
            '"UserName"                 VARCHAR(255) NOT NULL',
            '"PictureUrl"               TEXT NULL',
            '"StatusId"                 INT NOT NULL',
            '"DefaultFolder"            INT NULL',
            '"CommentTracking"          BOOLEAN NOT NULL DEFAULT FALSE'
        ]);
    }
}