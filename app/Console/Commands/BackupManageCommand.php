<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Tobuli\Entities\Backup;
use Tobuli\Helpers\Backup\BackupService;

class BackupManageCommand extends Command
{
    private const ACTION_PAUSE = 'pause';
    private const ACTION_KILL = 'kill';

    private const ITEM_ALL = 'all';

    protected $signature = 'backup:manage {action?} {item?}';

    protected $description = 'Pause or kill backup processes';

    private BackupService $backupService;

    public function __construct()
    {
        parent::__construct();

        $this->backupService = new BackupService([]);
    }

    public function handle(): void
    {
        $action = $this->getAction();
        $item = $this->getItem();

        switch ($action) {
            case self::ACTION_PAUSE:
                while (!ctype_digit($pauseLength = $this->ask('How many minutes should it be paused?')));

                $affected = $this->backupService->pause($pauseLength, null, $item);

                $this->info("Paused $affected processes");

                break;
            case self::ACTION_KILL:
                $affected = $this->backupService->kill(null, $item);

                $this->info("Killed $affected processes");

                break;
        }
    }

    private function getAction(): string
    {
        $action = $this->input->getArgument('action');

        $choices = [
            self::ACTION_PAUSE => 'Pause',
            self::ACTION_KILL => 'Kill',
        ];

        return $choices[$action] ?? $this->choice('How to turn off?', $choices);
    }

    private function getItem(): ?int
    {
        $item = $this->input->getArgument('item');

        $choices = Backup::whereHas('processes', fn (Builder $query) => $query->whereUnfinished())
            ->pluck('name', 'id')
            ->prepend('All', self::ITEM_ALL)
            ->all();

        $choice = $choices[$item] ?? $this->choice('How to turn off?', $choices);

        return $choice === self::ITEM_ALL ? null : $choice;
    }
}
