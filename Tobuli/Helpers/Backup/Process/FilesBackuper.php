<?php

namespace Tobuli\Helpers\Backup\Process;

use Tobuli\Entities\BackupProcess;

class FilesBackuper extends AbstractBackuper
{
    protected function backup($item): bool
    {
        $command = 'tar -cv ' . $item;

        $this->uploader->process($command, $this->process, $item);

        return true;
    }

    protected function getItems(): iterable
    {
        return [$this->process->source];
    }

    public static function makeProcess(string $source, array $options = []): BackupProcess
    {
        return new BackupProcess([
            'type'              => static::class,
            'source'            => $source,
            'options'           => $options,
            'duration_expire'   => 3600,
            'total'             => 1,
        ]);
    }
}