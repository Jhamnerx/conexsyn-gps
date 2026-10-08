<?php

namespace Tobuli\Importers\Task;

use Tobuli\Importers\Importer;
use Tobuli\Importers\ImportManager;

class TaskImportManager extends ImportManager
{
    protected function getReadersList(): array
    {
        return [
            'xls' => Readers\TaskExcelReader::class,
            'xlsx' => Readers\TaskExcelReader::class,
            'csv' => Readers\TaskCsvReader::class,
        ];
    }

    public function getImporter(): Importer
    {
        return new TaskImporter();
    }
}
