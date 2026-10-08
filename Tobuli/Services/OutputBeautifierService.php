<?php

namespace Tobuli\Services;

class OutputBeautifierService
{
    public function arrayToKeyValueText($value)
    {
        if (!is_array($value)) {
            return $value;
        }

        return str_replace(
            ["Array\n", "\n\n"],
            ['', "\n"],
            print_r($value, true)
        );
    }
}