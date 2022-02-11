<?php


namespace models;


use classes\Tools;


class ApiToken extends CRUD
{
    private ?int     $Id           = null;
    private ?string  $Token        = null;
    private ?int     $UserId       = null;
    private ?int     $CreatedAt    = null;
    private ?int     $UpdatedAt    = null;




    /**
     * Do not send parameters when calling the constructor.
     * They are needed for the methods "Find". Instead, use
     * the "Set..()" methods to set values and "Get..()" to read them.
     */
    public function __construct(
        int     $Id             = null,
        string  $Token          = null,
        int     $UserId         = null,
        int     $CreatedAt      = null,
        int     $UpdatedAt      = null,
        bool    $CheckAccess    = true
    )
    {
        if($CheckAccess) Authorization::IsAccess(Authentication::GetAuthUser(), "GetApiToken");
        if(isset($Id))
        {
            if($CheckAccess)
                Authorization::IsAccessApiToken(Authentication::GetAuthUser(), $Id);

            $this->Id           = $Id;
            $this->Token        = $Token;
            $this->UserId       = $UserId;
            $this->CreatedAt    = $CreatedAt;
            $this->UpdatedAt    = $UpdatedAt;
        }
    }


    public function Save(bool $CheckAccess = true) : int
    {
        if($CheckAccess) Authorization::IsAccess(Authentication::GetAuthUser(), "SetApiToken");
        return $this->Id =
        parent::Save(   API_TOKEN_TABLE,
                        "api_token_id",
                        $this->Id,
                        [
                            'token',
                            'user_id'
                        ],
                        "$1,$2",
                        [
                            $this->Token,
                            $this->UserId
                        ],
                        true
                    );
    }


    public function Delete(bool $CheckAccess = true)
    {
        if($CheckAccess) Authorization::IsAccess(Authentication::GetAuthUser(), "SetApiToken");
        parent::Delete(API_TOKEN_TABLE, "api_token_id", $this->Id);
        $this->Id = null;
    }


    public function GetUpdatedAt() : ?int
    {
        return $this->UpdatedAt;
    }


    public function GetCreatedAt() : ?int
    {
        return $this->CreatedAt;
    }

    public function GetToken() : ?string
    {
        return $this->Token;
    }

    public function GetUserId() : ?int
    {
        return $this->UserId;
    }


    public function GenerateToken()
    {
        while(true)
        {
            $Token = Tools::GenerateString(100);
            if(empty(self::FindByToken($Token)))
            {
                $this->Token = $Token;
                break;
            }
        }
    }


    public function SetUserId(int $UserId)
    {
        $this->UserId = $UserId;
    }





    static public function FindById(int $ApiTokenId, bool $CheckAccess = true) : ?ApiToken
    {
        $Find = (self::CreateClassObjs(parent::Find(API_TOKEN_TABLE, "api_token_id", "api_token_id = $1", [$ApiTokenId], null, 1), $CheckAccess))[0];
        return empty($Find) ? null : $Find;
    }


    static public function FindByToken(string $Token, bool $CheckAccess = true) : ?ApiToken
    {
        $Find = (self::CreateClassObjs(parent::Find(API_TOKEN_TABLE, "api_token_id", "token = $1", [$Token], null, 1), $CheckAccess))[0];
        return empty($Find) ? null : $Find;
    }


    static public function FindAll(string $Where = null, array $Parameters = [], int $Offset = null, int $Limit = null, bool $CheckAccess = true) : array
    {
        return self::CreateClassObjs(parent::Find(API_TOKEN_TABLE, "api_token_id", $Where, $Parameters, $Offset, $Limit), $CheckAccess);
    }

    static public function FindAllByUserId(int $UserId = null, bool $CheckAccess = true) : array
    {
        if(empty($UserId)) $UserId = Authentication::GetAuthUser()->GetId();
        return self::CreateClassObjs(parent::Find(API_TOKEN_TABLE, "api_token_id", "user_id = $1", [$UserId]), $CheckAccess);
    }


    /**
     * @return array ApiToken Classes
     */
    static public function CreateClassObjs(array $Obj, $CheckAccess = true) : array
    {
        $Out = [];
        foreach($Obj as $TempObj)
        {
            $Out[] =    new ApiToken(
                $TempObj->api_token_id,
                $TempObj->token,
                $TempObj->user_id,
                strtotime($TempObj->created_at),
                strtotime($TempObj->updated_at),
                $CheckAccess
            );
        }
        return $Out;
    }
}