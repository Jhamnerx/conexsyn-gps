<?php


namespace Tobuli\Sensors\Types;


class HarshBreaking extends Logical
{
    public static function getType(): string
    {
        return 'harsh_breaking';
    }

    public static function getTypeTitle(): string
    {
        return trans('front.harsh_breaking');
    }

    public static function isUpdatable() : bool
    {
        return false;
    }

    public static function isPositionValue() : bool
    {
        return false;
    }

    public static function isPersistent() : bool
    {
        return false;
    }

    public static function getInputs() : array
    {
        $inputs = parent::getInputs();
        $inputs['default']['logic_off'] = false;

        return $inputs;
    }
}