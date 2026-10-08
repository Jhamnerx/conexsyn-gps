<?php

namespace Tobuli\Helpers\Alerts\Check;


use Tobuli\Entities\Event;

class PoiStopDurationAlertCheck extends AlertCheck
{
    public function checkEvents($position, $prevPosition)
    {
        if ( ! $this->checkDuration())
            return null;

        if ( ! $this->checkPosition())
            return null;

        //convert from m to km
        $tolerance = $this->alert->getDistanceTolerance() / 1000;
        $position  = $this->getPosition();

        $check_at = max($this->device->traccar->moved_at, $this->device->traccar->engine_off_at);
        if ( ! $this->checkOccurred($check_at)) {
            return null;
        }

        $events = [];

        foreach ($this->alert->pois as $poi)
        {
            if ( ! $poi->pointIn($position, $tolerance))
                continue;

            $event = $this->getEvent();

            $event->type = Event::TYPE_POI_STOP_DURATION;
            $event->poi_id = $poi->id;
            $event->setAdditional('poi', $poi->name);
            $event->setAdditional('distance', $poi->pointDistance($position));
            $event->setAdditional('stop_duration', round($this->device->getStopDuration() / 60));

            $this->silent($event);

            $events[] = $event;
        }

        return $events;
    }

    protected function checkDuration()
    {
        if ( $this->alert->stop_duration < 1 )
            return false;

        $stopDuration = round($this->device->getStopDuration() / 60);

        if ($stopDuration < $this->alert->stop_duration )
            return false;

        if ( ! $this->device->traccar->moved_at )
            return false;

        return true;
    }

    protected function checkPosition()
    {
        $position = $this->getPosition();

        if ( ! $position)
            return false;

        if ( ! $this->checkAlertPosition($position))
            return false;

        return true;
    }
}