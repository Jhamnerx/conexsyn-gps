<?php

namespace Tobuli\Importers\DeviceSensorCalibrations;

use Tobuli\Importers\Importer;
use Tobuli\Importers\ImportManager;

class DeviceSensorCalibrationsImportManager extends ImportManager
{
    protected function getReadersList(): array
    {
        return ['csv' => Readers\CsvReader::class];
    }

    public function getImporter(): Importer
    {
        return new DeviceSensorCalibrationsImporter();
    }
}