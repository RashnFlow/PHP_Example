<?php


namespace models;


class Counter 
{
    static public function CountWhatsapps(int $UserId = null) : int
    {
        if(empty($UserId)) $UserId = Authentication::GetAuthUser()->GetId();
        return (int)QueryCreator::Count(WHATSAPP_TABLE, "user_id = $1", [$UserId])->Run()[0]->count;
    }


    static public function CountNotBannedWhatsapps(int $UserId = null) : int
    {
        if(empty($UserId)) $UserId = Authentication::GetAuthUser()->GetId();
        return (int)QueryCreator::Count(WHATSAPP_TABLE, "user_id = $1 and is_banned = $2", [$UserId, false])->Run()[0]->count;
    }


    static public function CountInstagrams(int $UserId = null) : int
    {
        if(empty($UserId)) $UserId = Authentication::GetAuthUser()->GetId();
        return (int)QueryCreator::Count(INSTAGRAM_TABLE, "user_id = $1", [$UserId])->Run()[0]->count;
    }


    static public function CountNotBannedInstagrams(int $UserId = null) : int
    {
        if(empty($UserId)) $UserId = Authentication::GetAuthUser()->GetId();
        return (int)QueryCreator::Count(INSTAGRAM_TABLE, "user_id = $1 and is_banned = $2", [$UserId, false])->Run()[0]->count;
    }


    static public function CountDialoguesPerMonth(int $UserId = null) : int
    {
        if(empty($UserId)) $UserId = Authentication::GetAuthUser()->GetId();
        
        $Count = 0;
        $Count += (int)QueryCreator::Count(DIALOG_TABLE, "(properties->'WhatsappId')::int in ($1) and created_at > $2", [QueryCreator::Find(WHATSAPP_TABLE, "whatsapp_id", "user_id = $1", [$UserId]), date("Y-m-01")])->Run()[0]->count;
        $Count += (int)QueryCreator::Count(DIALOG_TABLE, "(properties->'InstagramId')::int in ($1) and created_at > $2", [QueryCreator::Find(INSTAGRAM_TABLE, "instagram_id", "user_id = $1", [$UserId]), date("Y-m-01")])->Run()[0]->count;
        $Count += (int)QueryCreator::Count(DIALOG_TABLE, "(properties->'UserId')::int = $1 and created_at > $2", [$UserId, date("Y-m-01")])->Run()[0]->count;
        return $Count;
    }


    static public function CountWhatsappDialogues(int $UserId = null) : int
    {
        if(empty($UserId)) $UserId = Authentication::GetAuthUser()->GetId();
        return (int)QueryCreator::Count(DIALOG_TABLE, "(properties->'WhatsappId')::int in ($1)", [QueryCreator::Find(WHATSAPP_TABLE, "whatsapp_id", "user_id = $1", [$UserId])])->Run()[0]->count;
    }


    static public function CountInstagramDialogues(int $UserId = null) : int
    {
        if(empty($UserId)) $UserId = Authentication::GetAuthUser()->GetId();
        return (int)QueryCreator::Count(DIALOG_TABLE, "(properties->'InstagramId')::int in ($1)", [QueryCreator::Find(INSTAGRAM_TABLE, "instagram_id", "user_id = $1", [$UserId])])->Run()[0]->count;
    }


    static public function CountLocalDialogues(int $UserId = null) : int
    {
        if(empty($UserId)) $UserId = Authentication::GetAuthUser()->GetId();
        return (int)QueryCreator::Count(DIALOG_TABLE, "(properties->'UserId')::int = $1", [$UserId])->Run()[0]->count;
    }
}