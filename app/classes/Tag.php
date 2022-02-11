<?php


namespace classes;


class Tag
{    
    static public function ReplaceTagAll(array $ConfigTag, array $Arguments, string $Text) : string
    {
        foreach($ConfigTag as $key=>$obj)
        {
            if(!empty($Arguments[$obj]))
                $Text = str_replace("[@$key]", $Arguments[$obj], $Text);
        }
        return $Text;
    }

    
    static public function ReplaceTag(array $Tag, string $Text) : string
    {
        foreach($Tag as $key=>$obj)
            $Text = str_replace("[@$key]", $obj, $Text);
        return $Text;
    }
}