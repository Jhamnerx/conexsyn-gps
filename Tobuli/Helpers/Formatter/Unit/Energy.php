<?php

namespace Tobuli\Helpers\Formatter\Unit;

class Energy extends Numeric
{
    protected $precision = 1;

    public function __construct()
    {
        $this->setMeasure('kwh');
    }

    public function byMeasure($unit)
    {
        switch ($unit) {
            case 'kwh':
                $this->setRatio(1);
                $this->setUnit(trans('front.kwh'));
                break;

            default:
                $this->setRatio(1);
                $this->setUnit($unit);
        }
    }
}