<?php


namespace models;


use models\Query;


class QueryCreator
{
    private function __construct() {}


    static public function Find(string $TableName, string $Columns = "*", string $Where = null, array $Parameters = [], int $Offset = null, int $Limit = null, string $OrderBy = null) : Query
    {
        return new Query(
            "SELECT $Columns FROM \"$TableName\""
            . (empty($Where) ? "" : " WHERE " . $Where)
            . (empty($OrderBy) ? "" : " ORDER BY " . ((strpos($OrderBy, ' ') === false) ? "\"$OrderBy\"" : $OrderBy))
            . (empty($Offset) ? "" : " OFFSET " . $Offset)
            . (empty($Limit) ? "" : " LIMIT " . $Limit),
            __FUNCTION__,
            $Parameters
        );
    }


    static public function FindOne(string $TableName, string $Where, array $Parameters = [], string $Columns = "*") : Query
    {
        return QueryCreator::Find(
            $TableName,
            $Columns,
            $Where,
            $Parameters,
            null,
            1
        );
    }


    static public function AddColumn(string $TableName, string $Column, array $Parameters = []) : Query
    {
        return new Query(
            "ALTER TABLE \"$TableName\" ADD COLUMN $Column;",
            __FUNCTION__,
            $Parameters
        );
    }


    static public function GetTableColumnsAll(string $TableName, array $Parameters = []) : Query
    {
        return new Query(
            "SELECT table_name, column_name, data_type FROM information_schema.columns WHERE table_name = '$TableName'",
            __FUNCTION__,
            $Parameters
        );
    }


    static public function CreateTable(string $TableName, array $ColumnsArray, array $Parameters = [], bool $NotExists = false) : Query
    {
        return new Query(
            "CREATE TABLE"
            . ($NotExists ? " IF NOT EXISTS" : "")
            . " \"$TableName\""
            . " (" . implode(",", $ColumnsArray) . ")",
            __FUNCTION__,
            $Parameters
        );
    }


    static public function DeleteTable(string $TableName) : Query
    {
        return new Query("DROP TABLE \"$TableName\"", __FUNCTION__);
    }


    static public function Update(string $TableName, string $Val, string $Where = null, array $Parameters = [], bool $CreatedUpdatedAt = false) : Query
    {
        return new Query(
            "UPDATE \"$TableName\" SET $Val"
            . ($CreatedUpdatedAt ? ", updated_at = Now()" : "")
            .(empty($Where) ? "" : " WHERE ". $Where),
            __FUNCTION__,
            $Parameters
        );
    }


    static public function Create(string $TableName, array $ColumnsArray, string $Val, array $Parameters = [], string $ColumnId = "id", bool $CreatedUpdatedAt = false) : Query
    {
        return new Query(
            "INSERT INTO \"$TableName\" ("
            . '"' . implode("\",\"", $ColumnsArray) . '"'
            . ($CreatedUpdatedAt ? ", created_at, updated_at" : "")
            . ") VALUES ($Val"
            . ($CreatedUpdatedAt ? ", Now(), Now()" : "")
            . ") "
            . "RETURNING \"" . $ColumnId . '"',
            __FUNCTION__,
            $Parameters
        );
    }


    static public function CreateOrUpdate(string $TableName, array $ColumnsArray, string $Val, string $OnConflict, array $Parameters = []) : Query
    {
        $Update = "";
        for($i = 0; $i < count($ColumnsArray); $i++)
        {
            if($i > 0) $Update .= ", ";

            $Update .= $ColumnsArray[$i] . " = excluded." . $ColumnsArray[$i];
        }

        return new Query(
            "INSERT INTO \"$TableName\" ("
            . implode(",", $ColumnsArray)
            . ") VALUES ($Val) ON CONFLICT ($OnConflict) DO UPDATE SET $Update",
            __FUNCTION__,
            $Parameters
        );
    }


    static public function Delete(string $TableName, string $Where, array $Parameters = []) : Query
    {
        return new Query(
            "DELETE FROM \"$TableName\" WHERE $Where",
            __FUNCTION__,
            $Parameters
        );
    }


    static public function Count(string $TableName, string $Where = null, array $Parameters = []) : Query
    {
        return QueryCreator::Find(
            $TableName,
            "COUNT(*)",
            $Where,
            $Parameters
        );
    }
}