<?php


namespace migrations;


use models\Facebook;
use models\Migration;


class FacebookMigration extends Migration
{
    protected ?string $TableName;


    public function __construct()
    {
        $this->TableName = Facebook::$Table;
    }


    public function Create() : bool
    {
        return parent::Create([
            '"FacebookId"                   SERIAL PRIMARY KEY',
            '"UserId"                       INT NOT NULL',
            '"FullName"                     VARCHAR(255) NOT NULL',
            '"PictureUrl"                   TEXT NULL',
            '"AccessToken"                  TEXT NULL',
            '"RefreshToken"                 TEXT NULL',
            '"FacebookUserId"               BIGINT NOT NULL',
            '"LastRefreshTokenUpdateTime"   INT NULL',
            '"StatusId"                     INT NOT NULL',
            '"Pages"                        JSONB NOT NULL DEFAULT \'[]\''
        ]);
    }
}