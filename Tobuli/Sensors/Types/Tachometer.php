<?php


namespace Tobuli\Sensors\Types;


class Tachometer extends Numerical
{
    protected $precision = 0;

    public static function getType(): string
    {
        return 'tachometer';
    }

    public static function getTypeTitle(): string
    {
        return trans('front.tachometer');
    }

    public static function getInputs() : array
    {
        return [
            'default' => [
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