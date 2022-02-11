<?php


namespace models;


class Query
{
    public ?string  $Query         = null;
    public ?string  $Type          = null;
    public array    $Parameters    = [];


    public function __construct(string $Query = null, string $Type = null, array $Parameters = [])
    {
        $this->Query        = $Query;
        $this->Type         = $Type;
        $this->Parameters   = $Parameters;
    }


    public function Run() : array
    {
        $this->BuildQuery();

        if(QueryBuffer::IsRun($this->Type, $this))
            return DataBase::Query($this);
        return [];
    }


    public function BuildQuery()
    {
        for($i = count($this->Parameters) - 1; $i >= 0; $i--)
        {
            if(is_array($this->Parameters[$i]))
                $this->Parameters[$i] = json_encode($this->Parameters[$i]);

            if($this->Parameters[$i] instanceof Query)
            {
                $this->Parameters[$i]->BuildQuery();
                for($y = count($this->Parameters[$i]) - 1; $y >= 0; $y--)
                {
                    $this->Parameters[$i]->Query = preg_replace_callback(
                        "/[$]" . ($y + 1) . "\b/",
                        function () use($y, $i) { return "$" . ($y + 1 + $i); },
                        $this->Parameters[$i]->Query
                    );
                }
                $this->Query = preg_replace_callback(
                    "/[$]" . ($i + 1) . "\b/",
                    function () use($i)
                    {
                        return $this->Parameters[$i]->Query;
                    },
                    $this->Query
                );

                $this->Parameters = array_merge($this->Parameters, $this->Parameters[$i]->Parameters);

                $temp = $this->Parameters;
                unset($temp[$i]);
                $this->Parameters = [];
                foreach($temp as $Param)
                    $this->Parameters[] = $Param;
            }
            else if(is_object($this->Parameters[$i]))
                $this->Parameters[$i] = serialize($this->Parameters[$i]);

            if(is_bool($this->Parameters[$i]))
                $this->Parameters[$i] = $this->Parameters[$i] ? 'true' : 'false';

            if($this->Parameters[$i] === '')
                $this->Parameters[$i] = null;
        }
    }


    public function ParametersCountInQuery() : int
    {
        $Count = 0;
        preg_match_all("/[$]\d+\b/", $this->Query, $matches);
        foreach($matches[0] as $obj)
        {
            $obj = (int)ltrim($obj, '$');
            if($obj > $Count)
                $Count = $obj;
        }
        return $Count;
    }
}