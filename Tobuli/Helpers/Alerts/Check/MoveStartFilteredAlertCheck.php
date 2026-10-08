<?php

namespace Tobuli\Helpers\Alerts\Check;

use App\Jobs\ConfirmMoveStartFiltered;
use Tobuli\Entities\Event;

class MoveStartFilteredAlertCheck extends MoveStartAlertCheck
{
    public function checkEvents($position, $prevPosition): ?array
    {
        return $this->check($position) ? [] : null;
    }

    public function check($position): bool
    {
        if (!parent::check($position)) {
            return false;
        }

        $event = $this->getEvent();
        $event->type = Event::TYPE_MOVE_START_FILTERED;

        dispatch(
            (new ConfirmMoveStartFiltered($position->toArray(), $event->toArray(), $this->device, $this->alert))
        );

        return true;
    }
}