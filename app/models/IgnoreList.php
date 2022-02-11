<?php


namespace models;


/**
 * @property int     $UserTariffId {public get; private set;}
 * @property int     $UserId {public get; private set;}
 * @property string  $Name {public get; public set;}
 * @property int     $SaleId {public get; private set;}
 * @property double  $OldPrice {public get; public set;}
 * @property double  $Price {public get; public set;}
 * @property array   $Parameters {public get; public set;}
 * @property string  $Status {public get; public set;}
 * @property int     $PayDate {public get; public set;}
 * @property array   $Access {public get; public set;}
 * @property string  $HashSum {public get; public set;}
 */
class IgnoreList extends Model
{
    public static ?string     $Table      = "IgnoreList";
    public static ?string     $PrimaryKey = "IgnoreListId";
    protected static array    $Properties = [
        "public int UserId",
        "public array IgnoreParameters"
    ];

    protected function OnCreate() {}

    protected function OnUpdate() {}

    protected function OnDelete() {}

    protected function OnSave() {}

    static public function FindById(int $Id, bool $CheckAccess = true): ?IgnoreList
    {
        $Find = self::FindOne('"IgnoreListId" = $1', [$Id], $CheckAccess);
        return $Find;
    }

    static public function FindByUserId(int $UserId, bool $CheckAccess = true): ?IgnoreList
    {
        $Find = self::FindOne('"UserId" = $1', [$UserId], $CheckAccess);
        return $Find;
    }
}