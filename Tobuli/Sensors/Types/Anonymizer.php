<?php


namespace Tobuli\Sensors\Types;


class Anonymizer extends Logical
{
    public static function getType(): string
    {
        return 'anonymizer';
    }

    public static function getTypeTitle(): string
    {
        return trans('front.anonymizer');
    }

    public static function isUnique() : bool
    {
        return true;
    }

    public static function isEnabled() : bool
    {
        return config('addon.sensor_type_anonymizer');
    }
}