<?php


namespace models;


/**
 * @property int     $UserTariffId {public get; private set;}
 * @property int     $UserId {public get; private set;}
 * @property int     $WhatsAppTariffId {public get; private set;}
 * @property int     $InstagramTariffId {public get; private set;}
 * @property string  $Name {public get; public set;}
 * @property int     $SaleId {public get; private set;}
 * @property double  $OldPrice {public get; public set;}
 * @property double  $Price {public get; public set;}
 * @property string  $Status {public get; public set;}
 * @property int     $PayDate {public get; public set;}
 * @property array   $Access {public get; public set;}
 * @property double  $AllPrice {public get; public set;}
 */
class UserTariff extends Model
{
    public static ?string     $Table      = "UserTariff";
    public static ?string     $PrimaryKey = "UserTariffId";
    protected static array    $Properties = [
        "public int WhatsAppTariffId",
        "public int InstagramTariffId",
        "public int UserId",
        "public int SaleId",
        "public double Price",
    ];

    protected function OnCreate() {}

    protected function OnUpdate() {}

    protected function OnDelete() {}

    protected function OnSave() {}

    static public function FindById(int $Id, bool $CheckAccess = true): ?UserTariff
    {
        $Find = self::FindOne('"UserTariffId" = $1', [$Id], $CheckAccess);
        return $Find;
    }

    static public function FindAllByUserId(int $UserId, bool $CheckAccess = true): ?array
    {
        $Find = self::FindAll('"UserId" = $1', [$UserId]);
        return $Find;
    }
}