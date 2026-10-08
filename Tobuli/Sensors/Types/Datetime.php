<?php


namespace Tobuli\Sensors\Types;

use Tobuli\Sensors\Sensor;
use Formatter;

class Datetime extends Sensor
{
    public static function getType(): string
    {
        return 'datetime';
    }

    public static function getTypeTitle(): string
    {
        return trans('front.datetime');
    }

    protected function getResult($value)
    {
        if (empty($value))
            return null;

        if (is_numeric($value) && (int)$value == $value)
            return $value;

        if (!is_string($value))
            return null;

        $time = strtotime($value);

        return $time ? $time : null;
    }

    public function getValueFormatted($value)
    {
        $datetime = date('Y-m-d H:i:s', $value);

        return Formatter::time()->human($datetime);
    }

    public static function getInputs() : array
    {
        return [
            'default' => [
                'tag_name' => true,
                'skip_empty' => true,
                'bitcut' => true,
                'setflag' => true,
                'add_to_history' => true,
            ]
        ];
    }
}