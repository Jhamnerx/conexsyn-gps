<?php


namespace Tobuli\Sensors\Extractions;


use Tobuli\Sensors\Contracts\Extraction;

class Correlation implements Extraction
{
    /**
     * @var int
     */
    protected $val;

    /**
     * @var int
     */
    protected $max;

    public function __construct($max, $val)
    {
        $this->val = intval($val);
        $this->max = intval($max);
    }

    public function parse($value)
    {
        $value_number = parseNumber($value);

        if (!is_numeric($value_number))
            return null;

        return $this->max * (getPrc($this->val, $value_number) / 100);
    }


}