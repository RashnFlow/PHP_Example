<?php


namespace models;


use Exception;
use exceptions\NotEnoughPermissionsException;
use models\dialogues\Dialog;
use models\dialogues\InstagramApiDialog;
use models\dialogues\InstagramDialog;
use models\dialogues\LocalDialog;
use models\dialogues\WhatsappDialog;
use views\PrintJson;

class Authorization
{
    /**
     * @param string|array  $AccessLevel
     */
    static public function CheckAccess(string $UserType, $AccessLevel, User $User = null, object $Model = null) : bool
    {
        try
        {
            if($UserType == User::USER_TYPE_SYSTEM) return true;
            if($Model instanceof Whatsapp || $Model instanceof Instagram || $Model instanceof Dialog)
            {
                if($Model instanceof Whatsapp)
                {
                    $Counter = Counter::CountWhatsapps($User->GetId());
                    if($User->GetPermissionVal($AccessLevel) == 0 && $Counter > 0 && $AccessLevel == "GetWhatsapp")
                        return true;
                }

                if($Model instanceof Instagram) 
                {
                    $Counter = Counter::CountInstagrams($User->GetId());
                    if($User->GetPermissionVal($AccessLevel) == 0 && $Counter > 0 && $AccessLevel == "GetInstagram")
                        return true;
                }
                if($Model instanceof Dialog)    $Counter = Counter::CountLocalDialogues($User->GetId());
                if($User->GetPermissionVal($AccessLevel) > 0)
                {
                    if($User->GetPermissionVal($AccessLevel) > $Counter || !$Model->IsNew() || $User->GetPermissionVal($AccessLevel) == -1)
                        return true;
                    else
                        throw new NotEnoughPermissionsException();
                }
            }
            if(empty($GLOBALS["AuthorizationCfg"])) $GLOBALS["AuthorizationCfg"] = [];

            if(!is_array($AccessLevel)) $AccessLevel = [$AccessLevel];

            foreach($AccessLevel as $obj)
            {
                $Access = false;
                if(!empty($GLOBALS["AuthorizationCfg"][$UserType]["AccessLevels"][$obj]))
                    $Access = true;
                
                foreach($GLOBALS["AuthorizationCfg"][$UserType]["Extends"] as $Extends)
                {
                    if(self::CheckAccess($Extends, $obj))
                        $Access = true;
                }

                if(!$Access)
                    throw new NotEnoughPermissionsException();
            }

            return true;
        }
        catch(NotEnoughPermissionsException $error)
        {
            PrintJson::OperationError($error->getMessage(), $error->getCode());
        }
    }


    /**
     * @return true|Exception
     */
    static public function IsAccess(User $User = null, string $AccessLevel, object $Model = null) : bool
    {
        if(empty($User))
            $User = Authentication::GetAuthUser();
        if(!self::CheckAccess($User->GetUserType(), $AccessLevel, $User, $Model))
            throw new Exception("Access is denied", ACCESS_DENIED);
        else
            return true;
    }


    /**
     * @return true|Exception
     */
    static public function IsAccessDialog(User $User, int $DialogId, string $Type) : bool
    {
        if($User->GetUserType() == User::USER_TYPE_SYSTEM) return true;

        //FixDialogues
        switch($Type)
        {
            case WhatsappDialog::class:
                $Query = QueryCreator::Find(WHATSAPP_TABLE, "whatsapp_id", "user_id = $1", [$User->GetId()]);
                $PrimaryKey = "properties->'WhatsappId'";
                break;

            case InstagramDialog::class:
                $Query = QueryCreator::Find(INSTAGRAM_TABLE, "instagram_id", "user_id = $1", [$User->GetId()]);
                $PrimaryKey = "properties->'InstagramId'";
                break;

            case InstagramApiDialog::class:
                $Query = QueryCreator::Find(InstagramApi::$Table, '"' . InstagramApi::$PrimaryKey . '"', '"' . Facebook::$PrimaryKey . '" in ($1)', [QueryCreator::Find(Facebook::$Table, '"' . Facebook::$PrimaryKey . '"', '"UserId" = $1', [$User->GetId()])]);
                $PrimaryKey = "properties->'InstagramApiId'";
                break;

            case LocalDialog::class:
                if(count(QueryCreator::Find(DIALOG_TABLE, "dialog_id", "dialog_id = $1 and (properties->'UserId')::int = $2", [$DialogId, $User->GetId()], null, 1)->Run()) <= 0 &&
                    count(QueryCreator::Find(DIALOG_TABLE, "dialog_id", "dialog_id = $1 and whitelist @> $2", [$DialogId, (string)$User->GetId()], null, 1)->Run()) <= 0)
                    throw new Exception("Access is denied", ACCESS_DENIED);

                return true;
                break;

            default:
                throw new Exception("Type invalid");
                break;
        }

        if(count(QueryCreator::Find(DIALOG_TABLE, "dialog_id", "dialog_id = $1 and ($PrimaryKey)::int in ($2)", [$DialogId, $Query], null, 1)->Run()) <= 0 &&
            count(QueryCreator::Find(DIALOG_TABLE, "dialog_id", "dialog_id = $1 and whitelist @> $2", [$DialogId, (string)$User->GetId()], null, 1)->Run()) <= 0)
            throw new Exception("Access is denied", ACCESS_DENIED);

        return true;
    }


    /**
     * @return true|Exception
     */
    static public function IsAccessMassSending(User $User, int $MassSendingId) : bool
    {
        if($User->GetUserType() == User::USER_TYPE_SYSTEM) return true;
        if(count(QueryCreator::Find(MassSending::$Table, '"MassSendingId"', '"MassSendingId" = $1 and "UserId" = $2', [$MassSendingId, $User->GetId()], null, 1)->Run()) <= 0)
            throw new Exception("Access is denied", ACCESS_DENIED);

        return true;
    }


    /**
     * @return true|Exception
     */
    static public function IsAccessAutoresponder(User $User, int $AutoresponderId) : bool
    {
        if($User->GetUserType() == User::USER_TYPE_SYSTEM) return true;
        if(count(QueryCreator::Find(Autoresponder::$Table, '"AutoresponderId"', '"AutoresponderId" = $1 and "UserId" = $2', [$AutoresponderId, $User->GetId()], null, 1)->Run()) <= 0)
            throw new Exception("Access is denied", ACCESS_DENIED);

        return true;
    }


    /**
     * @return true|Exception
     */
    static public function IsAccessFacebook(User $User, int $FacebookId) : bool
    {
        if($User->GetUserType() == User::USER_TYPE_SYSTEM) return true;
        if(count(QueryCreator::Find(\models\Facebook::$Table, '"' . \models\Facebook::$PrimaryKey . '"', '"' . \models\Facebook::$PrimaryKey . '" = $1 and "UserId" = $2', [$FacebookId, $User->GetId()], null, 1)->Run()) <= 0)
            throw new Exception("Access is denied", ACCESS_DENIED);

        return true;
    }


    /**
     * @return true|Exception
     */
    static public function IsAccessInstagramApi(User $User, int $InstagramApi) : bool
    {
        if($User->GetUserType() == User::USER_TYPE_SYSTEM) return true;
        if(count(QueryCreator::Find(\models\InstagramApi::$Table, '"' . \models\InstagramApi::$PrimaryKey . '"', '"' . \models\InstagramApi::$PrimaryKey . '" = $1 and "FacebookId" in ($2)', [$InstagramApi, QueryCreator::Find(\models\Facebook::$Table, '"' . \models\Facebook::$PrimaryKey . '"', '"UserId" = $1', [$User->GetId()])], null, 1)->Run()) <= 0)
            throw new Exception("Access is denied", ACCESS_DENIED);

        return true;
    }


    /**
     * @return true|Exception
     */
    static public function IsAccessDynamicMassSending(User $User, int $DynamicMassSendingId) : bool
    {
        if($User->GetUserType() == User::USER_TYPE_SYSTEM) return true;
        if(count(QueryCreator::Find(DYNAMIC_MASS_SENDING, "dynamic_mass_sending_id", "dynamic_mass_sending_id = $1 and user_id = $2", [$DynamicMassSendingId, $User->GetId()], null, 1)->Run()) <= 0)
            throw new Exception("Access is denied", ACCESS_DENIED);

        return true;
    }


    /**
     * @return true|Exception
     */
    static public function IsAccessBitrixIntegration(User $User, int $BitrixIntegrationId) : bool
    {
        if($User->GetUserType() == User::USER_TYPE_SYSTEM) return true;
        if(count(QueryCreator::Find(BITRIX_INTEGRATION_TABLE, "bitrix_integration_id", "bitrix_integration_id = $1 and user_id = $2", [$BitrixIntegrationId, $User->GetId()], null, 1)->Run()) <= 0)
            throw new Exception("Access is denied", ACCESS_DENIED);

        return true;
    }


    /**
     * @return true|Exception
     */
    static public function IsAccessAmoCRMIntegration(User $User, int $AmoCRMIntegrationId) : bool
    {
        if($User->GetUserType() == User::USER_TYPE_SYSTEM) return true;
        if(count(QueryCreator::Find(AMOCRM_INTEGRATION_TABLE, "amocrm_integration_id", "amocrm_integration_id = $1 and user_id = $2", [$AmoCRMIntegrationId, $User->GetId()], null, 1)->Run()) <= 0)
            throw new Exception("Access is denied", ACCESS_DENIED);

        return true;
    }

    /**
     * @return true|Exception
     */
    static public function IsAccessMegaplanIntegration(User $User, int $MegaplanIntegrationId) : bool
    {
        if($User->GetUserType() == User::USER_TYPE_SYSTEM) return true;
        if(count(QueryCreator::Find(MEGAPLAN_INTEGRATION_TABLE, "megaplan_integration_id", "megaplan_integration_id = $1 and user_id = $2", [$MegaplanIntegrationId, $User->GetId()], null, 1)->Run()) <= 0)
            throw new Exception("Access is denied", ACCESS_DENIED);

        return true;
    }

    /**
     * @return true|Exception
     */
    static public function IsAccessYclientsIntegration(User $User, int $YclientsIntegrationId) : bool
    {
        if($User->GetUserType() == User::USER_TYPE_SYSTEM) return true;
        if(count(QueryCreator::Find(YCLIENTS_INTEGRATION_TABLE, "yclients_integration_id", "yclients_integration_id = $1 and user_id = $2", [$YclientsIntegrationId, $User->GetId()], null, 1)->Run()) <= 0)
            throw new Exception("Access is denied", ACCESS_DENIED);

        return true;
    }

    /**
     * @return true|Exception
     */
    static public function IsAccessYclientsTasks(User $User, int $TaskId) : bool
    {
        if($User->GetUserType() == User::USER_TYPE_SYSTEM) return true;
        if(count(QueryCreator::Find(YCLIENTS_INTEGRATION_TASK_TABLE, "task_id", "task_id = $1 and yclients_integration_id in ($2)", [$TaskId, QueryCreator::Find(YCLIENTS_INTEGRATION_TABLE, "yclients_integration_id", "user_id = $1", [$User->GetId()])], null, 1)->Run()) <= 0)
            throw new Exception("Access is denied", ACCESS_DENIED);

        return true;
    }


    /**
     * @return true|Exception
     */
    static public function IsAccessDynamicResource(User $User, int $DynamicResourceId) : bool
    {
        if($User->GetUserType() == User::USER_TYPE_SYSTEM) return true;
        if(count(QueryCreator::Find(DYNAMIC_RESOURCE_TABLE, "dynamic_resource_id", "dynamic_resource_id = $1 and user_id = $2", [$DynamicResourceId, $User->GetId()], null, 1)->Run()) <= 0)
            throw new Exception("Access is denied", ACCESS_DENIED);

        return true;
    }


    /**
     * @return true|Exception
     */
    static public function IsAccessWhatsapp(User $User, int $WhatsappId) : bool
    {
        if($User->GetUserType() == User::USER_TYPE_SYSTEM) return true;
        if(count(QueryCreator::Find(WHATSAPP_TABLE, "whatsapp_id", "whatsapp_id = $1 and user_id = $2", [$WhatsappId, $User->GetId()], null, 1)->Run()) <= 0)
            throw new Exception("Access is denied", ACCESS_DENIED);

        return true;
    }


    /**
     * @return true|Exception
     */
    static public function IsAccessFolder(User $User, int $FolderId) : bool
    {
        if($User->GetUserType() == User::USER_TYPE_SYSTEM) return true;
        if(count(QueryCreator::Find(FOLDER_TABLE, "folder_id", "folder_id = $1 and user_id = $2", [$FolderId, $User->GetId()], null, 1)->Run()) <= 0)
            throw new Exception("Access is denied", ACCESS_DENIED);

        return true;
    }


    /**
     * @return true|Exception
     */
    static public function IsAccessInstagram(User $User, int $InstagramId) : bool
    {
        if($User->GetUserType() == User::USER_TYPE_SYSTEM) return true;
        if(count(QueryCreator::Find(INSTAGRAM_TABLE, "instagram_id", "instagram_id = $1 and user_id = $2", [$InstagramId, $User->GetId()], null, 1)->Run()) <= 0)
            throw new Exception("Access is denied", ACCESS_DENIED);

        return true;
    }


    /**
     * @return true|Exception
     */
    static public function IsAccessApiToken(User $User, int $ApiTokenId) : bool
    {
        if($User->GetUserType() == User::USER_TYPE_SYSTEM) return true;
        if(count(QueryCreator::Find(API_TOKEN_TABLE, "api_token_id", "api_token_id = $1 and user_id = $2", [$ApiTokenId, $User->GetId()], null, 1)->Run()) <= 0)
            throw new Exception("Access is denied", ACCESS_DENIED);

        return true;
    }


    static public function SetAccesses(string $UserType, array $AccessLevels, array $Extends = [])
    {
        if(empty($GLOBALS["AuthorizationCfg"])) $GLOBALS["AuthorizationCfg"] = [];

        $GLOBALS["AuthorizationCfg"][$UserType]["AccessLevels"] = $AccessLevels;
        $GLOBALS["AuthorizationCfg"][$UserType]["Extends"]      = $Extends;
    }


    static public function GetAccessVal(string $UserType, string $AccessLevel) : int
    {
        if(empty($GLOBALS["AuthorizationCfg"])) $GLOBALS["AuthorizationCfg"] = [];

        return empty((int)$GLOBALS["AuthorizationCfg"][$UserType]["AccessLevels"][$AccessLevel]) ? 0 : (int)$GLOBALS["AuthorizationCfg"][$UserType]["AccessLevels"][$AccessLevel];
    }
}