<?php


namespace Tobuli\Sensors\Types;

class Seatbelt extends Logical
{
    public static function getType(): string
    {
        return 'seatbelt';
    }

    public static function getTypeTitle(): string
    {
        return trans('front.seatbelt_on_off');
    }
}