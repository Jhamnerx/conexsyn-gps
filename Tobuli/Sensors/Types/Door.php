<?php


namespace Tobuli\Sensors\Types;

class Door extends Logical
{
    public static function getType(): string
    {
        return 'door';
    }

    public static function getTypeTitle(): string
    {
        return trans('front.door_on_off');
    }
}