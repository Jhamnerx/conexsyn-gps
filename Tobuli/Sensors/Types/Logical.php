<?php


namespace Tobuli\Sensors\Types;

use Tobuli\Sensors\Extraction;
use Tobuli\Sensors\Extractions\Bin;
use Tobuli\Sensors\Extractions\Calibration;
use Tobuli\Sensors\Extractions\Formula;
use Tobuli\Sensors\Extractions\Logic;
use Tobuli\Sensors\Extractions\SetFlag;
use Tobuli\Sensors\Tag;
use Tobuli\Sensors\Sensor;

class Logical extends Sensor
{
    public static function getType(): string
    {
        return 'logical';
    }

    public static function getTypeTitle(): string
    {
        return trans('front.logical');
    }

    public static function isPositionValue() : bool
    {
        return true;
    }

    protected function getResult($value)
    {
        if ($this->on && $this->on->parse($value)) {
            return true;
        }

        if ($this->off && $this->off->parse($value)) {
            return false;
        }

        return null;
    }

    protected function valueTypeConvert($value)
    {
        if (is_null($value))
            return null;

        return boolval($value);
    }

    public function getValueFormatted($value)
    {
        return $value
            ? $this->on->getText()
            : $this->off->getText();
    }

    public static function getInputs() : array
    {
        return [
            'default' => [
                'tag_name' => true,
                'logic_on' => true,
                'logic_off' => true,
                //'bin' => true,
                'bitcut' => true,
                'add_to_history' => true,
                'add_to_graph' => true,
            ]
        ];
    }




}