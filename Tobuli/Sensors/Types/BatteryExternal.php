<?php


namespace Tobuli\Sensors\Types;


class BatteryExternal extends Battery
{
    public static function getType(): string
    {
        return 'battery_external';
    }

    public static function getTypeTitle(): string
    {
        return trans('front.battery_external');
    }

    public static function isEnabled() : bool
    {
        return config('addon.sensor_type_plugged');
    }
}