<?php


namespace Tobuli\Sensors\Types;


class Satellites extends Textual
{
    public static function getType(): string
    {
        return 'satellites';
    }

    public static function getTypeTitle(): string
    {
        return trans('front.satellites');
    }
}