<?php


namespace controllers;


use models\Event;
use models\EventCondition;


class EventController
{
    static public function EventToArray(Event $Event) : array
    {
        $ActionData = null;
        if($Event->GetActionType() == Event::ACTION_SEND_MESSAGE)
            $ActionData = MessageController::MessageToArray($Event->GetActionData());
        else if($Event->GetActionType() == Event::ACTION_MOVE_TO_FOLDER)
            $ActionData = $Event->GetActionData();

        $ElseActionData = null;
        if($Event->GetElseActionType() == Event::ACTION_SEND_MESSAGE)
            $ElseActionData = MessageController::MessageToArray($Event->GetElseActionData());
        else if($Event->GetElseActionType() == Event::ACTION_MOVE_TO_FOLDER)
            $ElseActionData = $Event->GetElseActionData();

        $StartCondition = [];
        if(is_array($Event->GetStartCondition()))
        {
            foreach($Event->GetStartCondition() as $obj)
            {
                if(is_string($obj))
                    $StartCondition[] = $obj;
                else if($obj instanceof EventCondition)
                    $StartCondition[] = EventConditionController::EventConditionToArray($obj);
            }
        }

        return [
            "event_id"          => $Event->GetId(),
            "action_type"       => $Event->GetActionType(),
            "action_data"       => $ActionData,
            "else_action_type"  => $Event->GetElseActionType(),
            "else_action_data"  => $ElseActionData,
            "disable_dialog"    => $Event->GetDisableDialog(),
            "is_active"         => $Event->GetIsActive(),
            "start_condition"   => $StartCondition
        ];
    }
}