<?php

namespace Tobuli\Helpers\Alerts\Check;


use Tobuli\Entities\Alert;
use Tobuli\Entities\Device;
use Tobuli\Entities\Event;

class OverspeedAlertCheck extends AlertCheck
{
    protected $checkEngine;

    protected $sensor;

    public function __construct(Device $device, Alert $alert)
    {
        parent::__construct($device, $alert);

        $this->checkEngine = settings('plugins.overspeed_only_engine_on.status');

        if ($this->checkEngine) {
            $this->sensor = $this->device->getEngineSensor();
        }
    }

    public function checkEvents($position, $prevPosition)
    {
        if (empty($this->alert->getOverspeed()))
            return null;

        if ( ! $position->isValid())
            return null;

        if ( ! $this->check($position))
            return null;

        if ($this->check($prevPosition))
            return null;

        $event = $this->getEvent();

        $event->type = Event::TYPE_OVERSPEED;
        $event->message = '';
        $event->setAdditional('overspeed_speed', $this->alert->getOverspeed());

        $this->silent($event);

        return [$event];
    }

    protected function check($position)
    {
        if ( ! $position)
            return false;

        if ( ! $this->checkAlertPosition($position))
            return false;

        if (round($this->device->getSpeed($position)) <= round($this->alert->getOverspeed()))
            return false;

        if ($this->checkEngine && $this->checkEngineStatus($position) !== true)
            return false;

        return true;
    }

    protected function checkEngineStatus($position)
    {
        if (!$this->sensor)
            return true;

        return $this->sensor->getValuePosition($position);
    }
}