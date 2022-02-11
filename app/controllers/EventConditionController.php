<?php


namespace controllers;


use classes\Validator;
use models\EventCondition;


class EventConditionController
{
    static public function EventConditionToArray(EventCondition $EventCondition) : array
    {
        return [
            "type" => $EventCondition->GetType(),
            "data" => Validator::ArrayKeyPascalCaseToSnakeCase($EventCondition->GetData())
        ];
    }
}