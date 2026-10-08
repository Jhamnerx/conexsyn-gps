<?php


namespace Tobuli\Sensors\Types;


class FuelConsumption extends Numerical
{
    protected $precision = 0;
    protected static $defaultShowType = 'incremental';

    public static function getType(): string
    {
        return 'fuel_consumption';
    }

    public static function getTypeTitle(): string
    {
        return trans('front.fuel_consumption');
    }

    public static function getShowTypes()
    {
        return [
            'incremental' => trans('front.incremental'),
            'separate' => trans('front.separate'),
        ];
    }

    public static function getInputs() : array
    {
        return [
            'incremental' => [
                'tag_name' => true,
                'formula' => true,
                'unit' => true,
                'skip_empty' => true,
                'add_to_history' => true,
                'add_to_graph' => true,
            ],
            'separate' => [
                'tag_name' => true,
                'formula' => true,
                'unit' => true,
                'skip_empty' => true,
                'add_to_history' => true,
                'add_to_graph' => true,
            ],
        ];
    }
}