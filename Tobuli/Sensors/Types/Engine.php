<?php


namespace Tobuli\Sensors\Types;

class Engine extends Logical
{
    public static function getType(): string
    {
        return 'engine';
    }

    public static function getTypeTitle(): string
    {
        return trans('front.engine_on_off');
    }

    public static function isUnique() : bool
    {
        return true;
    }
}