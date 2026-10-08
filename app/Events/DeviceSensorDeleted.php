<?php

namespace App\Events;

use App\Events\Event;
use Illuminate\Queue\SerializesModels;
use Tobuli\Entities\Device;
use Tobuli\Entities\DeviceSensor;

class DeviceSensorDeleted extends Event
{
    use SerializesModels;

    /**
     * @var Device
     */
    public $device;

    /**
     * @var DeviceSensor
     */
    public $sensor;

    public function __construct(Device $device, DeviceSensor $sensor) {
        $this->device = $device;
        $this->sensor = $sensor;
    }

}
