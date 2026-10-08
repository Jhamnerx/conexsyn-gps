<?php


namespace Tobuli\Sensors\Extractions;


use Tobuli\Sensors\Contracts\Extraction;

class SkipEmpty implements Extraction
{
    public function parse($value)
    {
        if (empty($value))
            return null;

        return $value;
    }
}