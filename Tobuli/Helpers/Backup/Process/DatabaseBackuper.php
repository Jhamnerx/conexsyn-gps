<?php

namespace Tobuli\Helpers\Backup\Process;

use Tobuli\Entities\BackupProcess;

class DatabaseBackuper extends AbstractBackuper
{
    protected function backup($item): bool
    {
        $command = "mysqldump"
            . " --single-transaction=TRUE"
            . " --lock-tables=false"
            . " -u '{$item['username']}'"
            . " -h '{$item['host']}'"
            . " -p'{$item['password']}'"
            . " --port={$item['port']}"
            . " --databases {$item['database']}";

        $this->uploader->process($command, $this->process, $item);

        return true;
    }

    protected function getItems(): array
    {
        return [\DB::connection($this->process->source)->getConfig()];
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