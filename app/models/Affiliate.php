<?php


namespace models;

use classes\Tools;

/**
 * @property int     $AffiliateId {public get; private set;}
 * @property int     $UserId {public get; private set;}
 * @property string  $Url {public get; public set;}
 * @property string  $UrlName {public get; public set;}
 * @property int     $Clicks {public get; public set;}
 */
class Affiliate extends Model
{
    public static ?string     $Table      = "Affiliate";
    public static ?string     $PrimaryKey = "AffiliateId";
    protected static array    $Properties = [
        "public int UserId",
        "public string UrlName",
        "public string Url",
        "public int Clicks"
    ];

    protected function OnCreate() {}

    protected function OnUpdate() {}

    protected function OnDelete() {}

    protected function OnSave() {}

    static public function FindById(int $Id, bool $CheckAccess = true): ?Affiliate
    {
        $Find = self::FindOne('"AffiliateId" = $1', [$Id], $CheckAccess);
        return $Find;
    }


    static public function FindByUrl(string $Url, bool $CheckAccess = true): ?Affiliate
    {
        $Find = self::FindOne('"Url" = $1', [$Url], $CheckAccess);
        return $Find;
    }

    static public function FindByUrlNameAndUserId(string $UrlName, int $UserId, bool $CheckAccess = true): ?Affiliate
    {
        $Find = self::FindOne('"UrlName" = $1 and "UserId" = $2', [$UrlName, $UserId], $CheckAccess);
        return $Find;
    }

    static public function FindAllByUserId(int $UserId, bool $CheckAccess = true): ?array
    {
        $Find = self::FindAll('"UserId" = $1', [$UserId]);
        return $Find;
    }
}