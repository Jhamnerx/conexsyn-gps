<?php

namespace Tobuli\History\Actions;

class AppendDrivePrivate extends ActionAppend
{
    public static function after()
    {
        return [
            AppendDrivePrivateByRoute::class,
            AppendDrivePrivateBySensor::class,
            AppendDrivePrivateBySchedule::class,
        ];
    }

    public static function defaultAfter(): array
    {
        return [
            AppendDrivePrivateByRoute::class,
            AppendDrivePrivateBySensor::class,
            AppendDrivePrivateBySchedule::class,
        ];
    }

    public function boot()
    {
    }

    public function proccess(&$position)
    {
    }
}