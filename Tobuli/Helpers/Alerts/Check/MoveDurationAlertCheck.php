<?php

namespace Tobuli\Helpers\Alerts\Check;


use Tobuli\Entities\Event;
use Tobuli\Entities\TraccarPosition;

class MoveDurationAlertCheck extends AlertCheck
{
    private $pivot;

    public function checkEvents($position, $prevPosition): ?array
    {
        $this->pivot = $this->device->pivot ?? $this->alert->pivot;

        if (!$this->check()) {
            return null;
        }

        $event = $this->getEvent();

        $event->type = Event::TYPE_MOVE_DURATION;
        $event->message = '';
        $event->setAdditional('move_duration', $this->getMoveDuration());

        $this->silent($event);

        return [$event];
    }

    public function check(): bool
    {
        if ($this->alert->move_duration < 1) {
            return false;
        }

        $position = $this->getPosition();

        if (!$position) {
            return false;
        }

        if (!$this->isMoving($position)) {
            return false;
        }

        $this->checkStartedAt($position);

        if (!$this->pivot->started_at || !$this->checkOccurred($this->pivot->started_at)) {
            return false;
        }

        $moveDuration = $this->getMoveDuration();

        return $moveDuration > $this->alert->move_duration;
    }

    protected function getMoveDuration(): int
    {
        if (!$this->pivot->started_at) {
            return 0;
        }

        return round((strtotime($this->position->time) - strtotime($this->pivot->started_at)) / 60);
    }

    protected function checkStartedAt(TraccarPosition $position): void
    {
        $time = $position->time;

        if (!$this->pivot->started_at && $this->isMoving($position)) {
            $this->saveStartedAt($time);

            return;
        }

        if ($this->prevPosition && $this->isMoving($this->prevPosition)) {
            return;
        }

        $stopDuration = round((strtotime($time) - strtotime($this->device->traccar->stop_begin_at)) / 60);

        if ($stopDuration < $this->alert->min_parking_duration) {
            return;
        }

        $this->saveStartedAt($time);
    }

    private function isMoving(TraccarPosition $position): bool
    {
        return $position->speed >= $this->device->min_moving_speed;
    }

    protected function saveStartedAt($time): void
    {
        if ($this->pivot->started_at === $time) {
            return;
        }

        $this->pivot->started_at = $time;

        $this->alert->devices()->updateExistingPivot($this->device->id, [
            'started_at' => $time
        ]);
    }
}