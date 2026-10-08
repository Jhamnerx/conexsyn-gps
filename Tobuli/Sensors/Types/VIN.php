<?php


namespace Tobuli\Sensors\Types;

use Tobuli\Sensors\Sensor;

class VIN extends Textual
{
    public static function getType(): string
    {
        return 'vin';
    }

    public static function getTypeTitle(): string
    {
        return trans('front.vin');
    }

    public static function getInputs() : array
    {
        return [
            'default' => [
                'tag_name' => true,
                'skip_empty' => true,
                'bitcut' => true,
                'setflag' => true,
                'mapping' => true,
                'add_to_history' => true,
            ]
        ];
    }
}