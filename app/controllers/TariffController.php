<?php


namespace controllers;

use classes\Tag;
use classes\Validator;
use models\SalesTariff;
use models\Tariff;
use views\PrintJson;

class TariffController
{
    public function ActionAddTariff(array $Parameters)
    {
        if(Validator::IsValid($Parameters["Post"], [
            ["Key" => "name"],
            ["Key" => "price_for_month"],
            ["Key" => "parameters", "Type" => "array"]
        ]))
        {
            if(empty($Tariff = Tariff::FindByName($Parameters["Post"]["name"])))
            {
                $Tariff = new Tariff();
                $Tariff->Name = $Parameters["Post"]["name"];
                if($Parameters["Post"]["price_for_month"] > 0)
                    $Tariff->PriceForMonth = (double)$Parameters["Post"]["price_for_month"];
                $Tariff->Parameters = $Parameters["Post"]["parameters"];
                $Tariff->Save();
                PrintJson::OperationSuccessful();
            }
            else
                PrintJson::OperationError(TariffIsExist, REQUEST_FAILED);
        }
    }


    public function ActionUpdateTariff(array $Parameters)
    {
        if(Validator::IsValid($Parameters["Post"], [
            ["Key" => "tariff_id", "Type" => "int"],
            ["Key" => "price_for_month"],
            ["Key" => "parameters", "Type" => "array"]
        ]))
        {
            if(!empty($Tariff = Tariff::FindById($Parameters["Post"]["tariff_id"])))
            {
                $Tariff->Parameters = $Parameters["Post"]["parameters"];
                $Tariff->PriceForMonth = $Parameters["Post"]["price_for_month"];
                $Tariff->Save();
                PrintJson::OperationSuccessful();
            }
            else
                PrintJson::OperationError(TariffNotFound, REQUEST_FAILED);
        }
    }

    public function ActionGetTariff(array $Parameters)
    {
        if(Validator::IsValid($Parameters["Post"], [
            ["Key" => "tariff_id", "Type" => "int"],
        ]))
        {
            if(!empty($Tariff = Tariff::FindById($Parameters["Post"]["tariff_id"])))
            {
                $Out = ["name" => $Tariff->Name, "price_for_month" => $Tariff->PriceForMonth, "parameters" => $Tariff->Parameters];
                PrintJson::OperationSuccessful($Out); 
            }
            else
                PrintJson::OperationError(TariffNotFound, REQUEST_FAILED);
        }
    }

    public function ActionDeleteTariff(array $Parameters)
    {
        if(Validator::IsValid($Parameters["Post"], [
            ["Key" => "tariff_id", "Type" => "int"],
        ]))
        {
            if(!empty($Tariff = Tariff::FindById($Parameters["Post"]["tariff_id"])))
            {
                $Tariff->Delete();
                PrintJson::OperationSuccessful();
            }
            else
                PrintJson::OperationError(TariffNotFound, REQUEST_FAILED);
        }
    }


    public function ActionAddSales(array $Parameters)
    {
        if(Validator::IsValid($Parameters["Post"], [
            ["Key" => "month"],
            ["Key" => "sale"]
        ]))
        {
            if(empty($Sales = SalesTariff::FindByMonth($Parameters["Post"]["month"])))
            {
                $Sales = new SalesTariff();
                $Sales->Month = $Parameters["Post"]["month"];
                $Sales->Sale = $Parameters["Post"]["sale"];
                $Sales->Save();
                PrintJson::OperationSuccessful();
            }
        }
    }


    public function ActionUpdateSales(array $Parameters)
    {
        if(Validator::IsValid($Parameters["Post"], [
            ["Key" => "sale_id", "Type" => "int"],
            ["Key" => "month"],
            ["Key" => "sale"]
        ]))
        {
            if(!empty($Sales = SalesTariff::FindById($Parameters["Post"]["sale_id"])))
            {
                $Sales->Month = $Parameters["Post"]["month"];
                $Sales->Sale = $Parameters["Post"]["sale"];
                $Sales->Save();
                PrintJson::OperationSuccessful();
            }
        }
    }


    public function ActionGetSales(array $Parameters)
    {
        if(Validator::IsValid($Parameters["Post"], [
            ["Key" => "sale_id", "Type" => "int"]
        ]))
        {
            if(!empty($Sales = SalesTariff::FindById($Parameters["Post"]["sale_id"])))
            {
                PrintJson::OperationSuccessful([
                    "Month" => $Sales->Month,
                    "Sale"  => $Sales->Sale,
                ]);
            }
        }
    }


    public function ActionDeleteSales(array $Parameters)
    {
        if(Validator::IsValid($Parameters["Post"], [
            ["Key" => "sale_id", "Type" => "int"],
        ]))
        {
            if(!empty($Sales = SalesTariff::FindById($Parameters["Post"]["sale_id"])))
            {
                $Sales->Delete();
                PrintJson::OperationSuccessful();
            }
            else
                PrintJson::OperationError(TariffNotFound, REQUEST_FAILED);
        }
    }
}