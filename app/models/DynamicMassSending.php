<?php


namespace models;


use classes\Validator;
use Exception;
use models\dialogues\WhatsappDialog;

class DynamicMassSending extends CRUD
{
    public const EVENT_ON_MESSAGE       = "OnMessage";
    public const EVENT_ON_DIALOG_MOVE   = "OnDialogMove";


    private ?int     $Id                    = null;
    private ?string  $Name                  = null;
    private ?string  $SendFileUid           = null;
    private array    $WhatsappIdsReserve    = [];
    private array    $Sent                  = [];
    private ?Message $Message               = null;
    private ?int     $UserId                = null;
    private ?int     $TimeStart             = null;
    private ?int     $FolderId              = null;
    private ?int     $DialogFolderId        = null;
    private ?string  $Status                = null;
    private ?int     $CountSend             = 0;
    private ?int     $CountSent             = 0;
    private array    $CountSentToDay        = [];
    private bool     $IsEnable              = false;
    private array    $RangeWork             = [];
    private ?int     $SendDay               = null;
    private ?Event  $OnMessage             = null;
    private          $Avatar                = null;
    private ?string  $CompanyName           = null;
    private ?int     $ActivityId            = null;
    private ?int     $CreatedAt             = null;
    private ?int     $UpdatedAt             = null;



    /**
     * Do not send parameters when calling the constructor.
     * They are needed for the methods "Find". Instead, use
     * the "Set..()" methods to set values and "Get..()" to read them.
     */
    public function __construct(
        int     $Id                     = null,
        int     $UserId                 = null,
        string  $Name                   = null,
        string  $SendFileUid            = null,
        array   $WhatsappIdsReserve     = [],
        array   $Sent                   = [],
        Message $Message                = null,
        int     $TimeStart              = null,
        int     $FolderId               = null,
        int     $DialogFolderId         = null,
        string  $Status                 = null,
        int     $CountSend              = null,
        int     $CountSent              = null,
        array   $CountSentToDay         = [],
        bool    $IsEnable               = false,
        array   $RangeWork              = null,
        int     $SendDay                = null,
        Event  $OnMessage              = null,
                $Avatar                 = null,
        string  $CompanyName            = null,
        int     $ActivityId             = null,
        int     $CreatedAt              = null,
        int     $UpdatedAt              = null,
        bool    $CheckAccess            = true
    )
    {
        if($CheckAccess) Authorization::IsAccess(Authentication::GetAuthUser(), "GetDynamicMassSending");
        if(isset($Id))
        {
            if($CheckAccess)
                Authorization::IsAccessDynamicMassSending(Authentication::GetAuthUser(), $Id);

            $this->Id                       = $Id;
            $this->Name                     = $Name;
            $this->SendFileUid              = $SendFileUid;
            $this->WhatsappIdsReserve       = $WhatsappIdsReserve;
            $this->Message                  = $Message;
            $this->UserId                   = $UserId;
            $this->TimeStart                = $TimeStart;
            $this->FolderId                 = $FolderId;
            $this->DialogFolderId           = $DialogFolderId;
            $this->Status                   = $Status;
            $this->CountSend                = $CountSend;
            $this->CountSent                = $CountSent;
            $this->CountSentToDay           = $CountSentToDay;
            $this->IsEnable                 = $IsEnable;
            $this->Sent                     = $Sent;
            $this->SendDay                  = $SendDay;
            $this->RangeWork                = $RangeWork;
            $this->OnMessage                = $OnMessage;
            $this->Avatar                   = $Avatar;
            $this->CompanyName              = $CompanyName;
            $this->ActivityId               = $ActivityId;
            $this->CreatedAt                = $CreatedAt;
            $this->UpdatedAt                = $UpdatedAt;
        }
    }

    /**
     * Creates or updates
     */
    public function Save(bool $CheckAccess = true) : int
    {
        if($CheckAccess) Authorization::IsAccess(Authentication::GetAuthUser(), "SetDynamicMassSending");
        return $this->Id =
        parent::Save(   DYNAMIC_MASS_SENDING,
                        "dynamic_mass_sending_id",
                        $this->Id,
                        [
                            'name',
                            'user_id',
                            'send_file_uid',
                            'message',
                            'time_start',
                            'status',
                            'count_sent',
                            'is_enable',
                            'sent',
                            'send_day',
                            'range_work',
                            'on_message',
                            'whatsapp_ids_reserve',
                            'count_sent_to_day',
                            'avatar',
                            'company_name',
                            'activity_id',
                            'folder_id',
                            'dialog_folder_id',
                            'count_send'
                        ],
                        "$1,$2,$3,$4,$5,$6,$7,$8,$9,$10,$11,$12,$13,$14,$15,$16,$17,$18,$19,$20",
                        [
                            $this->Name,
                            $this->UserId,
                            $this->SendFileUid,
                            (empty($this->Message) ? [] : $this->Message->ToArray()),
                            $this->TimeStart,
                            $this->Status,
                            $this->CountSent,
                            $this->IsEnable,
                            $this->Sent,
                            $this->SendDay,
                            $this->RangeWork,
                            (empty($this->OnMessage) ? [] : $this->OnMessage->ToArray()),
                            $this->WhatsappIdsReserve,
                            $this->CountSentToDay,
                            $this->Avatar,
                            $this->CompanyName,
                            $this->ActivityId,
                            $this->FolderId,
                            $this->DialogFolderId,
                            $this->CountSend
                        ], true
                    );
    }


    /**
     * Removes
     */
    public function Delete(bool $CheckAccess = true)
    {
        if(!empty($this->GetFolderId()))
            Folder::FindById($this->GetFolderId())->Delete();

        if($CheckAccess) Authorization::IsAccess(Authentication::GetAuthUser(), "SetDynamicMassSending");
        parent::Delete(DYNAMIC_MASS_SENDING, "dynamic_mass_sending_id", $this->Id);
        $this->Id = null;
    }


    public function CheckStart() : bool
    {
        if(!$this->GetIsEnable())
            return false;

        if($this->GetTimeStart() > time())
            return false;

        if(count($this->GetRangeWork()) > 0)
        {
            if(date("H") < $this->GetRangeWork()["Start"]["H"] || date("H") > $this->GetRangeWork()["Stop"]["H"])
                return false;
            
            if($this->GetRangeWork()["Start"]["H"] == date("H"))
                if(date("i") < $this->GetRangeWork()["Start"]["M"])
                    return false;

            if($this->GetRangeWork()["Stop"]["H"] == date("H"))
                if(date("i") >= $this->GetRangeWork()["Stop"]["M"])
                    return false;
        }

        if($this->GetSendDay() > 0 && $this->GetSendDay() < $this->GetCountSentToDay())
            return false;

        return true;
    }


    public function RunOnMessage(WhatsappDialog &$Dialog)
    {
        if($Dialog->GetLastMessage()->GetIsMe())    return false;

        $DynamicMassSendingPhone = DynamicMassSendingPhone::FindByPhoneAndDynamicMassSendingId($Dialog->GetPhone(), $this->GetId());
        if(empty($DynamicMassSendingPhone))
            return false;

        $DynamicMassSendingPhone->SetIsRead(true);
        $DynamicMassSendingPhone->SetIsResponse(true);
        $DynamicMassSendingPhone->Save();

        if($this->OnMessage instanceof Event)
            if($this->OnMessage->Run($Dialog))
                $this->Save();
    }


    public function RunRunOnRead(WhatsappDialog &$Dialog)
    {
        if($Dialog->GetLastMessage()->GetIsMe())    return false;

        $DynamicMassSendingPhone = DynamicMassSendingPhone::FindByPhoneAndDynamicMassSendingId($Dialog->GetPhone(), $this->GetId());
        if(empty($DynamicMassSendingPhone))
            return false;

        $DynamicMassSendingPhone->SetIsRead(true);
        $DynamicMassSendingPhone->Save();
    }


    public function UploadFile()
    {
        $File = DynamicResource::FindByUid($this->SendFileUid);

        if(empty($File))
            throw new Exception("File is empty");

        foreach($File->GetAllLinesResource() as $obj)
        {
            if(!Validator::IsValid(explode(":", $obj), [
                ["Key" => 0, "Type" => "int"],
                ["Key" => 1, "IsNull" => true]
            ], false))
                throw new Exception("Format invalid");
        }

        foreach($File->GetAllLinesResource() as $obj)
        {
            $Str = explode(":", $obj);
            // $DynamicMassSendingPhone = DynamicMassSendingPhone::FindByPhoneAndDynamicMassSendingId($Str[0], $this->Id);
            // if(empty($DynamicMassSendingPhone))
                $DynamicMassSendingPhone = new DynamicMassSendingPhone();
            
            $DynamicMassSendingPhone->SetPhone(Validator::NormalizePhone($Str[0]));

            if(!empty($Str[1]))
                $DynamicMassSendingPhone->SetName($Str[1]);

            $DynamicMassSendingPhone->Save();
        }

        $this->SendFileUid = null;
    }


    public function ReserveWhatsapp() : int
    {
        $Whatsapp = Whatsapp::FindAll("is_active = true and is_dynamic = true and is_banned = false and not ($1 @> ('[' || whatsapp_id || ']')::jsonb)", [QueryCreator::Find(WHATSAPP_TABLE, "whatsapp_ids_reserve")], null, 1)[0];
        
        if(empty($Whatsapp))
            throw new Exception("Fail Reserve Whatsapp");

        $this->AddWhatsappIdsReserve($Whatsapp->GetId());
        return $Whatsapp->GetId();
    }




    public function GetUserId() : ?int
    {
        return $this->UserId;
    }


    public function GetCountSend() : int
    {
        return (int)$this->CountSend;
    }


    public function GetDialogFolderId() : ?int
    {
        return $this->DialogFolderId;
    }


    public function GetFolderId() : ?int
    {
        return $this->FolderId;
    }


    public function GetActivityId() : ?int
    {
        return $this->ActivityId;
    }


    public function GetUpdatedAt() : ?int
    {
        return $this->UpdatedAt;
    }


    public function GetWhatsappIdsReserve() : ?array
    {
        return $this->WhatsappIdsReserve;
    }


    public function GetCreatedAt() : ?int
    {
        return $this->CreatedAt;
    }


    public function GetId() : ?int
    {
        return $this->Id;
    }


    public function GetTimeStart() : ?int
    {
        return $this->TimeStart;
    }


    public function GetCountSent() : int
    {
        return (int)$this->CountSent;
    }


    public function GetCountSentToDay() : int
    {
        return (int)$this->CountSentToDay["CountSent"];
    }


    public function GetIsEnable() : bool
    {
        return $this->IsEnable;
    }


    public function GetSent() : array
    {
        return $this->Sent;
    }


    public function GetName() : ?string
    {
        return $this->Name;
    }


    public function GetCompanyName() : ?string
    {
        return $this->CompanyName;
    }


    /**
     * @return string Image byte
     */
    public function GetAvatar() : ?string
    {
        return pg_unescape_bytea($this->Avatar);
    }


    public function GetStatus() : ?string
    {
        return $this->Status;
    }


    public function GetMessage() : ?Message
    {
        return $this->Message;
    }


    public function GetOnMessage() : ?Event
    {
        return $this->OnMessage;
    }


    public function GetSendFileUid() : ?string
    {
        return $this->SendFileUid;
    }


    public function GetRangeWork() : array
    {
        return $this->RangeWork;
    }


    public function GetSendDay() : ?int
    {
        return $this->SendDay;
    }


    public function SetMessage(Message $Message)
    {
        $this->Message = $Message;
    }


    public function SetAvatar($Avatar)
    {
        $this->Avatar = pg_escape_bytea($Avatar);
    }


    public function SetOnMessage(Event $Event)
    {
        $Event->SetId(0);
        $this->OnMessage = $Event;
    }


    public function SetSendFileUid(string $SendFileUid)
    {
        $this->SendFileUid = $SendFileUid;
    }


    public function SetSent(array $Sent)
    {
        $this->Sent = $Sent;
    }


    public function SetRangeWork(array $RangeWork)
    {
        $this->RangeWork = $RangeWork;
    }


    public function SetIsEnable(bool $IsEnable)
    {
        $this->IsEnable = $IsEnable;
    }


    public function SetStatus(string $Status)
    {
        $this->Status = $Status;
    }


    public function SetName(string $Name)
    {
        $this->Name = $Name;
    }


    public function SetCompanyName(string $CompanyName)
    {
        $this->CompanyName = $CompanyName;
    }


    public function SetActivityId(int $ActivityId)
    {
        $this->ActivityId = $ActivityId;
    }


    public function AddWhatsappIdsReserve(int $WhatsappId)
    {
        if(array_search($WhatsappId, $this->WhatsappIdsReserve) === false)
            $this->WhatsappIdsReserve[] = $WhatsappId;
    }


    public function SetCountSend(int $CountSend)
    {
        $this->CountSend = $CountSend;
    }


    public function SetUserId(int $UserId)
    {
        $this->UserId = $UserId;
    }


    public function SetDialogFolderId(int $DialogFolderId)
    {
        $this->DialogFolderId = $DialogFolderId;
    }


    public function SetFolderId(int $FolderId)
    {
        $this->FolderId = $FolderId;
    }


    public function SetSendDay(int $SendDay)
    {
        $this->SendDay = $SendDay;
    }


    public function SetTimeStart(int $TimeStart)
    {
        $this->TimeStart = $TimeStart;
    }


    public function CalculateCountSent(int $CountSent = 1)
    {
        $this->CountSent += $CountSent;
        
        if(date("d", $this->CountSentToDay["Time"]) != date("d"))
            $this->CountSentToDay["CountSent"] = 0;

        $this->CountSentToDay["CountSent"]++;
        $this->CountSentToDay["Time"] = time();
    }


    public function ClearCountSent(int $CountSent)
    {
        $this->CountSent = 0;
        $this->CountSentToDay["CountSent"] = 0;
        $this->CountSentToDay["Time"] = time();
    }




    static public function FindByDynamicMassSendingNameAndUserId(string $DynamicMassSendingName, int $UserId = null, bool $CheckAccess = true) : ?DynamicMassSending
    {
        if(empty($UserId)) $UserId = Authentication::GetAuthUser()->GetId();
        $Find = (self::CreateClassObjs(parent::Find(DYNAMIC_MASS_SENDING, "dynamic_mass_sending_id", "name = $1 and user_id = $2", [$DynamicMassSendingName, $UserId], null, 1), $CheckAccess))[0];
        return empty($Find) ? null : $Find;
    }


    static public function FindById(int $DynamicMassSendingId, bool $CheckAccess = true) : ?DynamicMassSending
    {
        $Find = (self::CreateClassObjs(parent::Find(DYNAMIC_MASS_SENDING, "dynamic_mass_sending_id", "dynamic_mass_sending_id = $1", [$DynamicMassSendingId], null, 1), $CheckAccess))[0];
        return empty($Find) ? null : $Find;
    }


    static public function FindAll(string $Where = null, array $Parameters = [], int $Offset = null, int $Limit = null, bool $CheckAccess = true) : array
    {
        return self::CreateClassObjs(parent::Find(DYNAMIC_MASS_SENDING, "dynamic_mass_sending_id", $Where, $Parameters, $Offset, $Limit), $CheckAccess);
    }


    static public function FindByUserId(int $UserId = null, int $Offset = null, int $Limit = null, bool $CheckAccess = true) : array
    {
        if(empty($UserId)) $UserId = Authentication::GetAuthUser()->GetId();
        return self::CreateClassObjs(parent::Find(DYNAMIC_MASS_SENDING, "dynamic_mass_sending_id", "user_id = $1", [$UserId], $Offset, $Limit), $CheckAccess);
    }


    static public function FindAllEnable(bool $CheckAccess = true) : array
    {
        return self::CreateClassObjs(parent::Find(DYNAMIC_MASS_SENDING, "dynamic_mass_sending_id", "is_enable = true", []), $CheckAccess);
    }


    static public function FindAllEnableByUserId(int $UserId = null, bool $CheckAccess = true) : array
    {
        if(empty($UserId)) $UserId = Authentication::GetAuthUser()->GetId();
        return self::CreateClassObjs(parent::Find(DYNAMIC_MASS_SENDING, "dynamic_mass_sending_id", "is_enable = true and user_id = $1", [$UserId]), $CheckAccess);
    }


    /**
     * @return array DynamicMassSending Classes
     */
    static public function CreateClassObjs(array $Obj, bool $CheckAccess = true) : array
    {
        $Out = [];
        foreach($Obj as $TempObj)
        {
            $Out[] =    new DynamicMassSending(
                $TempObj->dynamic_mass_sending_id,
                $TempObj->user_id,
                $TempObj->name,
                $TempObj->send_file_uid,
                json_decode($TempObj->whatsapp_ids_reserve, true),
                json_decode($TempObj->sent, true),
                Message::CreateMessageObj(json_decode($TempObj->message, true)),
                $TempObj->time_start,
                $TempObj->folder_id,
                $TempObj->dialog_folder_id,
                $TempObj->status,
                $TempObj->count_send,
                $TempObj->count_sent,
                json_decode($TempObj->count_sent_to_day, true),
                $TempObj->is_enable,
                json_decode($TempObj->range_work, true),
                $TempObj->send_day,
                empty(json_decode($TempObj->on_message, true)) ? null : Event::CreateEventObj(json_decode($TempObj->on_message, true)),
                $TempObj->avatar,
                $TempObj->company_name,
                $TempObj->activity_id,
                strtotime($TempObj->created_at),
                strtotime($TempObj->updated_at),
                $CheckAccess
            );
        }
        return $Out;
    }
}