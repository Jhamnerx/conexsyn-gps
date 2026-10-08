<?php


namespace Tobuli\Sensors\Extractions;


use Tobuli\Sensors\Contracts\Extraction;

class SetFlag implements Extraction
{
    /**
     * @var int
     */
    protected $start;

    /**
     * @var int
     */
    protected $count;

    public function __construct($start, $count)
    {
        $this->start = $start;
        $this->count = $count;
    }

    public function parse($value)
    {
        $value = substr($value, $this->start, $this->count);

        return $value === false ? null : $value;
    }
}