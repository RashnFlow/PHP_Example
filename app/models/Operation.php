<?php


namespace models;


/**
 * @property int $Sum {public get; public set;}
 * @property int $CreatedAt {public get; public set;}
 * @property int $UserId {public get; public set;}
 * @property int $OperationId {public get; private set;}
 * @property int $StatusId {public get; public set;}
 * @property mixed $Date {public get; public set;}
 * @property string $Name {public get; public set;}
 * @property string $Type {public get; public set;}
 */
class Operation extends Model
{
    public static ?string     $Table      = "Operations";
    public static ?string     $PrimaryKey = "OperationId";
    protected static array    $Properties = [
        "public int Sum",
        "public int UserId",
        "public bool IsPaid",
        "public mixed Data",
        "public int CreatedAt",
        "public int PayDate",
    ];

    protected function OnCreate()
    {
        if($this->IsNew)
            $this->CreatedAt = time();
    }

    protected function OnUpdate() {}

    protected function OnDelete() {}

    protected function OnSave() {}

    protected function __setData($Value)
    {
        return serialize($Value);
    }

    protected function __getData($Value)
    {
        return unserialize($Value);
    }

    static public function FindAllByUserId(int $UserId, bool $CheckAccess = true): ?array
    {
        $Find = self::FindAll('"UserId" = $1', [$UserId]);
        return $Find;
    }
}