<?php


namespace models;


use Exception;


class CRUD
{
    protected function __construct() {}


    protected function Save(string $TableName, string $ColumnIdName, ?int $Id, array $ColumnsArray, string $Val, array $Parameters, bool $CreatedUpdatedAt = false) : int
    {
        if(!isset($Id))
            $Id = (int)((QueryCreator::Create($TableName, $ColumnsArray, $Val, $Parameters, $ColumnIdName, $CreatedUpdatedAt))->Run()[0]->{$ColumnIdName});
        else
        {
            $Val = "";
            for($i = 0; $i < count($ColumnsArray); $i++)
            {
                if($i > 0) $Val .= ", ";
                $Val .= $ColumnsArray[$i]." = $". ($i + 1);
            }
            $Parameters[] = $Id;

            (QueryCreator::Update($TableName, $Val, "$ColumnIdName = $" . ($i + 1), $Parameters, $CreatedUpdatedAt))->Run();
        }

        return $Id;
    }


    protected function Delete(string $TableName, string $ColumnIdName, int $Id)
    {
        if(isset($Id))
            (QueryCreator::Delete($TableName, "$ColumnIdName = $1", [$Id]))->Run();
        else
            throw new Exception("Unable to delete, no  id specified");
    }


    protected function Find(string $TableName, string $ColumnIdName, string $Where = null, array $Parameters = [], int $Offset = null, int $Limit = null, string $Columns = "*") : array
    {
        return (QueryCreator::Find(
            $TableName,
            $Columns,
            $Where,
            $Parameters,
            $Offset,
            $Limit,
            $ColumnIdName
        ))->Run();
    }
}