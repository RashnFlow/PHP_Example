<?php


namespace models;

use Exception;

/**
 * @property int $FacebookId {public get; public set;}
 * @property int $InstagramUserId {public get; public set;}
 * @property int $InstagramApiId {public get; private set;}
 * @property int $PageId {public get; private set;}
 * @property string $UserName {public get; public set;}
 * @property string $PictureUrl {public get; public set;}
 * @property string $Status {public get; public set;}
 * @property bool $CommentTracking {public get; public set;}
 * @property bool $IsActive {public get; public set;}
 * @property int $StatusId {public get; public set;}
 * @property ?int $DefaultFolder {public get; public set;}
 */
class InstagramApi extends Model
{

    public const STATUSES = [
        "Не активирован",
        "Активен"
    ];


    public static ?string     $Table      = 'InstagramsApi';
    public static ?string     $PrimaryKey = 'InstagramApiId';
    protected static array    $Properties = [
        'public int FacebookId',
        'public string UserName',
        'public string PictureUrl',
        'public int StatusId',
        'public int InstagramUserId',
        'public int PageId',
        'public ?int DefaultFolder',
        'public bool CommentTracking',
        'public bool IsActive {write false}',
        'public string Status {write false; public get; private set;}',
    ];

    protected function OnCreate() {}

    protected function OnUpdate() {}

    protected function OnDelete()
    {
        QueryCreator::Delete(DIALOG_TABLE, "(properties->'InstagramApiId')::int = $1", [$this->InstagramApiId])->Run();
    }

    protected function OnSave() {}


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


    protected function __getDefaultFolder($Value)
    {
        return !empty($Value) ? $Value : Folder::FindDefault(Facebook::FindById($this->FacebookId)->UserId)->GetId();
    }


    protected function __setStatusId($Value)
    {
        if(empty(self::STATUSES[$Value]))
            throw new Exception('Status by StatusId not found');
        return $Value;
    }




    static public function FindAllByFacebookId(int $FacebookId, bool $CheckAccess = true) : array
    {
        return parent::FindAll('"FacebookId" = $1', [$FacebookId], null, null, $CheckAccess);
    }


    static public function FindAllActiveByFacebookId(int $FacebookId, bool $CheckAccess = true) : array
    {
        return parent::FindAll('"FacebookId" = $1 and "StatusId" > 0', [$FacebookId], null, null, $CheckAccess);
    }


    static public function FindByInstagramUserId(int $InstagramUserId, bool $CheckAccess = true) : ?InstagramApi
    {
        return parent::FindOne('"InstagramUserId" = $1', [$InstagramUserId], $CheckAccess);
    }
}