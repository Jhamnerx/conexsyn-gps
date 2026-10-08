<?php


namespace Tobuli\Sensors\Types;

class Ignition extends Logical
{
    public static function getType(): string
    {
        return 'ignition';
    }

    public static function getTypeTitle(): string
    {
        return trans('front.ignition_on_off');
    }

    public static function isUnique() : bool
    {
        return true;
    }
}