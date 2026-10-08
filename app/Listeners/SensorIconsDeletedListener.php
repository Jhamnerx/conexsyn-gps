<?php

namespace App\Listeners;

use App\Events\SensorIconsDeleted;

class SensorIconsDeletedListener
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
     * @param  SensorIconsDeleted  $event
     * @return void
     */
    public function handle(SensorIconsDeleted $event)
    {
        // todo: implement logic for related device sensors
    }
}
