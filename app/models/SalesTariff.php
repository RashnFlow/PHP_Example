<?php


namespace models;


/**
 * @property int    $SaleId {public get; private set;}
 * @property int    $Month {public get; public set;}
 * @property int    $Sale {public get; public set;}
 */
class SalesTariff extends Model
{
    public static ?string     $Table      = "SalesTariff";
    public static ?string     $PrimaryKey = "SaleId";
    protected static array    $Properties = [
        "public int Month",
        "public ?int Sale"
    ];

    protected function OnCreate() {}

    protected function OnUpdate() {}

    protected function OnDelete() {}

    protected function OnSave() {}

    static public function FindById(int $Id, bool $CheckAccess = true): ?SalesTariff
    {
        $Find = self::FindOne("\"SaleId\" = $1", [$Id], $CheckAccess);
        return $Find;
    }

    static public function FindByMonth(string $Month ,bool $CheckAccess = true) : ?SalesTariff
    {
        $Find = self::FindOne("\"Month\" = $1", [$Month], $CheckAccess);
        return $Find;
    }
}