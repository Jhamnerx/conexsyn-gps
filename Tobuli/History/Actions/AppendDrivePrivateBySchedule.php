<?php

namespace Tobuli\History\Actions;

class AppendDrivePrivateBySchedule extends ActionAppend
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
        if (property_exists($position, 'drive_private')) {
            return;
        }

        if ($position->in_business_time !== null) {
            $position->drive_private = $position->in_business_time === false;
        }
    }
}