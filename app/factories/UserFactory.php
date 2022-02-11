<?php


namespace factories;


use models\User;


class UserFactory
{
    static public function GetSystemUser() : User
    {
        return new User(-1, "System", "", null, null, null, true, [], User::USER_TYPE_SYSTEM);
    }


    static public function GetVenomUser() : User
    {
        return new User(-2, "Venom", "", null, null, null, true, [], User::USER_TYPE_SYSTEM);
    }


    static public function GetInstagramUser() : User
    {
        return new User(-3, "Instagram", "", null, null, null, true, [], User::USER_TYPE_SYSTEM);
    }


    static public function GetInstagramApiUser() : User
    {
        return new User(-4, "InstagramApi", "", null, null, null, true, [], User::USER_TYPE_SYSTEM);
    }
}
