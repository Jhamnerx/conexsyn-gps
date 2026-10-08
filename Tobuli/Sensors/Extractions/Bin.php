<?php


namespace Tobuli\Sensors\Extractions;


use Tobuli\Sensors\Contracts\Extraction;

class Bin implements Extraction
{
    /**
     * @var int
     */
    protected $from_base;

    /**
     * @var bool
     */
    protected $reverse;

    public function __construct($base, $reverse = true)
    {
        $this->from_base = $base;
        $this->reverse = $reverse;
    }

    public function parse($value)
    {
        try {
            $bin = base_convert($value, $this->from_base, 2);
            return $this->reverse ? strrev($bin) : $bin;
        } catch (\Exception $e) {
            return null;
        }
    }


}