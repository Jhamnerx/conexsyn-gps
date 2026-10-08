<?php


namespace Tobuli\Sensors;

use Tobuli\Sensors\Contracts\Extraction AS ExtractionInterface;

class Extraction
{
    /**
     * @var ExtractionInterface[]
     */
    protected $extractions = [];


    public function __construct($extractions)
    {
        $this->extractions = $extractions;
    }

    public function parse($value)
    {
        foreach ($this->extractions as $extraction) {
            $value = $extraction->parse($value);

            if (is_null($value)) {
                return $value;
            }
        }

        return $value;
    }
}