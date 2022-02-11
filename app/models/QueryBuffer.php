<?php


namespace models;


class QueryBuffer
{
    static private array    $Buffers        = [];
    static private bool     $IsRunning      = false;
    static private array    $QueryCache     = [];

    static public function Start(array $Types = ["All"])
    {
        self::$IsRunning = true;
        self::$Buffers[] = ["Query" => new Query(null, "QueryBuffer"), "Types" => $Types];
    }


    static public function Stop() : ?Query
    {
        if(count(self::$Buffers) == 1)
            self::$IsRunning = false;
    
        $Buffer = array_pop(self::$Buffers);
        if(!empty($Buffer["Query"]->Query))
            return $Buffer["Query"];
        
        return null;
    }


    static public function IsRun(string $Type, Query $Query) : bool
    {
        if(!self::$IsRunning)
            return true;

        $Buffer = end(self::$Buffers);
        if(in_array("All", $Buffer["Types"]) || in_array($Type, $Buffer["Types"]))
        {
            $Index = array_search($Query->Query, array_column(self::$QueryCache, "Query"));
            if($Index === false)
            {
                $PreparedQueryName = "prepared_query_" . count(self::$QueryCache);
                DataBase::Prepare($PreparedQueryName, $Query->Query);
                self::$QueryCache[] = ["Name" => $PreparedQueryName, "Query" => $Query->Query];
            }
            else
                $PreparedQueryName = self::$QueryCache[$Index]["Name"];

            $Buffer["Query"]->Query .= "EXECUTE $PreparedQueryName(" . implode(', ', DataBase::EscapeArray(array_slice($Query->Parameters, 0, $Query->ParametersCountInQuery()))) . ");\n";
            return false;
        }
        return true;
    }
}