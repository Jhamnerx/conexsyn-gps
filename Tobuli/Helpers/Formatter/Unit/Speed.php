<?php

namespace Tobuli\Helpers\Formatter\Unit;

class Speed extends Numeric
{
    public function __construct()
    {
        $this->setMeasure('km');
    }

    public function byMeasure($unit)
    {
        switch ($unit) {
            case 'km':
                $this->setRatio(1);
                $this->setUnit(trans('front.dis_h_km'));
                $this->setPrecision(0);
                break;

            case 'mi':
                $this->setRatio(0.621371192);
                $this->setUnit(trans('front.dis_h_mi'));
                $this->setPrecision(0);
                break;

            case 'nm':
            case 'kn':
                $this->setRatio(0.54);
                $this->setUnit(trans('front.kn'));
                $this->setPrecision(2);
                break;

            default:
                $this->setRatio(1);
                $this->setUnit($unit);
                $this->setPrecision(0);
        }
    }
}