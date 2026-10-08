<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Tobuli\Entities\Device;

class DeviceDeleted extends Event
{
    use Dispatchable, SerializesModels;

    public Device $device;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Device $device)
    {
        $this->device = $device;
    }
}
