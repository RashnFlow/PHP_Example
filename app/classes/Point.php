<?php


namespace classes;


class Point
{
    private int $X = 0;
    private int $Y = 0;


    public function __construct(int $X = 0, int $Y = 0)
    {
        $this->X = $X;
        $this->Y = $Y;
    }


    public function GetX() : int
    {
        return $this->X;
    }


    public function GetY() : int
    {
        return $this->Y;
    }


    public function SetX(int $X)
    {
        $this->X = $X;
    }


    public function SetY(int $Y)
    {
        $this->Y = $Y;
    }


    public function SetPoint(int $X, int $Y)
    {
        $this->X = $X;
        $this->Y = $Y;
    }


    public function ToArray() : array
    {
        return ["X" => $this->GetX(), "Y" => $this->GetY()];
    }


    static public function CreateClassObj(array $Point)
    {
        return new Point($Point["X"], $Point["Y"]);
    }
}
