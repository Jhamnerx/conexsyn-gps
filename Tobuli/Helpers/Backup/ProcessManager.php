<?php

namespace Tobuli\Helpers\Backup;

use Illuminate\Database\Eloquent\Builder;
use Tobuli\Entities\Backup;
use Tobuli\Entities\BackupProcess;
use Tobuli\Helpers\Backup\Process\AbstractBackuper;
use Tobuli\Helpers\Backup\Uploader\BackupFtp;

class ProcessManager
{
    private BackupFtp $ftp;
    private Backup $backup;

    public function __construct(BackupFtp $ftp)
    {
        $this->ftp = $ftp;
    }

    public function handle(): void
    {
        $processes = $this->backup->processes()->get();

        foreach ($processes as $process) {
            if ($this->isProcessRunnable($process) === false) {
                continue;
            }

            try {
                $this->resolveBackuper($process, $this->ftp)->run();
            } catch (\Throwable $e) {
                $this->backup->update([
                    'message' => (new ErrorTranslator())->translate($e),
                    'details' => $e->getCode() . ': ' . $e->getMessage(),
                ]);

                throw $e;
            }
        }

        if ($this->backup->isCompleted()) {
            $this->backup->update(['message' => trans('front.successfully_uploaded')]);
        }
    }

    private function isProcessRunnable(BackupProcess $process): bool
    {
        if ($process->isRunnable() === false) {
            return false;
        }

        $now = date('Y-m-d H:i:s');

        return (bool)BackupProcess::where('id', $process->id)
            ->where(fn (Builder $query) => $query
                ->where('reserved_at', $process->reserved_at)
                ->orWhereNull('reserved_at')
            )
            ->toBase()
            ->update(['reserved_at' => $now]);
    }

    public static function resolveBackuper(BackupProcess $process, BackupFtp $ftp): AbstractBackuper
    {
        return new $process->type($process, $ftp);
    }

    public function setBackup(Backup $backup): self
    {
        $this->backup = $backup;

        return $this;
    }
}