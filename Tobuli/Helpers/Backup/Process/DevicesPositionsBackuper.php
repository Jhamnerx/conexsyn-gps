<?php

namespace Tobuli\Helpers\Backup\Process;

use Exception;
use Illuminate\Database\QueryException;
use Tobuli\Entities\BackupProcess;
use Tobuli\Entities\TraccarDevice;
use Tobuli\Services\DatabaseService;

class DevicesPositionsBackuper extends AbstractBackuper
{
    private const GENERAL_ERROR_CODES = [
        1045, // Access denied for user
        2002, // Connection refused / Connection timed out
    ];

    protected int $failOnAttempt = 3;
    protected bool $attemptsPerItem = true;
    private string $cmdBase;

    /**
     * @param TraccarDevice $item
     */
    protected function backup($item): bool
    {
        $positions = $item->positions();

        try {
            $count = $positions->count();
        } catch (QueryException $e) {
            if ($e->getCode() === '42S02') { // table or view not found
                return false;
            }

            throw $e;
        }

        if ($count === 0) {
            return false;
        }

        $cmd = "$this->cmdBase " . $positions->getRelated()->getTable();

        $this->uploader->process($cmd, $this->process, $item);

        return true;
    }

    protected function getProcessedItemId($item)
    {
        return $item->id;
    }

    protected function getItems(): iterable
    {
        $this->setCmdBase();

        $this->process->updateReservation();

        while ($item = $this->getBaseQuery($this->process->source)
            ->where('id', '>', $this->process->last_item_id)
            ->orderBy('id', 'ASC')
            ->first()
        ) {
            yield $item;
        }
    }

    private function setCmdBase(): void
    {
        $databaseId = $this->process->source;

        $databaseService = new DatabaseService();

        $config = $databaseService->getDatabaseConfig($databaseId);

        if ($config === null) {
            throw new \InvalidArgumentException("Database with ID `$databaseId`");
        }

        $this->cmdBase = "mysqldump"
            . " --insert-ignore"
            . " --no-create-info"
            . " --skip-add-drop-table"
            . " -u '{$config['username']}'"
            . " -h '{$config['host']}'"
            . " -p'{$config['password']}'"
            . " --port={$config['port']}"
            . " {$config['database']}";
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder
     */
    private static function getBaseQuery(?string $databaseId)
    {
        return $databaseId
            ? TraccarDevice::where('database_id', $databaseId)
            : TraccarDevice::whereNull('database_id');
    }

    protected function getFailureResult(Exception $e): ?bool
    {
        if ($this->process->attempt < $this->failOnAttempt) {
            return null;
        }

        return $this->isItemSpecificError($e) ? false : null;
    }

    protected function isFailureTerminal(Exception $e): bool
    {
        if ($this->isItemSpecificError($e)) {
            return false;
        }

        return parent::isFailureTerminal($e);
    }

    private function isItemSpecificError(Exception $e): bool
    {
        return !in_array($e->getCode(), self::GENERAL_ERROR_CODES);
    }

    public static function makeProcess(string $source, array $options = []): BackupProcess
    {
        $total = self::getBaseQuery($source)->count();

        return new BackupProcess([
            'type'              => static::class,
            'source'            => $source,
            'options'           => $options,
            'duration_expire'   => 10 * $total,
            'last_item_id'      => 0,
            'total'             => $total,
        ]);
    }
}