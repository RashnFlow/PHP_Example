<?php


namespace models;


/**
 * @property int     $InstagramTariffId {public get; private set;}
 * @property int     $SaleId {public get; private set;}
 * @property double  $OldPrice {public get; public set;}
 * @property double  $Price {public get; public set;}
 * @property int     $EndDate {public get; public set;}
 * @property string  $Status {public get; public set;}
 * @property int     $PayDate {public get; public set;}
 * @property array   $Access {public get; public set;}
 * @property double  $AllPrice {public get; public set;}
 */
class InstagramTariff extends Model
{
    public static ?string     $Table      = "InstagramTariff";
    public static ?string     $PrimaryKey = "InstagramTariffId";
    protected static array    $Properties = [
        "public int UserId",
        "public int SaleId",
        "public double OldPrice",
        "public double Price",
        "public ?int EndDate",
        "public string Status",
        "public int PayDate",
        "public array Access",
        "public double AllPrice",
        "public boolean IsCheking"
    ];

    protected function OnCreate() {}

    protected function OnUpdate() {}

    protected function OnDelete() {}

    protected function OnSave() {}

    static public function FindById(int $Id, bool $CheckAccess = true): ?InstagramTariff
    {
        $Find = self::FindOne('"InstagramTariffId" = $1', [$Id], $CheckAccess);
        return $Find;
    }

    static public function FindAllByUserId(int $UserId, bool $CheckAccess = true): ?array
    {
        $Find = self::FindAll('"UserId" = $1', [$UserId]);
        return $Find;
    }
}