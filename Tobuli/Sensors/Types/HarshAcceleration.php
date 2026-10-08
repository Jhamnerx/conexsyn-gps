<?php


namespace Tobuli\Sensors\Types;


class HarshAcceleration extends HarshBreaking
{
    public static function getType(): string
    {
        return 'harsh_acceleration';
    }

    public static function getTypeTitle(): string
    {
        return trans('front.harsh_acceleration');
    }
}