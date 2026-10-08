<?php

namespace Tobuli\History\Actions;

class AppendDriveBusiness extends ActionAppend
{
    public static function after()
    {
        return [
            AppendDriveBusinessByRoute::class,
            AppendDriveBusinessBySensor::class,
            AppendDriveBusinessBySchedule::class,
        ];
    }

    public static function defaultAfter(): array
    {
        return [
            AppendDriveBusinessByRoute::class,
            AppendDriveBusinessBySensor::class,
            AppendDriveBusinessBySchedule::class,
        ];
    }

    public function boot()
    {
    }

    public function proccess(&$position)
    {
    }
}