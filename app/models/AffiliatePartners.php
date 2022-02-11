<?php


namespace models;

use classes\Tools;

/**
 * @property int     $AffiliatePartnersId {public get; private set;}
 * @property int     $UserId {public get; private set;}
 * @property string  $PartnerName {public get; public set;}
 * @property int     $PartnerSale {public get; public set;}
 * @property array   $RegistrationUsersId {public get; private set;}
 */
class AffiliatePartners extends Model
{
    public static ?string     $Table      = "AffiliatePartners";
    public static ?string     $PrimaryKey = "AffiliatePartnersId";
    protected static array    $Properties = [
        "public int UserId",
        "public string PartnerName",
        "public int PartnerSale",
        "public array RegistrationUsersId"
    ];

    protected function OnCreate() {}

    protected function OnUpdate() {}

    protected function OnDelete() {}

    protected function OnSave() {}

    static public function FindById(int $Id, bool $CheckAccess = true): ?AffiliatePartners
    {
        $Find = self::FindOne('"AffiliatePartnersId" = $1', [$Id], $CheckAccess);
        return $Find;
    }


    static public function FindByUserId(string $UserId, bool $CheckAccess = true): ?AffiliatePartners
    {
        $Find = self::FindOne('"UserId" = $1', [$UserId], $CheckAccess);
        return $Find;
    }
}