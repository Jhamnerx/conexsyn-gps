<?php

namespace Tobuli\History\Actions;

class AppendDriveBusinessBySchedule extends ActionAppend
{
    public static function required()
    {
        return [AppendDriveInBusinessTime::class];
    }

    public static function after()
    {
        return [
            AppendDriveBusinessBySensor::class,
            AppendDriveBusinessByRoute::class,
        ];
    }

    public function boot()
    {
    }

    public function proccess(&$position)
    {
        if (property_exists($position, 'drive_business')) {
            return;
        }

        if ($position->in_business_time !== null) {
            $position->drive_business = $position->in_business_time === true;
        }
    }
}