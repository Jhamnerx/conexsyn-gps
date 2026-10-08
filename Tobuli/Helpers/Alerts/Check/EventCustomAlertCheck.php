<?php

namespace Tobuli\Helpers\Alerts\Check;

use Tobuli\Entities\Event;
use Tobuli\Helpers\SetFlag;
use Tobuli\Services\ConditionService;

class EventCustomAlertCheck extends AlertCheck
{
    public function checkEvents($position, $prevPosition)
    {
        if ( ! $this->checkAlertPosition($position)) {
            if ($this->hasContinuousDuration()) {
                $this->resetStartedAt();
            }

            return null;
        }

        $events = [];

        foreach ($this->alert->events_custom as $eventCustom)
        {
            $currentStatus = $this->check($position, $eventCustom);

            if ($this->hasContinuousDuration()) {
                if ( ! is_null($currentStatus))
                    $currentStatus ? $this->setStartedAt($position->time) : $this->resetStartedAt();

                if ( ! $currentStatus)
                    continue;

                if ( ! $this->checkContinuousDuration($position))
                    continue;

                if (!$eventCustom->always && $this->checkContinuousDuration($prevPosition))
                    continue;

            } else {
                if ( ! $currentStatus)
                    continue;

                if (!$eventCustom->always && $this->getFiredAt() && $this->check($prevPosition, $eventCustom))
                    continue;
            }

            $event = $this->getEvent();

            $event->type = Event::TYPE_CUSTOM;
            $event->message = $eventCustom->message;

            $this->silent($event);

            $events[] = $event;

            $this->setFiredAt($event->time);
        }

        return $events;
    }

    protected function check($position, $eventCustom)
    {
        if ( ! $position)
            return null;

        if ($eventCustom->protocol != $position->protocol)
            return null;

        if ( ! $this->checkCustomEventConditions($position, $eventCustom))
            return false;

        return true;
    }

    protected function checkCustomEventConditions($position, $customEvent)
    {
        $parameters = $position->parameters;
        $parameters['speed'] = $this->device->getSpeed($position);

        if (empty($customEvent->conditions))
            return false;

        foreach ($customEvent->conditions as $condition)
        {
            $tag_value = $condition['tag_value'];

            if (!array_key_exists($condition['tag'], $parameters)) {
                $value = null;
            } else {
                $value = $parameters[$condition['tag']];

                if ($condition['tag'] == 'rfid' && $position->protocol == 'meitrack')
                    $value = hexdec($value);

                if ($setflag = SetFlag::singleCropValue($tag_value)) {
                    $tag_value = $setflag->getValue();
                    $value = $setflag->crop($value);
                }
            }

            if ( ! ConditionService::check($condition['type'], $value, $tag_value)) {
                return false;
            }
        }

        return true;
    }


    protected function checkContinuousDuration($position)
    {
        if (empty($position))
            return null;

        if (empty($this->alert->pivot->started_at))
            return false;

        $duration = strtotime($position->time) - strtotime($this->alert->pivot->started_at);

        return $this->alert->continuous_duration < $duration;
    }

    protected function hasContinuousDuration()
    {
        return $this->alert->continuous_duration > 0 ? true : false;
    }

    protected function getFiredAt()
    {
        return $this->alert->pivot->fired_at ?? null;
    }

    protected function setFiredAt($time)
    {
        if ($time > $this->alert->pivot->fired_at)
            $this->alert->pivot->fired_at = $time;
    }

    protected function setStartedAt($time)
    {
        if (!is_null($this->alert->pivot->started_at))
            return;

        $this->saveStartedAt($time);
    }

    protected function resetStartedAt()
    {
        if (is_null($this->alert->pivot->started_at))
            return;

        $this->saveStartedAt(null);
    }

    protected function saveStartedAt($time)
    {
        $this->alert->pivot->started_at = $time;

        $this->alert->devices()->updateExistingPivot($this->device->id, [
            'started_at' => $time
        ]);
    }
}