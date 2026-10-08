<?php

namespace Tobuli\Helpers\Backup;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Tobuli\Entities\Backup;
use Tobuli\Entities\BackupProcess;
use Tobuli\Helpers\Backup\Exception\InvalidFtpConfigException;
use Tobuli\Helpers\Backup\Exception\NoHiveConfigException;
use Tobuli\Helpers\Backup\Process\DatabaseBackuper;
use Tobuli\Helpers\Backup\Process\DevicesPositionsBackuper;
use Tobuli\Helpers\Backup\Process\FilesBackuper;
use Tobuli\Helpers\Backup\Uploader\BackupFtp;
use Tobuli\Helpers\Hive;
use Tobuli\Services\DatabaseService;

class BackupService
{
    private const LAUNCHER_AUTO = 'auto';
    private const LAUNCHER_FORCE = 'force';

    protected array $settings;
    protected BackupFtp $ftp;
    protected Hive $hive;

    public function __construct(array $settings)
    {
        $this->hive = new Hive();

        $this->settings = $settings;
    }

    /**
     * @throws InvalidFtpConfigException
     * @throws NoHiveConfigException
     */
    public function setupFtp(): void
    {
        $settings = $this->settings;

        if ($this->isAutoSettings()) {
            $hiveSettings = $this->hive->getBackupServer();

            if (!$hiveSettings) {
                throw new NoHiveConfigException();
            }

            $settings = array_merge($this->settings, $hiveSettings);
        }

        if (empty($settings['ftp_server'])) {
            throw new InvalidFtpConfigException();
        }

        $this->ftp = new BackupFtp(
            $settings['ftp_server'],
            $settings['ftp_username'],
            $settings['ftp_password'],
            $settings['ftp_port'],
            $settings['ftp_path']
        );
    }

    public function canRestorePositions(): bool
    {
        return !$this->isAutoSettings() || $this->hive->hasBackupServer();
    }

    public function hasHiveCredentials(): bool
    {
        return $this->isAutoSettings() && $this->hive->hasBackupServer();
    }

    private function isAutoSettings(): bool
    {
        return isset($this->settings['type']) && $this->settings['type'] === 'auto';
    }

    public function auto(): bool
    {
        if (!$this->canStartAutoBackup()) {
            return false;
        }

        try {
            $this->setupFtp();
        } catch (NoHiveConfigException | InvalidFtpConfigException $e) {
            $this->setNextBackup();
            return false;
        }

        $backup = $this->getCurrentAutoBackup();

        if (!$backup) {
            $this->setNextBackup();
            $backup = $this->createBackup(self::LAUNCHER_AUTO);
        }

        $processManager = new ProcessManager($this->ftp);

        try {
            $processManager->setBackup($backup)->handle();
        } catch (\Exception $e) {
            if (Arr::get($this->settings, 'type') == 'auto') {
                $hive = new Hive();
                $hive->backupServerError([
                    'code' => $e->getCode(),
                    'error' => $e->getMessage(),
                ]);
            }

            return false;
        } finally {
            $this->writeSettings();
        }

        return true;
    }

    private function canStartAutoBackup(): bool
    {
        $backup = $this->getCurrentAutoBackup();

        if (!$backup && !$this->isTimeDueForBackup()) {
            return false;
        }

        if ($backup && $backup->isCompleted()) {
            return false;
        }

        return true;
    }

    private function isTimeDueForBackup(): bool
    {
        if (!isset($this->settings['next_backup'])) {
            return true;
        }

        if (time() > $this->settings['next_backup']) {
            return true;
        }

        return false;
    }

    private function getCurrentAutoBackup(): ?Backup
    {
        if (empty($this->settings['next_backup'])) {
            return null;
        }

        $backup = Backup::where('launcher', self::LAUNCHER_AUTO)
            ->orderBy('created_at', 'DESC')
            ->first();

        if ($backup && $backup->processes()->whereUnfinished()->count()) {
            return $backup;
        }

        return Backup::where('launcher', self::LAUNCHER_AUTO)
            ->where('created_at', '>', date('Y-m-d H:i:s', $this->settings['next_backup']))
            ->first();
    }

    private function createBackup(string $launcher): Backup
    {
        $backup = new Backup([
            'name' => date('Y-m-d') . '-' . time(),
            'launcher' => $launcher,
        ]);
        $backup->save();

        $dbService = new DatabaseService();
        $dbs = $dbService->getDatabases()->pluck('id')->all();

        /** @var BackupProcess[] $data */
        $data = array_map(fn ($id) => DevicesPositionsBackuper::makeProcess($id), $dbs);

        array_unshift($data, FilesBackuper::makeProcess(images_path()));
        array_unshift($data, DatabaseBackuper::makeProcess(\DB::connection()->getName()));

        $durationExpire = array_sum(array_column($data, 'duration_expire'));

        foreach ($data as $process) {
            $process->duration_expire = $durationExpire;
        }

        $backup->processes()->saveMany($data);

        return $backup;
    }

    public function force()
    {
        if (!$this->ftp()->check()) {
            throw new \Exception(trans('front.login_failed'));
        }

        $this->kill(self::LAUNCHER_FORCE);

        $backup = $this->createBackup(self::LAUNCHER_FORCE);

        (new ProcessManager($this->ftp))
            ->setBackup($backup)
            ->handle();
    }

    public function kill(?string $launcher = null, ?int $backupId = null): int
    {
        $this->killCurrentProcesses();

        $updated = 0;

        DB::transaction(function () use ($launcher, $backupId, &$updated) {
            $updated = $this->getUpdatableProcesses($launcher, $backupId)->update([
                'failed_at' => date('Y-m-d H:i:s')
            ]);

            if ($updated) {
                Backup::when($backupId, fn (Builder $query) => $query->where('id', $backupId))
                    ->when($launcher, fn (Builder $query) => $query->where('launcher', $launcher))
                    ->update(['message' => 'Killed']);
            }
        });

        return $updated;
    }

    public function pause(int $minutes, ?string $launcher = null, ?int $backupId = null): int
    {
        $this->killCurrentProcesses();

        $date = date('Y-m-d H:i:s', time() + ($minutes * 60));

        return $this->getUpdatableProcesses($launcher, $backupId)->update([
            'reserved_at' => $date
        ]);
    }

    private function getUpdatableProcesses(?string $launcher = null, ?int $backupId = null): Builder
    {
        $query = BackupProcess::whereUnfinished();

        if ($launcher || $backupId) {
            $query->whereHas('backup', fn (Builder $query) => $query
                ->when($launcher, fn (Builder $query) => $query->where('launcher', $launcher))
                ->when($backupId, fn (Builder $query) => $query->where('id', $backupId))
            );
        }

        return $query;
    }

    public function check()
    {
        if ($this->hasHiveCredentials()) {
            return;
        }

        if (!$this->ftp()->check()) {
            throw new \Exception(trans('front.login_failed'));
        }

        try {
            $this->ftp()->testCommand();
        } catch (\Exception $e) {
            throw new \Exception(trans('front.unexpected_error'));
        }
    }

    protected function writeSettings($retry = 0)
    {
        try {
            settings('backups', $this->settings);
        } catch (\Exception $e) {
            if ($retry > 3) {
                throw $e;
            }

            sleep(30);
            $this->writeSettings(++$retry);
        }
    }

    protected function setNextBackup()
    {
        $this->settings['next_backup'] = strtotime(
            date('Y-m-d', strtotime('+' . $this->settings['period'] . ' days'))
            . ' '
            . $this->settings['hour']
        );

        settings('backups.next_backup', $this->settings['next_backup']);
    }

    /**
     * @throws InvalidFtpConfigException
     * @throws NoHiveConfigException
     */
    public function ftp(): BackupFtp
    {
        if (!isset($this->ftp)) {
            $this->setupFtp();
        }

        return $this->ftp;
    }

    protected function killCurrentProcesses(): void
    {
        (new \App\Console\ProcessManager('backup:mysql'))->killProcesses();
    }
}