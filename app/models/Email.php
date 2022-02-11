<?php


namespace models;


use classes\Tools;


class Email extends CRUD
{
    private ?int     $Id                = null;
    private ?string  $Email             = null;
    private ?string  $Uid               = null;
    private int      $ClicksCount       = 0;
    private int      $OpensCount        = 0;
    private int      $SentsCount        = 0;
    private int      $SpamsCount        = 0;
    private int      $FailedSentsCount  = 0;
    private bool     $IsSend            = true;


    /**
     * Do not send parameters when calling the constructor.
     * They are needed for the methods "Find". Instead, use
     * the "Set..()" methods to set values and "Get..()" to read them.
     */
    public function __construct(
        int     $Id                = null,
        string  $Email             = null,
        string  $Uid               = null,
        int     $ClicksCount       = 0,
        int     $OpensCount        = 0,
        int     $SentsCount        = 0,
        int     $SpamsCount        = 0,
        int     $FailedSentsCount  = 0,
        bool    $IsSend            = true,
        bool    $CheckAccess       = true)
    {
        if($CheckAccess) Authorization::IsAccess(Authentication::GetAuthUser(), "GetEmail");
        if(isset($Id))
        {
            $this->Id               = $Id;
            $this->Email            = $Email;
            $this->Uid              = $Uid;
            $this->ClicksCount      = $ClicksCount;
            $this->OpensCount       = $OpensCount;
            $this->SentsCount       = $SentsCount;
            $this->SpamsCount       = $SpamsCount;
            $this->FailedSentsCount = $FailedSentsCount;
            $this->IsSend           = $IsSend;
        }
    }

    /**
     * Creates or updates
     */
    public function Save(bool $CheckAccess = true) : int
    {
        if($CheckAccess) Authorization::IsAccess(Authentication::GetAuthUser(), "SetEmail");

        if(empty($this->Uid))
            $this->Uid = $this->GenerateUid(40);

        return $this->Id =
        parent::Save(   EMAIL_TABLE,
                        "email_id",
                        $this->Id,
                        [
                            'clicks_count',
                            'opens_count',
                            'sents_count',
                            'spams_count',
                            'failed_sents_count',
                            'is_send',
                            'email',
                            'email_uid'
                        ],
                        "$1,$2,$3,$4,$5,$6,$7,$8",
                        [
                            $this->ClicksCount,
                            $this->OpensCount,
                            $this->SentsCount,
                            $this->SpamsCount,
                            $this->FailedSentsCount,
                            $this->IsSend,
                            $this->Email,
                            $this->Uid
                        ]
                    );
    }


    /**
     * Removes
     */
    public function Delete(bool $CheckAccess = true)
    {
        if($CheckAccess) Authorization::IsAccess(Authentication::GetAuthUser(), "SetEmail");
        parent::Delete(EMAIL_TABLE, "email_id", $this->Id);
        $this->Id = null;
    }


    public function GetId() : ?int
    {
        return $this->Id;
    }


    public function GetEmail() : ?string
    {
        return $this->Email;
    }


    public function GetUid() : ?string
    {
        return $this->Uid;
    }


    public function GetClicksCount() : int
    {
        return $this->ClicksCount;
    }


    public function GetOpensCount() : int
    {
        return $this->OpensCount;
    }


    public function GetSentsCount() : int
    {
        return $this->SentsCount;
    }


    public function GetSpamsCount() : int
    {
        return $this->SpamsCount;
    }


    public function GetFailedSentsCount() : int
    {
        return $this->FailedSentsCount;
    }


    public function GetIsSend() : bool
    {
        return $this->IsSend;
    }


    public function FailedSent()
    {
        $this->FailedSentsCount++;
    }


    public function Spam()
    {
        $this->SpamsCount++;
    }


    public function Sent()
    {
        $this->SentsCount++;
    }


    public function Opened()
    {
        $this->OpensCount++;
    }


    public function Clicked()
    {
        $this->ClicksCount++;
    }


    public function SetIsSend(bool $IsSend)
    {
        $this->IsSend = $IsSend;
    }


    public function SetEmail(string $Email)
    {
        $this->Email = $Email;
    }


    private function GenerateUid(int $Length = 20) : string
    {
        while(true)
        {
            $Uid = Tools::GenerateString($Length);

            if(!self::FindByUid($Uid))
                return $Uid;
        }
    }



    static public function FindById(int $EmailId, bool $CheckAccess = true) : ?Email
    {
        $Find = (self::CreateClassObjs(parent::Find(EMAIL_TABLE, "email_id", "email_id = $1", [$EmailId], null, 1), $CheckAccess))[0];
        return empty($Find) ? null : $Find;
    }


    static public function FindByUid(string $EmailUid, bool $CheckAccess = true) : ?Email
    {
        $Find = (self::CreateClassObjs(parent::Find(EMAIL_TABLE, "email_id", "email_uid = $1", [$EmailUid], null, 1), $CheckAccess))[0];
        return empty($Find) ? null : $Find;
    }


    static public function FindByEmail(string $Email, bool $CheckAccess = true) : ?Email
    {
        $Find = (self::CreateClassObjs(parent::Find(EMAIL_TABLE, "email_id", "email = $1", [$Email], null, 1), $CheckAccess))[0];
        return empty($Find) ? null : $Find;
    }
    
    
    static public function FindAll(string $Where = null, array $Parameters = [], int $Offset = null, int $Limit = null, bool $CheckAccess = true) : array
    {
        return self::CreateClassObjs(parent::Find(EMAIL_TABLE, "email_id", $Where, $Parameters, $Offset, $Limit), $CheckAccess);
    }


    /**
     * @return array Email Classes
     */
    static public function CreateClassObjs(array $Obj, $CheckAccess = true) : array
    {
        $Out = [];
        foreach($Obj as $TempObj)
        {
            $Out[] =    new Email(
                $TempObj->email_id,
                $TempObj->email,
                $TempObj->email_uid,
                $TempObj->clicks_count,
                $TempObj->opens_count,
                $TempObj->sents_count,
                $TempObj->spams_count,
                $TempObj->failed_sents_count,
                $TempObj->is_send,
                $CheckAccess
            );
        }
        return $Out;
    }
}