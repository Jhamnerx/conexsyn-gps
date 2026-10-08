<?php

namespace Tobuli\History\Actions;

use Tobuli\Entities\DeviceSensor;

class AppendAnonymized extends ActionAppend
{
    protected ?DeviceSensor $sensor = null;

    public function boot()
    {
        $this->sensor = $this->getDevice()->getAnonymizerSensor();
    }

    public function proccess(&$position)
    {
        $position->anonymized = $this->sensor && $this->getSensorValue($this->sensor, $position);
    }
}