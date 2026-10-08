<?php


namespace Tobuli\Helpers;


use Tobuli\Sensors\Extractions\BitCut;

class SetFlagDO
{
    protected $place;
    protected $start;
    protected $count;
    protected $value;
    protected $bitcut;

    public function __construct($place, $start, $count, $value, $bitcut = null)
    {
        $this->place = $place;
        $this->start = $start;
        $this->count = $count;
        $this->value = $value;

        if ($bitcut) {
            $this->bitcut = new BitCut($this->start, $this->count, $bitcut == 'HEX' ? 16 : 10);
        }
    }

    public function getPlace()
    {
        return $this->place;
    }

    public function getStart()
    {
        return $this->start;
    }

    public function getCount()
    {
        return $this->count;
    }

    public function getValue()
    {
        return $this->value;
    }

    public function crop($value)
    {
        if ($this->bitcut)
            return $this->cropBit($value);

        return $this->cropString($value);
    }

    protected function cropString($value)
    {
        $value = substr($value, $this->start, $this->count);

        return $value === false ? null : $value;
    }

    protected function cropBit($value)
    {
        return $this->bitcut->parse($value);
    }
}