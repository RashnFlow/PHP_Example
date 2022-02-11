<?php


namespace classes;


class Tools
{
    static public function GenerateString(int $Length = 20) : string
    {
        $Chars = 'qazxswedcvfrtgbnhyujmkiolp1234567890QAZXSWEDCVFRTGBNHYUJMKIOLP'; 
        $Size = strlen($Chars) - 1; 
        $String = ''; 
        while($Length--) {
            $String .= $Chars[random_int(0, $Size)]; 
        }
        return $String;
    }
    

    static public function GenerateStringBySeed(int $Length = 20, int $Seed) : string
    {
        srand($Seed);
        $Chars = 'qazxswedcvfrtgbnhyujmkiolp1234567890QAZXSWEDCVFRTGBNHYUJMKIOLP'; 
        $Size = strlen($Chars) - 1; 
        $String = ''; 
        while($Length--) {
            $String .= $Chars[rand(0, $Size)]; 
        }
        srand(time());
        return $String;
    }


    static public function MbLcfirst($Str)
    {
        return mb_strtolower(mb_substr($Str, 0, 1)) . mb_substr($Str, 1);
    }


    static public function MbUcfirst($Str)
    {
        return mb_strtoupper(mb_substr($Str, 0, 1)) . mb_substr($Str, 1);
    }


    static public function ParseKeysInArray(array $Keys, array $Array) : array
    {
        foreach($Array as $key => &$obj)
        {
            if(!in_array($key, $Keys))
                unset($obj);
        }
        return $Array;
    }


    static public function ConvertStringToSeed(string $Str) : int
    {
        $Seed = $Length = strlen($Str);
        for ($i = 0; $i < $Length; $i++)
            if(!empty($Str[$i]))
                $Seed += mb_ord($Str[$i]) . mb_ord($Str[($Length - 1 - $i)]) * ($i % 2 == 0 ? $i : 1);

        return $Seed;
    }
}