<?php


namespace Tobuli\Sensors\Types;


class Load extends Numerical
{
    protected $precision = 0;
    protected static $timeout = 300;

    public static function getType(): string
    {
        return 'load';
    }

    public static function getTypeTitle(): string
    {
        return trans('front.load');
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