<?php

namespace Tobuli\History\Actions;


use Tobuli\Entities\Event;
use Tobuli\History\Group;

class GroupEvent extends ActionGroup
{
    protected $events;
    protected $lastEvent;

    public function boot(){}

    public function proccess($position)
    {
        $this->loadEvents();

        while ($event = $this->getLastEvent($position)) {
            $group = new Group('event');
            $group->name = $event->message;

            $this->history->groupStartEnd($group, $event);
        }
    }

    public function lastProcess($position)
    {
        $this->loadEvents();

        while ($event = $this->getEvent()) {
            $group = new Group('event');
            $group->name = $event->message;

            $this->history->groupStartEnd($group, $event);
        }
    }

    protected function getLastEvent($position)
    {
        if (empty($this->lastEvent))
            return null;

        if ($position->timestamp < strtotime($this->lastEvent->time))
            return null;

        return $this->getEvent();
    }

    protected function getEvent()
    {
        $event = $this->lastEvent;
        $this->lastEvent = $this->events->shift();

        return $event;
    }

    protected function loadEvents()
    {
        if (isset($this->events))
            return;

        $this->events = Event::userAccessible(getActingUser())
            ->whereBetween('time', [$this->history->getDateFrom(), $this->history->getDateTo()])
            ->where('device_id', $this->getDevice()->id)
            ->get()
            ->sortBy('time');

        $this->lastEvent = $this->events->shift();
    }
}