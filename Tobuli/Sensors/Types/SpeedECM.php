<?php


namespace Tobuli\Sensors\Types;


class SpeedECM extends Numerical
{
    protected $precision = 0;
    protected static $timeout = 300;

    public static function getType(): string
    {
        return 'speed_ecm';
    }

    public static function getTypeTitle(): string
    {
        return trans('front.speed') . ' ECM';
    }

    public static function getInputs() : array
    {
        return [
            'default' => [
                'tag_name' => true,
                'formula' => true,
                'skip_empty' => true,
                'add_to_history' => true,
                'add_to_graph' => true,
            ],
        ];
    }
}