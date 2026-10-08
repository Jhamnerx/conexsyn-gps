<?php

namespace Tobuli\Exporters\EntityManager\ModelChangeLog;

use Tobuli\Exporters\EntityManager\AbstractExportManager;

class ExportManager extends AbstractExportManager
{
    protected function getBasename(): string
    {
        return 'model_change_logs_report_' . time();
    }
}