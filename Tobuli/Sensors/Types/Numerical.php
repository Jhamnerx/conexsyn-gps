<?php


namespace Tobuli\Sensors\Types;

use Tobuli\Sensors\Sensor;

class Numerical extends Sensor
{
    protected $unit;

    protected $precision = 2;

    public static function getType(): string
    {
        return 'numerical';
    }

    public static function getTypeTitle(): string
    {
        return trans('front.numerical');
    }

    protected function getResult($value)
    {
        return $this->valueTypeConvert(parseNumber($value));
    }

    /**
     * @param $value
     * @return float|int|null
     */
    protected function valueTypeConvert($value)
    {
        if (is_null($value))
            return null;

        return floatval($value);
    }

    public function getValueFormatted($value)
    {
        $value = $this->getMappingValue($value);

        if (!is_numeric($value))
            return $value;

        if ($this->precision) {
            $value = round(floatval($value), $this->precision);
        } else {
            $value = round(intval($value));
        }

        return $value . ' ' . $this->getUnit();
    }

    public static function getInputs() : array
    {
        return [
            'default' => [
                'tag_name' => true,
                'bitcut' => true,
                'formula' => true,
                'mapping' => true,
                'unit' => true,
                'add_to_history' => true,
                'add_to_graph' => true,
            ]
        ];
    }
}