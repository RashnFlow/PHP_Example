<?php


namespace models\dialogues;

use classes\Tools;
use Exception;
use models\Authentication;
use models\Authorization;
use models\CRUD;
use models\DynamicResource;
use models\Message;
use models\ModelCollection;
use models\QueryCreator;
use Throwable;

abstract class Dialog extends CRUD
{
    private ?int     $Id            = null;
    private ?string  $Name          = null;
    private ?string  $Type          = null;
    private          $Avatar        = null;
    private bool     $IsActive      = true;
    private bool     $IsOnline      = false;
    private bool     $IsNew         = false;
    private ?int     $FolderId      = null;
    private array    $Properties    = [];
    private array    $Tags          = [];
    private array    $Whitelist     = [];
    private ?int     $CreatedAt     = null;
    private ?int     $UpdatedAt     = null;

    private ModelCollection $TempMessages;


    /**
     * Do not send parameters when calling the constructor.
     * They are needed for the methods "Find". Instead, use
     * the "Set..()" methods to set values and "Get..()" to read them.
     */
    public function __construct(
        int     $Id             = null,
        string  $Type           = null,
        string  $Name           = null,
        string  $Avatar         = null,
        bool    $IsActive       = true,
        bool    $IsOnline       = false,
        int     $FolderId       = null,
        array   $Properties     = [],
        array   $Tags           = [],
        array   $Whitelist      = [],
        int     $CreatedAt      = null,
        int     $UpdatedAt      = null,
        bool    $CheckAccess    = true
    )
    {
        $this->TempMessages = new ModelCollection();

        if($CheckAccess) Authorization::IsAccess(Authentication::GetAuthUser(), "GetDialog", $this);
        if(isset($Id))
        {
            if($CheckAccess)
                Authorization::IsAccessDialog(Authentication::GetAuthUser(), $Id, $Type);

            $this->Id               = $Id;
            $this->Type             = $Type;
            $this->Name             = $Name;
            $this->Avatar           = $Avatar;
            $this->IsActive         = $IsActive;
            $this->IsOnline         = $IsOnline;
            $this->FolderId         = $FolderId;
            $this->Properties       = $Properties;
            $this->Tags             = $Tags;
            $this->Whitelist        = $Whitelist;
            $this->CreatedAt        = $CreatedAt;
            $this->UpdatedAt        = $UpdatedAt;

            $this->OnCreate($CheckAccess);
        }
        else
            $this->IsNew = true;
    }


    /**
     * Creates or updates
     */
    public function Save(bool $CheckAccess = true) : int
    {
        if($CheckAccess) Authorization::IsAccess(Authentication::GetAuthUser(), "SetDialog", $this);
        $this->Id = parent::Save(   DIALOG_TABLE, "dialog_id", $this->Id, 
                        [
                            "folder_id",
                            "name",
                            "avatar",
                            "is_active",
                            "is_online",
                            "properties",
                            "tags",
                            "whitelist",
                            "type"
                        ],
                        "$1,$2,$3,$4,$5,$6,$7,$8,$9",
                        [
                            $this->FolderId,
                            $this->Name,
                            $this->Avatar,
                            $this->IsActive,
                            $this->IsOnline,
                            $this->Properties,
                            $this->Tags,
                            $this->Whitelist,
                            static::class
                        ], true);

        $Collection = $this->TempMessages->ToArray();
        $this->TempMessages->Clear();
        foreach($Collection as $Message)
        {
            $Message->SetDialogId($this->Id);
            $this->TempMessages->Add($Message);
        }
        $this->TempMessages->SaveModels();
        $this->TempMessages->Clear();

        return $this->Id;
    }


    /**
     * Removes
     */
    public function Delete(bool $CheckAccess = true)
    {
        if($CheckAccess) Authorization::IsAccess(Authentication::GetAuthUser(), "SetDialog", $this);
        
        // $Models = new ModelCollection();
        // foreach($this->GetMessages() as $Message)
        // {
        //     if($Message->GetType() == "img")
        //     {
        //         $Resources = DynamicResource::FindByUidAndUserId($Message->GetImg(), Authentication::GetAuthUser()->GetId());
        //         if(!empty($Resources))
        //             $Models->Add($Resources);
        //     }
        //     $Models->Add($Message);
        // }
        // $Models->DeleteModels();

        parent::Delete(DIALOG_TABLE, "dialog_id", $this->Id);
        $this->Id = null;
    }


    abstract protected function OnCreate(bool $CheckAccess);


    public function GetCountUnreadMessage() : ?int
    {
        return (int)QueryCreator::Count(MESSAGE_TABLE, 'status_id < 3 and is_me = false and dialog_id = $1', [$this->GetId()])->Run()[0]->count;
    }


    protected function LoadImportFile(string $FileUid) : array
    {
        $File = DynamicResource::FindByUidAndUserId($FileUid);
        if(empty($File))
            throw new Exception("File not found");

        $Data = $File->GetResource();
        if(empty($Data))
            throw new Exception("File data is empty");

        $Data = str_replace("\r\n", "\n", $Data);
        $Data = str_replace("\r", "\n", $Data);

        if(count(explode("\n", $Data)) > 10000)
            throw new Exception("Count limit");

        //Файл в массив
        $FileArray = [];
        switch($File->GetExtension())
        {
            case 'txt':
                $FileArray = explode("\n", $Data);
                array_walk($FileArray, function(&$Item, $key) {
                    $Item = ["Data" => $Item];
                });
                break;

            case 'csv':
                if (($Handle = fopen($File->GetPath(), "r")) !== FALSE)
                {
                    $Columns = null;
                    while (($Str = fgetcsv($Handle, 1000, ",")) !== FALSE)
                    {
                        if(count($Str) > 2 || count($Str) == 0)
                            throw new Exception("Invalid file");

                        if(empty($Columns))
                        {
                            array_walk($Str, function(&$Item, $key) {
                                $Item = mb_convert_case($Item, MB_CASE_TITLE);
                                if(!($Item == "Data" || $Item == "Name"))
                                    throw new Exception("Invalid file");
                            });
                            if(array_search("Data", $Str) === false)
                                throw new Exception("Invalid file");
                            $Columns = $Str;
                            continue;
                        }

                        $TempArray = [];
                        for($i = 0; $i < count($Str); $i++)
                            $TempArray[$Columns[$i]] = $Str[$i];

                        $FileArray[] = $TempArray;
                    }
                    fclose($Handle);
                }
                else
                    throw new Exception("Invalid file");
                break;

            case 'xlsx':
        
                break;
        }

        $Out = [];
        $TempDataArray = [];
        foreach(array_column($FileArray, "Data") as $key => $obj)
        {
            if(array_search($obj, $TempDataArray) === false && !empty($FileArray[$key]))
            {
                $TempDataArray[] = $obj;
                $Out[] = $FileArray[$key];
            }
        }

        unset($TempDataArray, $FileArray, $TempArray);

        return $Out;
    }


    abstract static public function ImportBase(string $FileUid, int $AccountId, int $FolderId = null);


    public function GetUpdatedAt() : ?int
    {
        return $this->UpdatedAt;
    }


    public function GetWhitelist() : array
    {
        return $this->Whitelist;
    }


    public function GetCreatedAt() : ?int
    {
        return $this->CreatedAt;
    }


    public function IsNew() : bool
    {
        return $this->IsNew;
    }


    public function GetTags() : array
    {
        return $this->Tags;
    }


    public function CheckTag(string $Tag) : bool
    {
        return array_search($Tag, $this->Tags) !== false;
    }


    /**
     * return Property
     */
    public function GetProperty(string $key)
    {
        return $this->Properties[$key];
    }


    public function GetProperties() : array
    {
        return $this->Properties;
    }


    public function GetAllKeyPropertys() : array
    {
        return array_keys($this->Properties);
    }


    /**
     * @return array Message Classes
     */
    public function GetMessages(int $Offset = 0, int $Limit = null, bool $Reverse = false) : array
    {
        if(!empty($this->GetId()))
            $Messages = Message::FindAllByDialogId($this->GetId(), $Offset, $Limit);
        else
            $Messages = $this->TempMessages->ToArray();
        return $Reverse ? array_reverse($Messages) : $Messages;
    }


    public function GetLastMessage() : ?Message
    {
        return Message::FindLastByDialogId($this->GetId());
    }


    public function GetType() : ?string
    {
        return $this->Type;
    }


    public function GetNameType() : ?string
    {
        return empty($this->Type) ? null : end(explode("\\", $this->Type));
    }


    public function GetIsOnline() : bool
    {
        return $this->IsOnline;
    }


    public function GetIsActive() : bool
    {
        return $this->IsActive;
    }


    public function GetIsRead() : bool
    {
        $LastMessage = $this->GetLastMessage();
        return !(!empty($LastMessage) && !$LastMessage->GetIsMe() && !$LastMessage->IsRead());
    }


    public function GetFolderId() : ?int
    {
        return $this->FolderId;
    }


    /**
     * @return string Image byte
     */
    public function GetAvatar() : ?string
    {
        return pg_unescape_bytea($this->Avatar);
    }


    public function GetName() : ?string
    {
        return $this->Name;
    }


    public function GetId() : ?int
    {
        return $this->Id;
    }


    public function SetName(string $Name)
    {
        $this->Name = $Name;
    }


    public function SetIsNew(bool $IsNew)
    {
        $this->IsNew = $IsNew;
    }


    public function SetAvatar($Avatar)
    {
        $this->Avatar = pg_escape_bytea($Avatar);
    }


    public function AddTag(string $Tag)
    {
        if(array_search($Tag, $this->Tags) === false)
            $this->Tags[] = $Tag;
        else
            throw new Exception("Tag exists");
    }


    public function SetTags(array $Tags)
    {
        $this->Tags = [];
        foreach($Tags as $obj)
            $this->AddTag($obj);
    }


    public function SetProperty(string $key, $val)
    {
        $this->Properties[$key] = $val;
    }


    public function AddProperties(array $Properties)
    {
        $this->Properties = array_merge($this->Properties, $Properties);
    }


    public function AddMessage(Message &$Message)
    {
        if(empty($Message->GetUid()))
        {
            $Uid = null;
            while(true)
            {
                $Uid = Tools::GenerateString();
                
                foreach($this->TempMessages->ToArray() as $Message)
                    if($Message->GetUid() == $Uid)
                        continue 2;

                if(empty(Message::FindByUid($Uid)))
                    break;
            }
            $Message->SetUid($Uid);
        }

        $FileUid = null;
        switch($Message->GetType())
        {
            case Message::MESSAGE_TYPE_IMG:
                $FileUid = $Message->GetImg();
            break;

            case Message::MESSAGE_TYPE_VIDEO:
                $FileUid = $Message->GetVideo();
            break;

            case Message::MESSAGE_TYPE_DOCUMENT:
                $FileUid = $Message->GetDocument();
            break;
        }

        if(!empty($FileUid) && empty(DynamicResource::FindByUid($FileUid)))
            throw new Exception('DynamicResource is empty');

        $this->TempMessages->Add($Message);
    }


    public function SetIsOnline(bool $IsOnline)
    {
        $this->IsOnline = $IsOnline;
    }


    public function SetIsActive(bool $IsActive)
    {
        $this->IsActive = $IsActive;
    }


    public function Read()
    {
        QueryCreator::Update(MESSAGE_TABLE, 'status_id = $1', 'dialog_id = $2', [Message::MESSAGE_STATUS_READ, $this->GetId()])->Run();
    }


    public function SetFolderId(int $FolderId)
    {
        $this->FolderId = $FolderId;

        try
        {
            if(!$this->IsNew())
                \controllers\AutoresponderController::RunEvent(\models\Autoresponder::AUTORESPONDER_EVENT_ON_MOVED, $this);
        }
        catch(Throwable $error) {}
    }


    public function SetWhitelist(array $Whitelist)
    {
        $this->Whitelist = $Whitelist;
    }


    public function AddUserIdToWhitelist(int $UserId)
    {
        if(array_search($UserId, $this->Whitelist) === false)
            $this->Whitelist[] = $UserId;
    }


    public function RemoveUserIdFromWhitelist(int $UserId)
    {
        $Find = array_search($UserId, $this->Whitelist);
        if($Find !== false)
        {
            unset($this->Whitelist[$Find]);
            sort($this->Whitelist);
        }
    }



    


    static abstract public function FindAllByUserId(int $UserId = null, int $Offset = null, int $Limit = null, bool $CheckAccess = true) : array;
    

    static public function FindById(int $Id, bool $CheckAccess = true) : ?Dialog
    {
        $Find = (self::CreateClassObjs(parent::Find(DIALOG_TABLE, "dialog_id", "dialog_id = $1", [$Id], null, 1), $CheckAccess))[0];
        return empty($Find) ? null : $Find;
    }


    static abstract public function FindByDialogIdAndUserId(int $DialogId, int $UserId = null, bool $CheckAccess = true) : ?Dialog;


    static public function FindAll(string $Where = null, array $Parameters = [], int $Offset = null, int $Limit = null, string $OrderByColumn = "dialog_id", bool $CheckAccess = true) : array
    {
        return self::CreateClassObjs(parent::Find(DIALOG_TABLE, $OrderByColumn, $Where, $Parameters, $Offset, $Limit), $CheckAccess);
    }


    static public function FindAllBySearchQuery(string $Search, bool $Phone = true, bool $Login = true, bool $Name = true, bool $Messages = true, int $Offset = null, int $Limit = null, bool $CheckAccess = true) : array
    {
        $Search = trim(preg_replace("/\s/u", "<->", preg_replace("/[^()+\d\s\w-]/u", "", trim($Search))));
        if(empty($Search))
            return [];

        $Where = null;
        $Parameters = [];
        if($Phone)
        {
            $Temp = preg_replace("/^8/u", "7", preg_replace("/[^\d]/u", "", $Search));
            if(!empty($Temp))
            {
                $Parameters[] = $Temp . ":*";
                $Where .= "to_tsvector(properties->'Phone') @@ to_tsquery($" . count($Parameters) . ")";
            }
        }
            
        if($Login)
        {
            if(!empty($Where)) $Where .= " or ";

            $Parameters[] = $Search . ":*";
            $Where .= "to_tsvector(properties->'Login') @@ to_tsquery($" . count($Parameters) . ")";
        }

        if($Name)
        {
            if(!empty($Where)) $Where .= " or ";

            $Parameters[] = $Search . ":*";
            $Where .= "to_tsvector(name) @@ to_tsquery($" . count($Parameters) . ")";
        }

        // if($Messages)
        // {
        //     if(!empty($Where)) $Where .= " or ";
        //     $Where .= "(select count(*) from " . MESSAGE_TABLE . " where to_tsvector(messages) @@ to_tsquery($1))";
        // }

        if(!empty($Where))
            return self::CreateClassObjs(parent::Find(DIALOG_TABLE, "dialog_id", $Where, $Parameters, $Offset, $Limit), $CheckAccess);
        else
            return [];
    }


    /**
     * @return array Dialog Classes
     */
    static public function CreateClassObjs(array $Obj, bool $CheckAccess = true) : array
    {
        $Out = [];
        foreach($Obj as $TempObj)
        {
            try
            {
                $Class = $TempObj->type;
                if(!class_exists($Class))
                    throw new Exception("Class invalid");
                if(is_a(new $Class, "Dialog"))
                    throw new Exception("Class invalid");
            
                $Out[] =    new $Class(
                    $TempObj->dialog_id,
                    $TempObj->type,
                    $TempObj->name,
                    $TempObj->avatar,
                    $TempObj->is_active,
                    $TempObj->is_online,
                    $TempObj->folder_id,
                    json_decode($TempObj->properties, true),
                    json_decode($TempObj->tags, true),
                    json_decode($TempObj->whitelist, true),
                    strtotime($TempObj->created_at),
                    strtotime($TempObj->updated_at),
                    $CheckAccess
                );
            }
            catch(Exception $error){}
        }
        return $Out;
    }
}