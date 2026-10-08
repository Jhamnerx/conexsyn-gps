<?php

namespace App\Console\Commands;

use App\Jobs\DatabaseImportJob;
use App\Jobs\DevicePositionsImportJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Tobuli\Entities\Device;
use Tobuli\Helpers\Backup\BackupService;
use Tobuli\Helpers\Backup\FileMeta;
use Tobuli\Helpers\Backup\Uploader\BackupFtp;

class BackupImportCommand extends Command
{
    private const PAGE_SIZE = 20;

    private const CAT_ALL = 1;
    private const CAT_MAIN = 2;
    private const CAT_DEV = 3;

    private const CAT_JOB_MAP = [
        'main' => [self::CAT_ALL, self::CAT_MAIN],
        'devices' => [self::CAT_ALL, self::CAT_DEV],
    ];

    private const CATEGORIES = [
        self::CAT_ALL => 'All',
        self::CAT_MAIN => 'Main database',
        self::CAT_DEV => 'Devices positions',
    ];

    protected $signature = 'backup:import';

    protected $description = 'Import backup';

    private BackupFtp $ftp;
    private string $folder;

    public function handle(): void
    {
        $ftpSettings = $this->getFtpSettings();

        $this->ftp = (new BackupService($ftpSettings))->ftp();

        if (!$this->ftp->check()) {
            throw new \Exception('Invalid ftp settings');
        }

        $this->getFolder();

        if ($this->ftp->findFirstFile($this->folder) === null) {
            throw new \Exception('Folder empty');
        }

        $restoreOption = $this->getRestoreOption();

        $this->restore($restoreOption);

        $this->info('Done');
    }

    private function restore(array $data): void
    {
        $category = $data['category'];
        $options = $data['options'];

        foreach (self::CAT_JOB_MAP as $key => $ignore) {
            if (in_array($category, self::CAT_JOB_MAP[$key])) {
                $method = 'restore' . Str::studly($key);

                $this->$method($options);
            }
        }
    }

    private function restoreMain(): void
    {
        $this->line('Main DB restore started');

        $file = $this->ftp->getMainDbFile($this->folder);

        dispatch_sync(new DatabaseImportJob(DB::getConfig(), $file, null));

        $this->line('Main DB successfully restored');
    }

    private function restoreDevices(array $options): void
    {
        $query = Device::query();

        if (isset($options['device_id_from'])) {
            $query->where('id', '>=', $options['device_id_from']);
        }

        if (isset($options['device_id_from'])) {
            $query->where('id', '<=', $options['device_id_to']);
        }

        $total = $query->count();
        $bar = $this->output->createProgressBar($total);

        do {
            $device = (clone $query)->where('id', '>', $device->id ?? 0)->orderBy('id')->first();

            if ($device) {
                $this->restoreDevice($device);
                $bar->advance();
            }
        } while ($device);

        $bar->finish();
    }

    private function restoreDevice(Device $device)
    {
        $file = $this->ftp->getDeviceFile($device, $this->folder);

        if ($file === null) {
            return;
        }

        dispatch_sync(new DevicePositionsImportJob($device, $file, null));

        File::delete($file);
    }

    private function getFtpSettings(): array
    {
        $settingsChoice = $this->choice('Select FTP settings', [
            1 => 'From config',
            2 => 'Custom',
        ]);

        if ($settingsChoice === 'From config') {
            return settings('backups');
        }

        $this->line('Provide FTP settings');

        return [
            'ftp_server' => $this->ask('Server'),
            'ftp_username' => $this->ask('Username'),
            'ftp_password' => $this->ask('Password'),
            'ftp_port' => $this->ask('Port', 21),
            'ftp_path' => $this->ask('Base path', '/'),
        ];
    }

    private function getFolder(): void
    {
        $choice = 'n';
        $page = 0;
        $items = $this->ftp->findBackupFolders();

        if (empty($items)) {
            throw new \RuntimeException('No backups found');
        }

        $items = array_map(fn (FileMeta $item) => $item->getName(), $items);

        usort($items, fn ($a, $b) => strcmp($b, $a));

        while (!is_numeric($choice)) {
            $pageItems = array_slice($items, $page * self::PAGE_SIZE, self::PAGE_SIZE);

            if ($page * self::PAGE_SIZE + count($pageItems) < count($items)) {
                $pageItems['n'] = 'Next';
            }

            if ($page) {
                $pageItems['p'] = 'Previous';
            }

            $choice = $this->choice('Choose backup folder', $pageItems);

            // inconsistent return behavior in different PHP versions
            if (!isset($pageItems[$choice])) {
                $choice = array_search($choice, $pageItems, true);
            }

            if ($choice === 'n') {
                $page++;
            } elseif ($choice === 'p') {
                $page--;
            }
        }

        $this->folder = $items[$page * self::PAGE_SIZE + $choice];
    }

    private function getRestoreOption(): array
    {
        $category = $this->choice('Select what to restore', self::CATEGORIES);

        $choice = [
            'category' => array_search($category, self::CATEGORIES),
            'options' => [],
        ];

        if ($choice['category'] === self::CAT_DEV && $this->confirm('Apply filters?')) {
            $choice['options']['device_id_from'] = $this->ask('From ID');
            $choice['options']['device_id_to'] = $this->ask('To ID');
        }

        return $choice;
    }
}
