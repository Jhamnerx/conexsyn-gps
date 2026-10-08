<?php


namespace Tobuli\Sensors\Types;


class Temperature extends Numerical
{
    protected static $timeout = 300;

    public static function getType(): string
    {
        return 'temperature';
    }

    public static function getTypeTitle(): string
    {
        return trans('front.temperature');
    }

    public static function getInputs() : array
    {
        return [
            'default' => [
                'tag_name' => true,
                'formula' => true,
                'unit' => true,
                'skip_empty' => true,
                'calibration' => true,
                'add_to_history' => true,
                'add_to_graph' => true,
            ],
        ];
    }
}