<?php

namespace Tobuli\Exporters\EntityManager\ModelChangeLog;

use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Tobuli\Entities\ModelChangeLog;
use Tobuli\Exporters\Downloader\CsvChunkDownloader;
use Tobuli\Exporters\EntityManager\ExporterInterface;

class CsvExporter extends CsvChunkDownloader implements ExporterInterface
{
    private $idxCauser;
    private $idxSubject;
    private $idxAttributesCount;

    public function generateReport(Builder $query, array $attributes, string $filename): BinaryFileResponse
    {
        return $this->download($query, $attributes, $filename);
    }

    public function generateFile(Builder $query, array $attributes, string $filename): string
    {
        $this->idxCauser = array_search('causer_name', $attributes);
        $this->idxSubject = array_search('subject_name', $attributes);
        $this->idxAttributesCount = array_search('attributes_count', $attributes);

        return parent::generateFile($query, $attributes, $filename);
    }

    /**
     * @param ModelChangeLog $item
     */
    protected function parseValues($item, array $attributes): array
    {
        $data = parent::parseValues($item, $attributes);

        if ($this->idxCauser !== false) {
            $data[$this->idxCauser] = $item->getCauserName();
        }

        if ($this->idxSubject !== false) {
            $data[$this->idxSubject] = $item->getSubjectName();
        }

        if ($this->idxAttributesCount !== false) {
            $data[$this->idxAttributesCount] = $item->attributesCount();
        }

        return $data;
    }
}