<?php


namespace models;

use Exception;

/**
 * @property int    $TariffId {public get; private set;}
 * @property string $Name {public get; public set;}
 * @property float  $PriceForMonth {public get; public set;}
 * @property array  $Parameters {public get; public set;}
 */
class YclientsRecords extends Model
{
    public static ?string     $Table      = "YclientsRecords";
    public static ?string     $PrimaryKey = "YclientsRecordId";
    protected static array    $Properties = [
        "public int UserId",
        "public int RecordId",
        "public string RecordDate",
        "public int MasterId",
        "public int RecordStatus",
        "public array RecordCategory",
        "public array RecordServices"
    ];

    protected function OnCreate() {}

    protected function OnUpdate() {}

    protected function OnDelete() {}

    protected function OnSave() {}

    static public function FindByRecordId(int $RecordId ,bool $CheckAccess = true) : ?YclientsRecords
    {
        $Find = self::FindOne("\"RecordId\" = $1", [$RecordId], $CheckAccess);
        return $Find;
    }
    
}   