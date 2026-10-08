<?php

namespace Tobuli\Services;

use App\Exceptions\ResourseNotFoundException;
use App\Jobs\SendConfigurationCommands;
use CustomFacades\Server;
use Tobuli\Entities\Device;
use Tobuli\Entities\DeviceSensor;
use Tobuli\Exceptions\ValidationException;
use Tobuli\Helpers\SMS\Services\SendSmsManager;
use Validator;

class DeviceAnonymizerService
{
    /**
     * @var Device
     */
    protected $device;

    /**
     * @var DeviceSensor
     */
    protected $sensor;

    protected $lastPosition;

    public function __construct(Device $device)
    {
        $this->device = $device;

        $this->sensor = $device->getAnonymizerSensor();
    }

    public function getLatitude($position)
    {
        $this->check($position);

        return $this->lastPosition->latitude ?? null;
    }

    public function getLongitude($position)
    {
        $this->check($position);

        return $this->lastPosition->longitude ?? null;
    }

    public function isAnonymous($position)
    {
        if (!$this->sensor)
            return false;

        return $this->sensor->getValuePosition($position);
    }

    protected function check($position)
    {
        if ($position->id == ($this->lastPosition->id ?? null))
            return null;

        if ($this->isAnonymous($position))
            return null;

        return $this->lastPosition = $position;
    }
}
