<?php


namespace models;

use Exception;

/**
 * @property int $FacebookId {public get; private set;}
 * @property int $UserId {public get; public set;}
 * @property int $FacebookUserId {public get; public set;}
 * @property array $Pages {public get; public set;}
 * @property int $StatusId {public get; public set;}
 * @property string $FullName {public get; public set;}
 * @property string $LastRefreshTokenUpdateTime {public get; private set;}
 * @property string $PictureUrl {public get; public set;}
 * @property string $AccessToken {public get; public set;}
 * @property string $RefreshToken {public get; public set;}
 * @property bool $IsActive {write: false; public get; public set;}
 * @property string $Status {write: false; public get; private set;}
 */
class Facebook extends Model
{

    public const STATUSES = [
        "Не активирован",
        "Активен"
    ];


    public static ?string     $Table      = 'Facebooks';
    public static ?string     $PrimaryKey = 'FacebookId';
    protected static array    $Properties = [
        'public int UserId',
        'public string FullName',
        'public string PictureUrl',
        'public string AccessToken',
        'public int StatusId',
        'public ?int LastRefreshTokenUpdateTime {public get; private set;}',
        'public array Pages',
        'public string RefreshToken',
        'public int FacebookUserId',
        'public bool IsActive {write false}',
        'public string Status {write false; public get; private set;}',
    ];

    protected function OnCreate() {}

    protected function OnUpdate() {}

    protected function OnDelete() {}

    protected function OnSave() {}


    protected function __setRefreshToken($Value)
    {
        $this->LastRefreshTokenUpdateTime = time();
        return $Value;
    }


    protected function __setIsActive($Value)
    {
        $this->StatusId = $Value ? 1 : 0;
        return null;
    }


    protected function __getIsActive($Value)
    {
        return $this->StatusId == 1;
    }


    protected function __getStatus($Value)
    {
        return self::STATUSES[$this->StatusId];
    }


    protected function __setStatusId($Value)
    {
        if(empty(self::STATUSES[$Value]))
            throw new Exception('Status by StatusId not found');
        return $Value;
    }




    static public function FindAllByUserId(int $UserId = null, bool $CheckAccess = true) : array
    {
        if(empty($UserId)) $UserId = Authentication::GetAuthUser()->GetId();
        return parent::FindAll('"UserId" = $1', [$UserId], null, null, $CheckAccess);
    }


    static public function FindByFacebookUserId(int $FacebookUserId, bool $CheckAccess = true) : ?Facebook
    {
        return parent::FindOne('"FacebookUserId" = $1', [$FacebookUserId], $CheckAccess);
    }
}