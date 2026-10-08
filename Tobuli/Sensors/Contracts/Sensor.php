<?php


namespace Tobuli\Sensors\Contracts;


interface Sensor
{

    public function getPositionValue($position);
    public function getPositionStoredValue($position);
    public function getDataValue($data);
    public function getValueIcon($value);
    public function getValueFormatted($value);
}