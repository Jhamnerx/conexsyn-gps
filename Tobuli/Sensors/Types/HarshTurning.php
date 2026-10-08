<?php


namespace Tobuli\Sensors\Types;


class HarshTurning extends HarshBreaking
{
    public static function getType(): string
    {
        return 'harsh_turning';
    }

    public static function getTypeTitle(): string
    {
        return trans('front.harsh_turning');
    }
}