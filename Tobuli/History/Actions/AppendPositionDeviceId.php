<?php

namespace Tobuli\History\Actions;


class AppendPositionDeviceId extends ActionAppend
{
    protected $device_id;

    public function boot(){
        $this->device_id = $this->getDevice()->traccar_device_id;
    }

    public function proccess(&$position)
    {
        $position->device_id = $this->device_id;
    }
}