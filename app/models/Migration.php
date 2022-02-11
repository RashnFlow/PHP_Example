<?php


namespace models;


use Exception;


abstract class Migration
{
    protected ?string $TableName = null;


    public function Create(array $ColumnsArray) : bool
    {
        try
        {
            (QueryCreator::CreateTable($this->TableName, $ColumnsArray, [], false))->Run();
            return true;
        }
        catch(Exception $error) {}

        $AllColumns = array_column(QueryCreator::GetTableColumnsAll($this->TableName)->Run(), "column_name");
        foreach($ColumnsArray as $Column)
        {
            preg_match("/\w+\b/", $Column, $ColumnName);
            if(array_search(trim($ColumnName[0]), $AllColumns) === false)
                QueryCreator::AddColumn($this->TableName, $Column)->Run();
        }

        return true;
    }


    public function Delete() : bool
    {
        (QueryCreator::DeleteTable($this->TableName))->Run();
        return true;
    }
}
