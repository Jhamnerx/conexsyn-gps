<?php


namespace Tobuli\Sensors\Types;


class Battery extends Numerical
{
    protected static $defaultShowType = 'tag_value';

    protected static $timeout = 300;

    public static function getType(): string
    {
        return 'battery';
    }

    public static function getTypeTitle(): string
    {
        return trans('front.battery');
    }

    public static function getShowTypes()
    {
        return [
            'tag_value' => trans('validation.attributes.tag_value'),
            'min_max_values' => trans('front.min_max_values'),
            'formula' => trans('validation.attributes.formula'),
        ];
    }

    public static function getInputs() : array
    {
        return [
            'tag_value' => [
                'tag_name' => true,
                'unit' => true,
                'mapping' => true,
                'add_to_history' => true,
                'add_to_graph' => true,
            ],
            'min_max_values' => [
                'tag_name' => true,
                'minmax' => true,
                'add_to_history' => true,
                'add_to_graph' => true,
            ],
            'formula' => [
                'tag_name' => true,
                'bitcut' => true,
                'formula' => true,
                'unit' => true,
                'mapping' => true,
                'add_to_history' => true,
                'add_to_graph' => true,
            ]
        ];
    }




}