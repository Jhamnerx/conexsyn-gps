<?php


namespace Tobuli\Sensors\Types;

class Plugged extends Logical
{
    public static function getType(): string
    {
        return 'plugged';
    }

    public static function getTypeTitle(): string
    {
        return trans('front.plugged');
    }

    public static function isUnique() : bool
    {
        return true;
    }

    public static function isEnabled() : bool
    {
        return config('addon.sensor_type_plugged');
    }
}