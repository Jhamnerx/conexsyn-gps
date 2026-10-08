<?php


namespace Tobuli\Sensors\Extractions;


use Tobuli\Sensors\Contracts\Extraction;

class Number implements Extraction
{
    public function parse($value)
    {
        preg_match("/-?((?:[0-9]+,)*[0-9]+(?:\.[0-9]+)?)/", $value, $matches);
        if (isset($matches['0']))
            return $matches['0'];

        return '';
    }
}