<?php

namespace Tobuli\History\Actions;

use Tobuli\Entities\DeviceSensor;

class AppendRpm extends ActionAppend
{
    private ?DeviceSensor $sensor;

    public function boot()
    {
        $this->sensor = $this->getSensor('tachometer');
    }

    public function proccess(& $position)
    {
        $position->rpm = $this->sensor
            ? $this->getSensorValue($this->sensor, $position)
            : null;
    }
}