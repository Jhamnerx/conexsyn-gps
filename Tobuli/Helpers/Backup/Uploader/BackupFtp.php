<?php

namespace Tobuli\Helpers\Backup\Uploader;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use Tobuli\Clients\FtpHandler;
use Tobuli\Entities\Backup;
use Tobuli\Entities\BackupProcess;
use Tobuli\Entities\Device;
use Tobuli\Entities\TraccarDevice;
use Tobuli\Helpers\Backup\FileMeta;
use Tobuli\Helpers\Backup\Process\DatabaseBackuper;
use Tobuli\Helpers\Backup\Process\DevicesPositionsBackuper;
use Tobuli\Helpers\Backup\Process\FilesBackuper;

class BackupFtp implements BackupUploaderInterface
{
    protected static array $devicePositionsSubfolders;

    private FtpHandler $handler;

    public function __construct($host, $user, $pass, $port, $path)
    {
        $this->handler = new FtpHandler($host, $user, $pass, $port, rtrim($path, '/') . '/');
    }

    public function getHost()
    {
        return $this->handler->getHost();
    }

    public function check(): bool
    {
        return $this->handler->getConn() !== false;
    }

    public function testCommand()
    {
        $filename = time() . 'test.txt';
        $command = "ncftpput -m -c"
            . " -u '{$this->handler->getUser()}'"
            . " -p '{$this->handler->getPass()}'"
            . " -P {$this->handler->getPort()}"
            . " {$this->handler->getHost()} {$this->handler->getPath()}$filename";

        $this->run('echo "test"' . " | $command");
    }

    public function process($commands, BackupProcess $process, $item, bool $gzip = true)
    {
        $command = $this->buildBackupCommand($commands, $process, $item, $gzip);

        try {
            $this->run($command, $process);
        } catch (ProcessFailedException $e) {
            $backup = $process->backup;
            $cache = Cache::store('array');

            // backup root dir may not exist
            if ($e->getProcess()->getExitCode() === 14 && !$cache->has("backups.$backup->id.root_create")) {
                $cache->put("backups.$backup->id.root_create", 1);

                $cmdMakeDir = 'ncftpput -m -c ' . $this->getFtpLoginParams() . self::getRootPath($backup) . '.empty';

                $this->run($cmdMakeDir);
                $this->run($command, $process);

                return;
            }

            throw $e;
        }
    }

    protected function buildBackupCommand($commands, BackupProcess $process, $item, bool $gzip = true): string
    {
        $filename = $this->resolveItemFilename($process, $item);
        $filename = self::getRootPath($process->backup) . $filename;

        if (is_string($commands)) {
            $commands = [$commands];
        }

        if ($gzip) {
            $filename .= '.gz';
            $commands[] = 'gzip -9';
        }

        $commands[] = "ncftpput -m -c" . $this->getFtpLoginParams() . "$filename";

        return implode(' | ', $commands);
    }

    protected function getFtpLoginParams(): string
    {
        return " -u '{$this->handler->getUser()}'"
            . " -p '{$this->handler->getPass()}'"
            . " -P {$this->handler->getPort()}"
            . " {$this->handler->getHost()} {$this->handler->getPath()}";
    }

    protected function resolveItemFilename(BackupProcess $process, $item): string
    {
        switch ($process->type) {
            case FilesBackuper::class:
                return basename($item) . '.tar';
            case DatabaseBackuper::class:
                return self::getDatabaseFilename($item);
            case DevicesPositionsBackuper::class:
                return self::getTraccarFilename($item);
            default:
                throw new \InvalidArgumentException('Unsupported backup process type: ' . $process->type);
        }
    }

    protected function run(string $command, ?BackupProcess $backupProcess = null): void
    {
        $process = Process::fromShellCommandline($command);
        $process->start();

        $timer = 0;

        while ($process->isRunning()) {
            sleep(1);
            $timer++;

            if ($backupProcess && ($timer % BackupProcess::DURATION_ALIVE === 0)) {
                $backupProcess->updateReservation();
            }
        }

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }
    }

    public static function getDatabaseFilename(array $config): string
    {
        return "{$config['host']}-{$config['database']}-db.sql";
    }

    public static function getTraccarFilename(TraccarDevice $device): string
    {
        $table = $device->positions()->getRelated()->getTable();

        return self::getDevicePositionsRemoteDir($device->id) . $table . '.sql';
    }

    /**
     * if id = 312456, $path = <...>/10000000/1000000/400000/20000/3000/500/
     */
    public static function getDevicePositionsRemoteDir($id): string
    {
        $path = '';
        $dirDecimals = self::getDevicePositionsSubfolders();

        foreach ($dirDecimals as $i) {
            $path .= (ceil($id / $i) % 10 * $i) . '/';
        }

        return $path;
    }

    protected static function getDevicePositionsSubfolders(): array
    {
        if (isset(self::$devicePositionsSubfolders)) {
            return self::$devicePositionsSubfolders;
        }

        $decimal = '100';
        $dirDecimals = [];

        while (strlen($decimal) <= 10) { // avoiding 20-digit numbers, because they are stored as 1.0E+19
            array_unshift($dirDecimals, $decimal);
            $decimal .= '0';
        }

        return self::$devicePositionsSubfolders = $dirDecimals;
    }

    /**
     * @param  Backup|string  $backupDate
     */
    public static function getRootPath($backupDate): string
    {
        if ($backupDate instanceof Backup) {
            $backupDate = $backupDate->created_at;
        }

        $date = \Carbon::parse($backupDate)->format('Y-m-d');
        $timestamp = \Carbon::parse($backupDate)->timestamp;

        return "backup_$date-$timestamp/";
    }

    public function findBackupFolders(): array
    {
        $list = $this->handler->rawlist('backup_*') ?: [];

        return $this->parseRawFileList($list, $this->handler->getPath());
    }

    public function findFiles(string $pathPattern): array
    {
        $list = $this->handler->rawlist($pathPattern);

        $dir = dirname($pathPattern);

        return $this->parseRawFileList($list, $dir);
    }

    public function findFirstFile(string $pathPattern): ?FileMeta
    {
        $files = $this->findFiles($pathPattern);

        return $files[0] ?? null;
    }

    /**
     * @param string|array $rows
     * @return FileMeta[]
     */
    private function parseRawFileList($rows, string $dir): array
    {
        if ($rows === false) {
            return [];
        }

        if (is_string($rows)) {
            $rows = [$rows];
        }

        return array_map(fn ($row) => FileMeta::fromFtpRaw($row, $dir), $rows);
    }

    public function getMainDbFile(string $folder): ?string
    {
        $file = self::getDatabaseFilename(DB::getConfig());

        $pattern = $folder . '/' . $file . '*';

        return $this->downloadFileByPattern($pattern);
    }

    public function getDeviceFile(Device $device, string $folder): ?string
    {
        $pattern = $folder . '/' . $this->getTraccarFilename($device->traccar) . '*';

        return $this->downloadFileByPattern($pattern);
    }

    private function downloadFileByPattern(string $pattern): ?string
    {
        $file = $this->findFirstFile($pattern);

        if ($file === null) {
            return null;
        }

        return $this->downloadFile($file->getPath());
    }

    private function downloadFile(string $path): string
    {
        $localPath = storage_path('cache/' . basename($path));

        $this->handler->get($localPath, $path);

        return $localPath;
    }
}