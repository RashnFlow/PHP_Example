<?php


namespace models;


/**
 * @property int     $WhatsAppTariffId {public get; private set;}
 * @property int     $SaleId {public get; private set;}
 * @property double  $OldPrice {public get; public set;}
 * @property double  $Price {public get; public set;}
 * @property int     $EndDate {public get; public set;}
 * @property string  $Status {public get; public set;}
 * @property int     $PayDate {public get; public set;}
 * @property array   $Access {public get; public set;}
 * @property double  $AllPrice {public get; public set;}
 */
class WhatsAppTariff extends Model
{
    public static ?string     $Table      = "WhatsAppTariff";
    public static ?string     $PrimaryKey = "WhatsAppTariffId";
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

    static public function FindById(int $Id, bool $CheckAccess = true): ?WhatsAppTariff
    {
        $Find = self::FindOne('"WhatsAppTariffId" = $1', [$Id], $CheckAccess);
        return $Find;
    }

    static public function FindAllByUserId(int $UserId, bool $CheckAccess = true): ?array
    {
        $Find = self::FindAll('"UserId" = $1', [$UserId]);
        return $Find;
    }
}