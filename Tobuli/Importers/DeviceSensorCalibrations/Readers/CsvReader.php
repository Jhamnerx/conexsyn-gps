<?php

namespace Tobuli\Importers\DeviceSensorCalibrations\Readers;

use Tobuli\Importers\Readers\CsvReader as BaseCsvReader;

class CsvReader extends BaseCsvReader
{
    protected $requiredFieldRules = [
        'x' => 'required|numeric',
        'y' => 'required|numeric',
    ];
}