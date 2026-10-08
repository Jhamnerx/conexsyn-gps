<?php


namespace Tobuli\Sensors\Types;


class GSM extends Numerical
{
    protected static $timeout = 300;

    protected $precision = 0;

    public static function getType(): string
    {
        return 'gsm';
    }

    public static function getTypeTitle(): string
    {
        return trans('front.gsm');
    }

    public static function getInputs() : array
    {
        return [
            'default' => [
                'tag_name' => true,
                'minmax' => true,
                'add_to_history' => true,
                'add_to_graph' => true,
            ],
        ];
    }
}