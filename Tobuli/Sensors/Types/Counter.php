<?php


namespace Tobuli\Sensors\Types;

use Tobuli\Sensors\Sensor;

class Counter extends Sensor
{
    protected $precision = 0;

    public static function getType(): string
    {
        return 'counter';
    }

    public static function getTypeTitle(): string
    {
        return trans('front.counter');
    }

    public static function isPositionValue() : bool
    {
        return true;
    }

    public function getPositionValue($position)
    {
        return $this->getPositionStoredValue($position);
    }

    protected function getResult($value)
    {
        if ($this->on && $this->on->parse($value)) {
            return true;
        }

        return null;
    }

    public function getValueFormatted($value)
    {
        return $value;
    }

    public static function getInputs() : array
    {
        return [
            'default' => [
                'tag_name' => true,
                'logic_on' => true,
                'value' => true,
                //'add_to_history' => true,
                //'add_to_graph' => true,
            ]
        ];
    }
}