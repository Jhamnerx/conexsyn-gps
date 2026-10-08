<?php


namespace Tobuli\Sensors\Extractions;


use Tobuli\Sensors\Contracts\Extraction;

class Percentages implements Extraction
{
    /**
     * @var int
     */
    protected $min;

    /**
     * @var int
     */
    protected $max;

    public function __construct($min, $max)
    {
        $this->min = floatval($min);
        $this->max = floatval($max);
    }

    public function parse($value)
    {
        $value_number = parseNumber($value);

        if (!is_numeric($value_number))
            return null;

        if ($value <= $this->min)
            return 0;

        if ($value >= $this->max)
            return 100;
        
        return getPrc($this->max - $this->min, $value_number - $this->min);
    }


}