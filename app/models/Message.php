<?php


namespace models;


use Exception;


class Message extends CRUD
{

    public const MESSAGE_STATUS_MD_DOWNGRADE            = -7;
    public const MESSAGE_STATUS_INACTIVE                = -6;
    public const MESSAGE_STATUS_CONTENT_UNUPLOADABLE    = -5;
    public const MESSAGE_STATUS_CONTENT_TOO_BIG         = -4;
    public const MESSAGE_STATUS_CONTENT_GONE            = -3;
    public const MESSAGE_STATUS_EXPIRED                 = -2;
    public const MESSAGE_STATUS_FAILED                  = -1;
    public const MESSAGE_STATUS_CLOCK                   = 0;
    public const MESSAGE_STATUS_SENT                    = 1;
    public const MESSAGE_STATUS_RECEIVED                = 2;
    public const MESSAGE_STATUS_READ                    = 3;
    public const MESSAGE_STATUS_PLAYED                  = 4;


    public const MESSAGE_TYPE_IMG       = "img";
    public const MESSAGE_TYPE_TEXT      = "text";
    public const MESSAGE_TYPE_DOCUMENT  = "document";
    public const MESSAGE_TYPE_VIDEO     = "video";

    private ?int     $Id         = null;
    private ?int     $StatusId   = self::MESSAGE_STATUS_CLOCK;
    private ?int     $DialogId   = null;
    private ?string  $Uid        = null;
    private ?string  $Text       = null;
    private ?string  $Document   = null;
    private ?string  $Video      = null;
    private ?string  $Caption    = null;
    private string   $Type       = self::MESSAGE_TYPE_TEXT;
    private ?string  $Img        = null;
    private ?int     $Time       = null;
    private bool     $IsMe       = false;
    private ?string  $Source     = null;


    /**
     * Do not send parameters when calling the constructor.
     * They are needed for the methods "Find". Instead, use
     * the "Set..()" methods to set values and "Get..()" to read them.
     */
    public function __construct(
        int     $Id             = null,
        int     $StatusId       = null,
        int     $DialogId       = null,
        string  $Uid            = null,
        string  $Text           = null,
        string  $Document       = null,
        string  $Video          = null,
        string  $Caption        = null,
        string  $Type           = null,
        string  $Img            = null,
        int     $Time           = null,
        bool    $IsMe           = false,
        bool    $CheckAccess    = true)
    {
        if($CheckAccess) Authorization::IsAccess(Authentication::GetAuthUser(), "GetMessage");
        
        $this->Id       = $Id;
        $this->StatusId = isset($StatusId) ? $StatusId : self::MESSAGE_STATUS_CLOCK;
        $this->Uid      = $Uid;
        $this->DialogId = $DialogId;
        $this->Text     = $Text;
        $this->Document = $Document;
        $this->Video    = $Video;
        $this->Type     = isset($Type) ? $Type : self::MESSAGE_TYPE_TEXT;
        $this->Img      = $Img;
        $this->Caption  = $Caption;
        $this->Time     = isset($Time) ? $Time : time();
        $this->IsMe     = $IsMe;
    }


    /**
     * Creates or updates
     */
    public function Save(bool $CheckAccess = true) : int
    {
        if($CheckAccess) Authorization::IsAccess(Authentication::GetAuthUser(), "SetMessage");
        return $this->Id =
        parent::Save(   MESSAGE_TABLE,
                        "message_id",
                        $this->Id,
                        [
                            'status_id',
                            'message_uid',
                            'text',
                            'document',
                            'video',
                            'type',
                            'img',
                            'caption',
                            'time',
                            'is_me',
                            'dialog_id'
                        ],
                        "$1,$2,$3,$4,$5,$6,$7,$8,$9,$10,$11",
                        [
                            $this->StatusId,
                            $this->Uid,
                            $this->Text,
                            $this->Document,
                            $this->Video,
                            $this->Type,
                            $this->Img,
                            $this->Caption,
                            $this->Time,
                            $this->IsMe,
                            $this->DialogId
                        ]
                    );
    }

    /**
     * Removes
     */
    public function Delete(bool $CheckAccess = true)
    {
        if($CheckAccess) Authorization::IsAccess(Authentication::GetAuthUser(), "SetMessage");
        
        parent::Delete(MESSAGE_TABLE, "message_id", $this->Id);
        $this->Id = null;
    }


    public function Untie()
    {
        $this->Id = null;
    }


    public function GetId() : ?int
    {
        return $this->Id;
    }


    public function GetSource() : ?string
    {
        return $this->Source;
    }


    public function GetStatusId() : ?int
    {
        return $this->StatusId;
    }


    public function GetDialogId() : ?int
    {
        return $this->DialogId;
    }


    public function GetUid() : ?string
    {
        return $this->Uid;
    }


    public function GetText() : ?string
    {
        return $this->Text;
    }


    public function GetDocument() : ?string
    {
        return $this->Document;
    }


    public function GetVideo() : ?string
    {
        return $this->Video;
    }


    public function GetCaption() : ?string
    {
        return $this->Caption;
    }


    public function GetType() : ?string
    {
        return $this->Type;
    }


    public function GetImg() : ?string
    {
        return $this->Img;
    }


    public function GetTime() : ?int
    {
        return $this->Time;
    }


    public function GetIsMe() : bool
    {
        return $this->IsMe;
    }


    public function IsRead() : bool
    {
        return $this->StatusId >= Message::MESSAGE_STATUS_READ;
    }


    public function IsSent() : bool
    {
        return $this->StatusId >= Message::MESSAGE_STATUS_SENT;
    }


    public function IsClock() : bool
    {
        return $this->StatusId == Message::MESSAGE_STATUS_CLOCK;
    }


    public function IsFailed() : bool
    {
        return $this->StatusId == Message::MESSAGE_STATUS_FAILED;
    }


    public function SetSource(string $Source)
    {
        $this->Source = $Source;
    }


    public function SetText(string $Text)
    {
        $this->Text = $Text;
        $this->Type = self::MESSAGE_TYPE_TEXT;
    }


    public function SetStatusId(int $StatusId)
    {
        $this->StatusId = $StatusId;
    }


    public function SetDialogId(int $DialogId)
    {
        $this->DialogId = $DialogId;
    }


    public function SetCaption(string $Caption)
    {
        $this->Caption = $Caption;
    }


    public function SetContent(string $Type, string $Content, string $Caption = null)
    {
        switch($Type)
        {
            case self::MESSAGE_TYPE_TEXT:
                $this->SetText($Content);
                break;

            case self::MESSAGE_TYPE_IMG:
                $this->SetImg($Content);
                break;

            case self::MESSAGE_TYPE_DOCUMENT:
                $this->SetDocument($Content);
                break;

            case self::MESSAGE_TYPE_VIDEO:
                $this->SetVideo($Content);
                break;

            default:
                throw new Exception("Type not found");
                break;
        }

        if(!empty($Caption))
            $this->SetCaption($Caption);
    }


    public function SetImg(string $Img)
    {
        $this->Img = $Img;
        $this->Type = self::MESSAGE_TYPE_IMG;
    }


    public function SetDocument(string $Document)
    {
        $this->Document = $Document;
        $this->Type = self::MESSAGE_TYPE_DOCUMENT;
    }


    public function SetVideo(string $Video)
    {
        $this->Video = $Video;
        $this->Type = self::MESSAGE_TYPE_VIDEO;
    }


    public function SetUid(string $Uid)
    {
        $this->Uid = $Uid;
    }


    public function SetTime(int $Time)
    {
        $this->Time = $Time;
    }


    public function SetIsMe(bool $IsMe)
    {
        $this->IsMe = $IsMe;
    }


    public function Read()
    {
        $this->SetStatusId(Message::MESSAGE_STATUS_READ);
    }


    public function Clock()
    {
        $this->SetStatusId(Message::MESSAGE_STATUS_CLOCK);
    }


    public function Sent()
    {
        $this->SetStatusId(Message::MESSAGE_STATUS_SENT);
    }


    public function Failed()
    {
        $this->SetStatusId(Message::MESSAGE_STATUS_FAILED);
    }


    public function ToArray() : array
    {
        return
        [
            "Id"        => $this->GetId(),
            "StatusId"  => $this->GetStatusId(),
            "Uid"       => $this->GetUid(),
            "Text"      => $this->GetText(),
            "Document"  => $this->GetDocument(),
            "Video"     => $this->GetVideo(),
            "Type"      => $this->GetType(),
            "Img"       => $this->GetImg(),
            "IsMe"      => $this->GetIsMe(),
            "Time"      => $this->GetTime(),
            "Caption"   => $this->GetCaption(),
            "DialogId"  => $this->GetDialogId()
        ];
    }


    static public function CreateMessageObj(array $ArrayObj, bool $SetIsRead = false) : Message
    {
        return new Message(
            $ArrayObj["Id"],
            $SetIsRead && !((bool)$ArrayObj["IsMe"]) ? Message::MESSAGE_STATUS_READ : $ArrayObj["StatusId"],
            $ArrayObj["DialogId"],
            $ArrayObj["Uid"],
            $ArrayObj["Text"],
            $ArrayObj["Document"],
            $ArrayObj["Video"],
            $ArrayObj["Caption"],
            $ArrayObj["Type"],
            $ArrayObj["Img"],
            $ArrayObj["Time"],
            (bool)$ArrayObj["IsMe"]
        );
    }



    static public function FindById(int $EmailId, bool $CheckAccess = true) : ?Message
    {
        $Find = (self::CreateClassObjs(parent::Find(MESSAGE_TABLE, "message_id", "message_id = $1", [$EmailId], null, 1), $CheckAccess))[0];
        return empty($Find) ? null : $Find;
    }


    static public function FindByUid(string $MessageUid, bool $CheckAccess = true) : ?Message
    {
        $Find = (self::CreateClassObjs(parent::Find(MESSAGE_TABLE, "message_id", "message_uid = $1", [$MessageUid], null, 1), $CheckAccess))[0];
        return empty($Find) ? null : $Find;
    }


    static public function FindLastByDialogId(int $DialogId, bool $CheckAccess = true) : ?Message
    {
        $Find = (self::CreateClassObjs(parent::Find(MESSAGE_TABLE, "time DESC", "dialog_id = $1", [$DialogId], null, 1), $CheckAccess))[0];
        return empty($Find) ? null : $Find;
    }
    
    
    static public function FindAll(string $Where = null, array $Parameters = [], int $Offset = null, int $Limit = null, bool $CheckAccess = true) : array
    {
        return self::CreateClassObjs(parent::Find(MESSAGE_TABLE, "message_id", $Where, $Parameters, $Offset, $Limit), $CheckAccess);
    }


    static public function FindAllByDialogId(int $DialogId, int $Offset = null, int $Limit = null, bool $CheckAccess = true) : array
    {
        return self::CreateClassObjs(parent::Find(MESSAGE_TABLE, "time DESC", "dialog_id = $1", [$DialogId], $Offset, $Limit), $CheckAccess);
    }


    /**
     * @return array Message Classes
     */
    static public function CreateClassObjs(array $Obj, $CheckAccess = true) : array
    {
        $Out = [];
        foreach($Obj as $TempObj)
        {
            $Out[] =    new Message(
                $TempObj->message_id,
                $TempObj->status_id,
                $TempObj->dialog_id,
                $TempObj->message_uid,
                $TempObj->text,
                $TempObj->document,
                $TempObj->video,
                $TempObj->caption,
                $TempObj->type,
                $TempObj->img,
                $TempObj->time,
                $TempObj->is_me,
                $CheckAccess
            );
        }
        return $Out;
    }
}