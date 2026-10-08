<?php

namespace App\Listeners;

use App\Events\DevicePositionChanged;
use App\Events\DeviceSensorDeleted;
use App\Events\GeofenceUpdatedEvent;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Tobuli\Entities\Geofence;
use Tobuli\Services\GeofenceService;

class DeviceIgnitionDetectionChangeListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  DeviceSensorDeleted  $event
     * @return void
     */
    public function handle(DeviceSensorDeleted $event)
    {
        if ($event->device->detect_engine != $event->sensor->type
            && $event->device->engine_hours != $event->sensor->type)
            return;

        if ($event->device->engine_hours == $event->sensor->type)
            $event->device->engine_hours = 'gps';

        if ($event->device->detect_engine == $event->sensor->type)
            $event->device->detect_engine = 'gps';

        $event->device->save();
    }
}
