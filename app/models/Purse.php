<?php


namespace models;


/**
 * @property double $Balance {public get; public set;}
 * @property int    $UserId {public get; public set;}
 * @property int    $ReplenishmentAt {public get; public set;}
 * @property int    $PurseId {public get; private set;}
 */
class Purse extends Model
{
    public static ?string     $Table      = "Purses";
    public static ?string     $PrimaryKey = "PurseId";
    protected static array    $Properties = [
        "public double Balance",
        "public int UserId",
        "public int ReplenishmentAt",
    ];

    protected function OnCreate() {}

    protected function OnUpdate() {}

    protected function OnDelete() {}

    protected function OnSave() {}

    static public function FindByUserId(int $UserId, bool $CheckAccess = true): ?Purse
    {
        $Find = self::FindOne('"UserId" = $1', [$UserId], $CheckAccess);
        return $Find;
    }
}