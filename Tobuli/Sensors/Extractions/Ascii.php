<?php


namespace Tobuli\Sensors\Extractions;


use Tobuli\Sensors\Contracts\Extraction;

class Ascii implements Extraction
{
    public function parse($value)
    {
        try {
            $text = hex2bin($value);
        } catch (\Exception $e) {
            return null;
        }

        if (empty($text))
            return null;

        return preg_replace('/[\x00-\x1F\x7F-\xFF]/', '', $text);
    }
}