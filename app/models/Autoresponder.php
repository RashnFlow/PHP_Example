<?php


namespace models;

/**
 * @property int $AutoresponderId {public get; private set;}
 * @property string $Name {public get; public set;}
 * @property array $FolderIds {public get; public set;}
 * @property string $Event {public get; public set;}
 * @property string $Status {public get; public set;}
 * @property bool $IsEnable {public get; public set;}
 * @property array $RangeWork {public get; public set;}
 * @property array $Sent {public get; public set;}
 */
class Autoresponder extends Model
{
    public const AUTORESPONDER_EVENT_ON_MOVED           = "OnMoved";
    public const AUTORESPONDER_EVENT_ON_NEW_DIALOG      = "OnNewDialog";
    public const AUTORESPONDER_EVENT_ON_MESSAGE         = "OnMessage";
    public const AUTORESPONDER_EVENT_ON_AFTER_HOURS     = "OnAfterHours";
    public const AUTORESPONDER_EVENT_ON_NEW_SUBSCRIBER  = "OnNewSubscriber";
    public const AUTORESPONDER_EVENT_ON_COMMENT         = "OnComment";

    public const SUPPORTED_EVENTS = [
        'OnMoved',
        'OnNewDialog',
        'OnMessage',
        'OnAfterHours',
        'OnNewSubscriber',
        'OnComment'
    ];


    public static ?string     $Table      = "Autoresponders";
    public static ?string     $PrimaryKey = "AutoresponderId";
    protected static array    $Properties = [
        "public string Name",
        "public string Event",
        "public string Status {private set; public get;} = Отключён",
        "public int CountSent {private set; public get; write false;}",
        "public int UserId",
        "public bool IsEnable",
        "public ?array RangeWork",
        "public array FolderIds",
        'public models\Message Message',
        "public array Sent"
    ];

    protected function OnCreateNew()
    {
        if(!empty(Authentication::GetAuthUser()))
            $this->UserId = Authentication::GetAuthUser()->GetId();
    }


    protected function __setIsEnable(bool $Value)
    {
        $this->Status = $Value ? 'Работает' : 'Отключён';
        return $Value;
    }

    protected function __getCountSent(bool $Value)
    {
        return count($this->Sent);
    }



    static public function FindByNameAndUserId(string $Name, ?int $UserId = null, bool $CheckAccess = true) : ?Autoresponder
    {
        if(empty($UserId))
            $UserId = Authentication::GetAuthUser()->GetId();
        return parent::FindOne('"UserId" = $1 and "Name" = $2', [$UserId, $Name], $CheckAccess);
    }


    static public function FindAllByUserId(?int $UserId = null, bool $CheckAccess = true) : array
    {
        if(empty($UserId))
            $UserId = Authentication::GetAuthUser()->GetId();
        return parent::FindAll('"UserId" = $1', [$UserId], null, null, $CheckAccess);
    }


    static public function FindAllActiveByUserId(?int $UserId = null, bool $CheckAccess = true) : array
    {
        if(empty($UserId))
            $UserId = Authentication::GetAuthUser()->GetId();
        return parent::FindAll('"UserId" = $1 and "IsEnable" = true', [$UserId], null, null, $CheckAccess);
    }
}