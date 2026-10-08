<?php

namespace Tobuli\History\Actions;


class AppendDriveBusinessBySensor extends ActionAppend
{
    protected $sensor;

    protected $last = null;

    public static function after()
    {
        return [AppendDriveBusinessByRoute::class];
    }

    public function boot()
    {
        if ( ! settings('plugins.business_private_drive.status') )
            return;

        $this->sensor = $this->getSensor('drive_business');
    }

    public function proccess(&$position)
    {
        if ( ! $this->sensor)
            return;

        if (property_exists($position, 'drive_business')) {
            return;
        }

        $position->drive_business = $this->last;

        $value = $this->getSensorValue($this->sensor, $position, null);

        if (is_null($value))
            return;

        $position->drive_business = $this->last = $value;
    }
}