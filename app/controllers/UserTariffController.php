<?php


namespace controllers;

use classes\Validator;
use DateTime;
use Exception;
use models\Authentication;
use models\InstagramTariff;
use models\SalesTariff;
use models\Tariff;
use models\User;
use models\UserTariff;
use models\WhatsAppTariff;
use views\PrintJson;

class UserTariffController
{
    public function ActionCreateUserTariff(array $Parameters)
    {
        if(Validator::IsValid($Parameters["Post"], [
            ["Key" => "sale_id"],
            ["Key" => "parameters", "Type" => "array"]
        ]))
            {
                if(Validator::IsValid($Parameters["Post"]["parameters"][0], [
                    ["Key" => "whatsapp", "Type" => "int", "IntMin" => 0],
                    ["Key" => "instagram", "Type" => "int", "IntMin" => 0]
                ]))
                {
                $Sales = SalesTariff::FindById($Parameters["Post"]["sale_id"]);
                if ($Parameters["Post"]["parameters"][0]["whatsapp"] > 0)
                {
                    $Tariff = Tariff::FindById(1);
                    $WhatsAppTariff = new WhatsAppTariff();
                    if (empty($Sales))
                        return;
                    $WhatsAppTariff->SaleId = $Sales->SaleId;

                    $PriceWithoutSale = ($Parameters["Post"]["parameters"][0]["whatsapp"] * $Tariff->PriceForMonth) * $Sales->Month;
                    $Sale  = $PriceWithoutSale * ($Sales->Sale / 100);
                    $Price = $PriceWithoutSale - $Sale;
                    $WhatsAppTariff->EndDate = 0;

                    $WhatsAppTariff->Price = $Price;
                    $WhatsAppTariff->Status = "waiting";
                    $WhatsAppTariff->UserId = Authentication::GetAuthUser()->GetId();
                    $WhatsAppTariff->OldPrice = 0;
                    $WhatsAppTariff->AllPrice = 0;
                    $WhatsAppTariff->Access = [
                        "GetWhatsapp" => $Parameters["Post"]["parameters"][0]["whatsapp"],
                        "SetWhatsapp" => $Parameters["Post"]["parameters"][0]["whatsapp"],
                    ];
                    $WhatsAppTariff->Save();
                }
                if ($Parameters["Post"]["parameters"][0]["instagram"] > 0)
                {
                    $Tariff = Tariff::FindById(2);
                    $InstagramTariff = new InstagramTariff();
                    if (empty($Sales))
                        return;
                    $InstagramTariff->SaleId = $Sales->SaleId;

                    $PriceWithoutSale = ($Parameters["Post"]["parameters"][0]["instagram"] * $Tariff->PriceForMonth) * $Sales->Month;
                    $Sale  = $PriceWithoutSale * ($Sales->Sale / 100);
                    $Price = $PriceWithoutSale - $Sale;
                    $InstagramTariff->EndDate = 0;

                    $InstagramTariff->Price = $Price;
                    $InstagramTariff->Status = "waiting";
                    $InstagramTariff->UserId = Authentication::GetAuthUser()->GetId();
                    $InstagramTariff->OldPrice = 0;
                    $InstagramTariff->AllPrice = 0;
                    $InstagramTariff->Access = [
                        "GetInstagram" => $Parameters["Post"]["parameters"][0]["instagram"],
                        "SetInstagram" => $Parameters["Post"]["parameters"][0]["instagram"],
                    ];
                    $InstagramTariff->Save();
                }

                $UserTariff = new UserTariff();
                $UserTariff->UserId = Authentication::GetAuthUser()->GetId();
                $UserTariff->WhatsAppTariffId  = (!empty($WhatsAppTariff->WhatsAppTariffId)) ? $WhatsAppTariff->WhatsAppTariffId : 0;
                $UserTariff->InstagramTariffId = (!empty($InstagramTariff->InstagramTariffId)) ? $InstagramTariff->InstagramTariffId : 0;
                $UserTariff->SaleId = $Sales->SaleId;
                $UserTariff->Price = ((!empty($WhatsAppTariff->Price)) ? $WhatsAppTariff->Price : 0) + ((!empty($InstagramTariff->Price)) ? $InstagramTariff->Price : 0);
                $UserTariff->Save();

                PrintJson::OperationSuccessful(["user_tariff_id" => $UserTariff->UserTariffId]);
            }
        }
    }


    public function ActionGetTariff(array $Parameters)
    {
        if(Validator::IsValid($Parameters["Get"], [
            ["Key" => "whatsapp_tariff_id", "IsNull" => true],
            ["Key" => "instagram_tariff_id", "IsNull" => true]
        ]))
        {
            try
            {
                if (!empty($Parameters["Get"]["whatsapp_tariff_id"]) && !empty($WhatsAppTariff = WhatsAppTariff::FindById($Parameters["Get"]["whatsapp_tariff_id"])))
                {
                    $Time = 0;
                    $AllTime = 0;
                    if($WhatsAppTariff->Status == "success")
                    {
                        $EndDate = DateTime::createFromFormat('U', $WhatsAppTariff->EndDate);
                        $CurrentDate = DateTime::createFromFormat('U', time());
                        $PayDate = DateTime::createFromFormat('U', $WhatsAppTariff->PayDate);
                        $Time = $EndDate->diff($CurrentDate);
                        $AllTime = $EndDate->diff($PayDate);
                    }

                    PrintJson::OperationSuccessful([
                        "whatsapp_tariff_id"    => $WhatsAppTariff->WhatsAppTariffId,
                        "sale_id"               => $WhatsAppTariff->SaleId,
                        "old_price"             => $WhatsAppTariff->OldPrice,
                        "price"                 => $WhatsAppTariff->Price,
                        "pay_date"              => $WhatsAppTariff->PayDate,
                        "count"                 => $WhatsAppTariff->Access["GetWhatsapp"],
                        "remainig_days"         => ($Time != 0) ? $Time->days + 1 : 0,
                        "all_days"              => ($AllTime != 0) ? $AllTime->days + 1 : 0
                    ]);
                }
                else if(!empty($Parameters["Get"]["instagram_tariff_id"]) && !empty($InstagramTariff = InstagramTariff::FindById($Parameters["Get"]["instagram_tariff_id"])))
                {
                    $Time = 0;
                    $AllTime = 0;
                    if($InstagramTariff->Status == "success")
                    {
                        $EndDate = DateTime::createFromFormat('U', $InstagramTariff->EndDate);
                        $CurrentDate = DateTime::createFromFormat('U', time());
                        $PayDate = DateTime::createFromFormat('U', $InstagramTariff->PayDate);
                        $Time = $EndDate->diff($CurrentDate);
                        $AllTime = $EndDate->diff($PayDate);
                    }
                    PrintJson::OperationSuccessful([
                        "instagram_tariff_id"   => $InstagramTariff->InstagramTariffId,
                        "sale_id"               => $InstagramTariff->SaleId,
                        "old_price"             => $InstagramTariff->OldPrice,
                        "price"                 => $InstagramTariff->Price,
                        "pay_date"              => $InstagramTariff->PayDate,
                        "count"                 => $InstagramTariff->Access["GetInstagram"],
                        "remainig_days"         => ($Time != 0) ? $Time->days + 1 : 0,
                        "all_days"              => ($AllTime != 0) ? $AllTime->days + 1 : 0
                    ]);
                }
                else
                    PrintJson::OperationError(TariffNotFound, NOT_FOUND);
            }
            catch(Exception $e) {}
        }
    }


    public function ActionGetAllUserTariff(array $Parameters)
    {
        if (!empty($UserTariffs = UserTariff::FindAllByUserId(Authentication::GetAuthUser()->GetId())))
        {
            $Out = [];
            foreach ($UserTariffs as $UserTariff)
            {
                $Time = 0;
                if(!empty($UserTariff->WhatsAppTariffId) && !empty($WhatsAppTariff = WhatsAppTariff::FindById($UserTariff->WhatsAppTariffId)))
                {
                    if($WhatsAppTariff->Status == "success")
                    {
                        $EndDate = DateTime::createFromFormat('U', $WhatsAppTariff->EndDate);
                        $CurrentDate = DateTime::createFromFormat('U', time());
                        $Time = $EndDate->diff($CurrentDate, true);
                    }
                    $Out["user_tariff"][] = [
                        "whatsapp_tariff_id"        => $WhatsAppTariff->WhatsAppTariffId,
                        "sale_id"                   => $WhatsAppTariff->SaleId,
                        "price"                     => $WhatsAppTariff->Price,
                        "old_price"                 => $WhatsAppTariff->OldPrice,
                        "all_price"                 => $WhatsAppTariff->AllPrice,
                        "end_date"                  => $WhatsAppTariff->EndDate,
                        "status"                    => $WhatsAppTariff->Status,
                        "paydate"                   => $WhatsAppTariff->PayDate,
                        "count"                     => $WhatsAppTariff->Access["GetWhatsapp"],
                        "remaining_days"            => $Time->days
                    ];
                }
                if(!empty($UserTariff->InstagramTariffId && !empty($InstagramTariff = InstagramTariff::FindById($UserTariff->InstagramTariffId))))
                {
                    if($InstagramTariff->Status == "success")
                    {
                        $EndDate = DateTime::createFromFormat('U', $InstagramTariff->EndDate);
                        $CurrentDate = DateTime::createFromFormat('U', time());
                        $Time = $EndDate->diff($CurrentDate, true);
                    }
                    $Out["user_tariff"][] = [
                        "instagram_tariff_id"       => $InstagramTariff->InstagramTariffId,
                        "sale_id"                   => $InstagramTariff->SaleId,
                        "price"                     => $InstagramTariff->Price,
                        "old_price"                 => $InstagramTariff->OldPrice,
                        "all_price"                 => $InstagramTariff->AllPrice,
                        "end_date"                  => $InstagramTariff->EndDate,
                        "status"                    => $InstagramTariff->Status,
                        "paydate"                   => $InstagramTariff->PayDate,
                        "count"                     => $InstagramTariff->Access["GetInstagram"],
                        "remaining_days"            => $Time->days
                    ];
                }
            }
            PrintJson::OperationSuccessful($Out);
        }
        else
            PrintJson::OperationError(TariffNotFound, NOT_FOUND);
    }


    public function ActionUpdateTariff(array $Parameters)
    {
        if(Validator::IsValid($Parameters["Post"], [
            ["Key" => "whatsapp_tariff_id", "IsNull" => true],
            ["Key" => "instagram_tariff_id", "IsNull" => true],
            ["Key" => "sale_id"]
        ]))
        {
            if(!empty($Parameters["Post"]["whatsapp_tariff_id"]) && !empty($WhatsAppTariff = WhatsAppTariff::FindById($Parameters["Post"]["whatsapp_tariff_id"])))
            {
                if($WhatsAppTariff->SaleId == $Parameters["Post"]["sale_id"])
                {
                    PrintJson::OperationSuccessful();
                    return;
                }
                $Tariff = Tariff::FindById(1);
                $Sales = SalesTariff::FindById($Parameters["Post"]["sale_id"]);
                $PriceWithoutSale = ($WhatsAppTariff->Access["GetWhatsapp"] * $Tariff->PriceForMonth) * $Sales->Month;
                $Sale  = $PriceWithoutSale * ($Sales->Sale / 100);
                $Price = $PriceWithoutSale - $Sale;
                $WhatsAppTariff->Price = $Price;
                $WhatsAppTariff->SaleId = $Sales->SaleId;
                $WhatsAppTariff->Save();
                PrintJson::OperationSuccessful();

            }
            else if(!empty($Parameters["Post"]["instagram_tariff_id"]) && !empty($InstagramTariff = InstagramTariff::FindById($Parameters["Post"]["instagram_tariff_id"])))
            {
                if($InstagramTariff->SaleId == $Parameters["Post"]["sale_id"])
                {
                    PrintJson::OperationSuccessful();
                    return;
                }
                $Tariff = Tariff::FindById(2);
                $Sales = SalesTariff::FindById($Parameters["Post"]["sale_id"]);
                $PriceWithoutSale = ($InstagramTariff->Access["GetInstagram"] * $Tariff->PriceForMonth) * $Sales->Month;
                $Sale  = $PriceWithoutSale * ($Sales->Sale / 100);
                $Price = $PriceWithoutSale - $Sale;
                $InstagramTariff->Price = $Price;
                $InstagramTariff->SaleId = $Sales->SaleId;
                $InstagramTariff->Save();
                PrintJson::OperationSuccessful();
            }
        }
    }


    public function ActionUpdateTariffAfterPay(array $Parameters)
    {
        if(Validator::IsValid($Parameters["Post"], [
            ["Key" => "whatsapp_tariff_id", "IsNull" => true],
            ["Key" => "instagram_tariff_id", "IsNull" => true],
            ["Key" => "sale_id"]
        ]))
        {
            if(!empty($Parameters["Post"]["whatsapp_tariff_id"]) && !empty($WhatsAppTariff = WhatsAppTariff::FindById($Parameters["Post"]["whatsapp_tariff_id"])))
            {
                $Tariff = Tariff::FindById(1);
                $Sales = SalesTariff::FindById($Parameters["Post"]["sale_id"]);
                $PriceWithoutSale = ($WhatsAppTariff->Access["GetWhatsapp"] * $Tariff->PriceForMonth) * $Sales->Month;
                $Sale  = $PriceWithoutSale * ($Sales->Sale / 100);
                $Price = $PriceWithoutSale - $Sale;
                $WhatsAppTariff->Price = $Price;
                $WhatsAppTariff->AllPrice += $WhatsAppTariff->OldPrice;
                $WhatsAppTariff->OldPrice = 0;
                $WhatsAppTariff->SaleId = $Sales->SaleId;
                $WhatsAppTariff->Save();
                PrintJson::OperationSuccessful();
            }
            else if(!empty($Parameters["Post"]["instagram_tariff_id"]) && !empty($InstagramTariff = InstagramTariff::FindById($Parameters["Post"]["instagram_tariff_id"])))
            {
                $Tariff = Tariff::FindById(2);
                $Sales = SalesTariff::FindById($Parameters["Post"]["sale_id"]);
                $PriceWithoutSale = ($InstagramTariff->Access["GetInstagram"] * $Tariff->PriceForMonth) * $Sales->Month;
                $Sale  = $PriceWithoutSale * ($Sales->Sale / 100);
                $Price = $PriceWithoutSale - $Sale;
                $InstagramTariff->Price = $Price;
                $InstagramTariff->AllPrice += $InstagramTariff->OldPrice;
                $InstagramTariff->OldPrice = 0;
                $InstagramTariff->SaleId = $Sales->SaleId;
                $InstagramTariff->Save();
                PrintJson::OperationSuccessful();
            }
        }
    }


    public function ActionDeleteUserTariff(array $Parameters)
    {
        if(Validator::IsValid($Parameters["Post"], [
            ["Key" => "whatsapp_tariff_id", "IsNull" => true],
            ["Key" => "instagram_tariff_id", "IsNull" => true],
        ]))
        {
            if(!empty($Parameters["Post"]["whatsapp_tariff_id"]) && !empty($WhatsAppTariff = WhatsAppTariff::FindById($Parameters["Post"]["whatsapp_tariff_id"])))
            {
                if($WhatsAppTariff->Status == "waiting" && User::FindById($WhatsAppTariff->UserId)->GetId() == Authentication::GetAuthUser()->GetId())
                {
                    $WhatsAppTariff->Delete();
                    PrintJson::OperationSuccessful();
                }
                else
                    PrintJson::OperationError(TariffDoesNotCompleted, ACCESS_DENIED);
            }
            else if(!empty($Parameters["Post"]["instagram_tariff_id"]) && !empty($InstagramTariff = InstagramTariff::FindById($Parameters["Post"]["instagram_tariff_id"])))
            {
                if($InstagramTariff->Status == "waiting" && User::FindById($InstagramTariff->UserId)->GetId() == Authentication::GetAuthUser()->GetId())
                {
                    $InstagramTariff->Delete();
                    PrintJson::OperationSuccessful();
                }
                else
                    PrintJson::OperationError(TariffDoesNotCompleted, ACCESS_DENIED);
            }
            else
                PrintJson::OperationError(TariffNotFound, NOT_FOUND);
        }
    }
}