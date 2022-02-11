<?php


namespace migrations;


use models\Migration;


class FolderMigration extends Migration
{
    protected ?string $TableName = FOLDER_TABLE;


    public function Create() : bool
    {
        return parent::Create([
            'folder_id          SERIAL PRIMARY KEY',
            'user_id            INT NOT NULL',
            'parent_folder_id   INT NULL',
            'name               VARCHAR(50) NOT NULL',
            'tags               JSONB NOT NULL',
            'is_default         BOOLEAN NOT NULL DEFAULT FALSE',
            'editing_possible   BOOLEAN NOT NULL DEFAULT TRUE',
            'is_isolated        BOOLEAN NOT NULL DEFAULT FALSE',
            'properties         JSONB NOT NULL'
        ]);
    }
}