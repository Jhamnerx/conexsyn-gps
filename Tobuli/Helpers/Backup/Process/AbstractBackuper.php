<?php

namespace Tobuli\Helpers\Backup\Process;

use Exception;
use Tobuli\Entities\BackupProcess;
use Tobuli\Helpers\Backup\Uploader\BackupUploaderInterface;

abstract class AbstractBackuper
{
    protected BackupProcess $process;
    protected BackupUploaderInterface $uploader;
    protected int $failOnAttempt = 1; // use 0 to never fail
    protected bool $attemptsPerItem = false;

    public function __construct(BackupProcess $process, BackupUploaderInterface $uploader)
    {
        $this->process = $process;
        $this->uploader = $uploader;
    }

    public function run()
    {
        $this->setAttempt();

        $items = $this->getItems();

        foreach ($items as $item) {
            try {
                $result = $this->backup($item);
            } catch (Exception $e) {
                $result = $this->handleFailure($e);

                if ($result === null) {
                    throw $e;
                }
            }

            $result
                ? $this->completeItem($item)
                : $this->skipItem($item);
        }

        $this->setCompleted();
    }

    protected function setCompleted(): void
    {
        $this->process->completed_at = date('Y-m-d H:i:s');
        $this->process->save();
    }

    protected function setFailed(): void
    {
        $this->process->failed_at = date('Y-m-d H:i:s');
        $this->process->save();
    }

    protected function setAttempt(): void
    {
        $this->process->attempt += 1;
        $this->process->save();
    }

    protected function handleFailure(Exception $e): ?bool
    {
        if ($this->isFailureTerminal($e)) {
            $this->setFailed();
        }

        return $this->getFailureResult($e);
    }

    protected function getFailureResult(Exception $e): ?bool
    {
        return null;
    }

    protected function isFailureTerminal(Exception $e): bool
    {
        return $this->failOnAttempt && $this->process->attempt >= $this->failOnAttempt;
    }

    protected function skipItem($item): void
    {
        $this->process->skipped = $this->process->skipped + 1;
        $this->incrementBackupCount($item);
    }

    protected function completeItem($item): void
    {
        $this->process->processed = $this->process->processed + 1;
        $this->incrementBackupCount($item);
    }

    protected function incrementBackupCount($item): void
    {
        if ($this->attemptsPerItem) {
            $this->process->attempt = 1;
        }

        $this->process->last_item_id = $this->getProcessedItemId($item);
        $this->process->reserved_at = date('Y-m-d H:i:s');
        $this->process->save();
    }

    protected function getProcessedItemId($item)
    {
        return $item;
    }

    abstract protected function backup($item): bool;

    abstract protected function getItems(): iterable;

    abstract public static function makeProcess(string $source, array $options = []): BackupProcess;
}