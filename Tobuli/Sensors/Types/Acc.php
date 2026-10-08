<?php


namespace Tobuli\Sensors\Types;

class Acc extends Logical
{
    public static function getType(): string
    {
        return 'acc';
    }

    public static function getTypeTitle(): string
    {
        return trans('front.acc_on_off');
    }

    public static function isUnique() : bool
    {
        return true;
    }
}