<?php


namespace models;

use Exception;
use models\dialogues\Dialog;
use models\dialogues\InstagramApiDialog;
use models\dialogues\InstagramDialog;
use models\dialogues\WhatsappDialog;
use Throwable;

/**
 * @property int $MassSendingId {public get; private set;}
 * @property string $Name {public get; private set;}
 * @property ?string $Status {public get; private set;}
 * @property array $Send {public get; private set;}
 * @property array $Sent {public get; private set;}
 * @property array $RangeWork {public get; private set;}
 * @property int $UserId {public get; private set;}
 * @property int $SentCount {public get; private set;}
 * @property ?int $TimeStart {public get; private set;}
 * @property int $Interval {public get; private set;}
 * @property int $SendDay {public get; private set;}
 * @property bool $Random {public get; private set;}
 * @property bool $IsEnable {public get; private set;}
 * @property Message $Message {public get; private set;}
 * @property Event $OnMessage {public get; private set;}
 */
class MassSending extends Model
{
    public const EVENT_ON_MESSAGE       = "OnMessage";
    public const EVENT_ON_DIALOG_MOVE   = "OnDialogMove";


    public static ?string     $Table      = 'MassSendings';
    public static ?string     $PrimaryKey = 'MassSendingId';
    public static ?bool       $CreatedUpdatedAt = true;
    protected static array    $Properties = [
        'public string Name',
        'public ?string Status = Создан',
        'public array Send',
        'public array Sent',
        'public array RangeWork',
        'public int UserId',
        'public int SentCount',
        'public ?int TimeStart',
        'public int Interval = 20',
        'public int SendDay',
        'public bool Random',
        'public bool IsEnable',
        'public models\Message Message',
        'public ?models\Event OnMessage',
    ];

    protected function OnCreateNew()
    {
        $this->TimeStart = time();
        if(!empty(Authentication::GetAuthUser()))
            $this->UserId = Authentication::GetAuthUser()->GetId();
    }


    public function RunOnMessage(Dialog &$Dialog)
    {
        if(!$this->CheckStart($Dialog)) return;
        if(!($Dialog instanceof Dialog)) throw new Exception("Data of type Dialog was expected");

        if($this->OnMessage instanceof Event)
            if($this->OnMessage->Run($Dialog))
                $this->Save();
    }


    private function CheckStart(Dialog $Dialog) : bool
    {
        if($Dialog->GetLastMessage()->GetIsMe())    return false;

        if(is_array($this->Send["FolderIds"]))
            if(array_search($Dialog->GetFolderId(), $this->Send["FolderIds"]) !== false)
                return true;

        if(is_array($this->Send["DialogIds"]))
            if(array_search($Dialog->GetId(), $this->Send["DialogIds"]) !== false)
                return true;

        return false;
    }


    public function Send()
    {
        if(!$this->IsEnable)    return;
        if($this->TimeStart > time())   return;

        if(count($this->RangeWork) > 0)
        {
            if(date("H") < $this->RangeWork["Start"]["H"] || date("H") > $this->RangeWork["Stop"]["H"])     return;
            
            if($this->RangeWork["Start"]["H"] == date("H"))
                if(date("i") < $this->RangeWork["Start"]["M"])
                    return;

            if($this->RangeWork["Stop"]["H"] == date("H"))
                if(date("i") >= $this->RangeWork["Stop"]["M"])
                    return;
        }

        if($this->SendDay > 0)
        {
            $SentCountToDay = 0;
            foreach($this->Sent as $obj)
                if(date("d") == date("d", $obj["Time"]))
                    $SentCountToDay++;

            if($this->SendDay <= $SentCountToDay)   return;
        }

        if($this->Interval > 0)
            if(time() - $this->Sent[count($this->Sent) - 1]["Time"] < $this->Interval) return;
        

        //Рассылка
        $Dialogues = null;
        if(is_array($this->Send["FolderIds"]))
            $Dialogues = Dialog::FindAll('($1) @> (\'[\' || folder_id || \']\')::jsonb and dialog_id not in ($2)', [
                QueryCreator::Find(self::$Table, '"Send"->\'FolderIds\'', '"MassSendingId" = $1', [$this->MassSendingId]),
                new Query('SELECT (arr.item_object->\'Sent\')::int FROM "MassSendings", jsonb_array_elements("Sent") with ordinality arr(item_object) WHERE "MassSendingId" = $1', "Find", [$this->MassSendingId])
            ]);

        if(is_array($this->Send["DialogIds"]) && empty($Dialog))
            $Dialogues = Dialog::FindAll('($1) @> (\'[\' || dialog_id || \']\')::jsonb and dialog_id not in ($2)', [
                QueryCreator::Find(self::$Table, '"Send"->\'DialogIds\'', '"MassSendingId" = $1', [$this->MassSendingId]),
                new Query('SELECT (arr.item_object->\'Sent\')::int FROM "MassSendings", jsonb_array_elements("Sent") with ordinality arr(item_object) WHERE "MassSendingId" = $1', "Find", [$this->MassSendingId])
            ]);

        if(empty($Dialogues))
        {
            $this->Status = "База закончиась, ожидание...";
            $this->Save();
            return;
        }

        $Dialog = $Dialogues[array_rand($Dialogues)];

        //FixDialogues
        try
        {
            if($Dialog instanceof WhatsappDialog)
            {
                $Find = Whatsapp::FindById($Dialog->GetWhatsappId());
                if(empty($Find) || !$Find->GetIsActive())
                    return;
            }
            else if($Dialog instanceof InstagramDialog)
            {
                $Find = Instagram::FindById($Dialog->GetInstagramId());
                if(empty($Find) || !$Find->GetIsActive())
                    return;
            }
            else if($Dialog instanceof InstagramApiDialog)
            {
                $Find = InstagramApi::FindById($Dialog->GetInstagramApiId());
                if(empty($Find) || !$Find->IsActive)
                    return;
            }
        }
        catch(Throwable $error) {}

        $Sent = ["Time" => time(), "Sent" => $Dialog->GetId()];
        try
        {
            $this->Message->SetTime(time());
            $this->Message->SetSource("MassSending");
            (new \controllers\MessageController())->SendMessage($Dialog, $this->Message);
            $this->SentCount++;
        }
        catch(Exception $error) {
            $Sent['Status'] = 'FailSend';
        }

        $this->Sent[] = $Sent;
        $this->Status = "Работает";
        $this->Save();
    }




    static public function FindAllEnable(bool $CheckAccess = true) : array
    {
        return parent::FindAll('"IsEnable" = true', [], null, null, $CheckAccess);
    }


    static public function FindByMassSendingNameAndUserId(string $MassSendingName, int $UserId = null, bool $CheckAccess = true) : ?MassSending
    {
        if(empty($UserId)) $UserId = Authentication::GetAuthUser()->GetId();
        return parent::FindOne('"Name" = $1 and "UserId" = $2', [$MassSendingName, $UserId], $CheckAccess);
    }

    static public function FindByUserId(int $UserId = null, int $Offset = null, int $Limit = null, bool $CheckAccess = true) : array
    {
        if(empty($UserId)) $UserId = Authentication::GetAuthUser()->GetId();
        return parent::FindAll('"UserId" = $1', [$UserId], $Offset, $Limit, $CheckAccess);
    }
}