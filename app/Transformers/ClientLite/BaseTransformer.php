<?php

namespace App\Transformers\ClientLite;

use App\Transformers\BaseTransformer AS ParentTransformer;
use Formatter;

class BaseTransformer extends ParentTransformer {

    protected function serializeDateTime($datetime)
    {
        $timestamp = strtotime($datetime);

        if (empty($timestamp))
            return null;

        return [
            'timestamp' => $timestamp,
            'formatted' => Formatter::time()->human($datetime)
        ];
    }

    protected function serializeFormatter($formatter, $value)
    {
        return [
            'value' => floatval($value),
            'unit'  => $formatter->getUnit(),
            'human' => $formatter->human($value)
        ];
    }
}