<?php


namespace Tobuli\Sensors\Extractions;


use Tobuli\Sensors\Contracts\Extraction;

class Calibration implements Extraction
{
    const ORDER_DEC = 1;
    const ORDER_ASC = 2;

    /**
     * @var array
     */
    protected $calibrations;

    protected $first;
    protected $last;
    protected $first_val;
    protected $last_val;
    protected $order;
    protected $skip;

    public function __construct($calibrations, $skip)
    {
        $this->setCalibrations($calibrations);

        $this->skip = $skip;
    }

    public function setCalibrations($calibrations)
    {
        krsort($calibrations);

        $this->first = key($calibrations);
        $this->first_val = current($calibrations);
        $this->last_val = end($calibrations);
        $this->last = key($calibrations);

        $this->calibrations = $calibrations;

        $this->order = ($this->first_val > $this->last_val && $this->first < $this->last)
            ? self::ORDER_DEC
            : self::ORDER_ASC;
    }

    public function parse($value)
    {
        if (!is_numeric($value))
            return null;

        if ($this->order === self::ORDER_DEC) {
            $calibrated = $this->calibrateDec($value);
        } else {
            $calibrated = $this->calibrateAsc($value);
        }

        return is_null($calibrated) ? null : round($calibrated, 2);
    }

    public function getMaxValue()
    {
        return $this->order == self::ORDER_DEC ? $this->first_val : $this->last_val;
    }

    protected function calibrateDec($value)
    {
        if ($value < $this->first)
            return $this->skip ? null : $this->first_val;

        $prev_x = null;
        $prev_y = null;

        foreach ($this->calibrations as $x => $y) {
            if (!is_null($prev_x)) {
                if ($value < $x) {
                    return $this->calibrate($value, $prev_x, $prev_y, $x, $y);
                }
            }

            $prev_x = $x;
            $prev_y = $y;
        }

        return $this->skip ? null : $this->last_val;
    }

    protected function calibrateAsc($value)
    {
        if ($value > $this->first)
            return $this->skip ? null : $this->first_val;

        $prev_x = null;
        $prev_y = null;

        foreach ($this->calibrations as $x => $y) {
            if (!is_null($prev_x)) {
                if ($value > $x) {
                    return $this->calibrate($value, $prev_x, $prev_y, $x, $y);
                }
            }

            $prev_x = $x;
            $prev_y = $y;
        }

        return $this->skip ? null : $this->last_val;
    }

    protected function calibrate($number, $x1, $y1, $x2, $y2)
    {
        if ($number == $x1)
            return $y1;

        if ($number == $x2)
            return $y2;


        if ($x1 > $x2) {
            $nx1 = $x1;
            $nx2 = $x2;
        } else {
            $nx1 = $x2;
            $nx2 = $x1;
        }

        if ($y1 > $y2) {
            $ny1 = $y1;
            $ny2 = $y2;
            $pr = $x2;
        } else {
            $ny1 = $y2;
            $ny2 = $y1;
            $pr = $x1;
        }


        $sk = ($pr - $number);
        $sk = $sk < 0 ? -$sk : $sk;

        return (($ny1 - $ny2) / ($nx1 - $nx2)) * $sk + $ny2;
    }
}