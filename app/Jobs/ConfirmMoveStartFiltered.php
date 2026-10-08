<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\SerializesModels;
use Tobuli\Entities\Alert;
use Tobuli\Entities\Device;
use Tobuli\Entities\Event;
use Tobuli\Entities\TraccarPosition;
use Tobuli\Helpers\Formatter\Facades\Formatter;
use Tobuli\Services\EventWriteService;

class ConfirmMoveStartFiltered extends Job implements ShouldQueue
{
    use SerializesModels;

    private array $positionData;
    private array $eventData;
    private Device $device;
    private Alert $alert;

    public function __construct(array $positionData, array $eventData, Device $device, Alert $alert)
    {
        $this->positionData = $positionData;
        $this->eventData = $eventData;
        $this->device = $device;
        $this->alert = $alert;
    }

    public function handle(): void
    {
        $currentPosition = new TraccarPosition($this->positionData);

        $positionTime = \Carbon::parse($currentPosition->time);
        $stoppedAt = \Carbon::parse($this->device->traccar->stoped_at);

        if ($positionTime->diffInDays($stoppedAt) > 0) {
            $this->sendEvent();

            return;
        }

        if (!$this->checkTime($currentPosition)) {
            return;
        }

        $startPosition = $this->device
            ->positions()
            ->where('time', '>=', (clone $positionTime)->subDay())
            ->orderliness('ASC')
            ->first();

        if (!$startPosition) {
            return;
        }

        $startDistance = $startPosition->getParameter('totaldistance');
        $currentDistance = $this->device->getParameter('totaldistance');

        if ($currentDistance - $startDistance < $this->alert->distance * 1000) {
            return;
        }

        $this->sendEvent();
    }

    private function checkTime(TraccarPosition $position): bool
    {
        if (!$this->alert->acceptable_time_from) {
            return false;
        }

        Formatter::byUser($this->alert->user);

        $timeFrom = \Carbon::parse(Formatter::time()->convert($this->alert->acceptable_time_from));
        $positionTime = \Carbon::parse(Formatter::time()->convert($position->time));

        if ($timeFrom->gt($positionTime)) {
            return false;
        }

        return true;
    }

    private function sendEvent(): void
    {
        $event = new Event($this->eventData);
        $event->channels = $this->eventData['channels'];

        (new EventWriteService())->write([$event]);
    }
}