<?php


namespace models;

use Exception;

/**
 * @property int    $TariffId {public get; private set;}
 * @property string $Name {public get; public set;}
 * @property float  $PriceForMonth {public get; public set;}
 * @property array  $Parameters {public get; public set;}
 */
class Tariff extends Model
{
    public static ?string     $Table      = "Tariff";
    public static ?string     $PrimaryKey = "TariffId";
    protected static array    $Properties = [
        "public string Name",
        "public ?double PriceForMonth",
        "public array Parameters"
    ];

    protected function OnCreate() {}

    protected function OnUpdate() {}

    protected function OnDelete() {}

    protected function OnSave() {}

    static public function FindByName(string $TariffName ,bool $CheckAccess = true) : ?Tariff
    {
        $Find = self::FindOne("\"Name\" = $1", [$TariffName], $CheckAccess);
        return $Find;
    }

    static public function FindById(int $Id, bool $CheckAccess = true): ?Tariff
    {
        $Find = self::FindOne("\"TariffId\" = $1", [$Id], $CheckAccess);
        return $Find;
    }
    
}   