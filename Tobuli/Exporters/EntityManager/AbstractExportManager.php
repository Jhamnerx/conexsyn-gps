<?php

namespace Tobuli\Exporters\EntityManager;

use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

abstract class AbstractExportManager
{
    protected Builder $query;

    public function __construct(Builder $query)
    {
        set_time_limit(600);
        $this->query = $query;
    }

    public function download(array $attributes, string $format, array $attributeHeaders = []): BinaryFileResponse
    {
        $filename = $this->getBasename() . '.' . $format;
        $exporter = $this->resolveExporter($format);

        if ($exporter instanceof HeaderContainingInterface) {
            $exporter->setAttributeHeaders($attributeHeaders);
        }

        return $exporter->generateReport($this->query, $attributes, $filename)
            ->deleteFileAfterSend(true);
    }

    protected function resolveExporter(string $format): ExporterInterface
    {
        $namespace = (new \ReflectionClass(get_class($this)))->getNamespaceName();
        $exporterClass = $namespace . '\\' . ucfirst($format) . 'Exporter';

        if (!class_exists($exporterClass, true)) {
            $excelExtensions = config('excel.extension_detector');

            if (isset($excelExtensions[$format])) {
                $exporterClass = $namespace . '\\ExcelExporter';
            }

            if (!class_exists($exporterClass, true)) {
                throw new \LogicException('Format exporter class not found!');
            }
        }

        return new $exporterClass();
    }

    abstract protected function getBasename(): string;
}