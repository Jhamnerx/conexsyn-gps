<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\PermissionException;
use App\Jobs\DevicePositionsImportJob;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Tobuli\Entities\Device;
use Tobuli\Exceptions\ValidationException;
use Tobuli\Helpers\Backup\BackupService;
use Tobuli\Helpers\Backup\Uploader\BackupFtp;

class DevicesPositionsBackupsController extends BaseController
{
    private const PAGE_SIZE = 20;

    private BackupFtp $ftp;

    public function __construct()
    {
        parent::__construct();

        $backupService = new BackupService(settings('backups'));

        $canRestore = Cache::remember('backup_can_restore_positions', 60, fn () => $backupService->canRestorePositions());

        if ($canRestore === false) {
            throw new PermissionException();
        }

        $error = Cache::remember('backup_check_error', 60, function () use ($backupService) {
            try {
                $backupService->check();
            } catch (\Exception $exception) {
                return $exception->getMessage();
            }

            return '';
        });

        if ($error) {
            throw new ValidationException(['message' => $error]);
        }
    }

    public function index(int $id)
    {
        return $this->getList($id, 1);
    }

    public function table(int $id)
    {
        return $this->getList($id, request()->input('page', 1), '_table');
    }

    private function getList(int $id, int $page, string $viewPostfix = '')
    {
        $device = $this->getDevice($id);

        $folders = $this->getBackupFtp()->findBackupFolders();

        $folders = array_filter($folders, function ($folder) use ($device) {
            $pathPattern = $this->getPathPattern($folder->getName(), $device);

            return $this->getBackupFtp()->findFirstFile($pathPattern);
        });

        rsort($folders);

        $total = count($folders);
        $folders = array_slice($folders, ($page - 1) * self::PAGE_SIZE, self::PAGE_SIZE);

        $folders = (new LengthAwarePaginator($folders, $total, self::PAGE_SIZE, $page))
            ->setPath(route('admin.objects.positions_backups.table', $id));

        return view('Frontend.Devices.positions_backups' . $viewPostfix)->with(compact('id', 'folders'));
    }

    public function upload(int $id)
    {
        $device = $this->getDevice($id);

        $path = $this->loadBackupFile($device);

        dispatch(new DevicePositionsImportJob($device, $path, $this->user));

        return ['status' => 1];
    }

    public function download(int $id)
    {
        $device = $this->getDevice($id);

        $path = $this->loadBackupFile($device);

        return response()->download($path)->deleteFileAfterSend();
    }

    private function loadBackupFile(Device $device): string
    {
        $this->validate(request(), ['folder' => 'required']);

        $file = $this->getBackupFtp()->getDeviceFile($device, request('folder'));

        if ($file === null) {
            throw new ValidationException(['folder' => trans('global.not_found')]);
        }

        return $file;
    }

    private function getPathPattern(string $folder, Device $device): string
    {
        return $folder . '/' . $this->getBackupFtp()->getTraccarFilename($device->traccar) . '*';
    }

    private function getDevice(int $id): Device
    {
        $item = Device::findOrFail($id);

        $this->checkException('devices', 'view', $item);

        return $item;
    }

    private function getBackupFtp(): BackupFtp
    {
        if (isset($this->ftp)) {
            return $this->ftp;
        }

        try {
            $this->ftp = (new BackupService(settings('backups')))->ftp();
        } catch (\Exception $e) {
            throw new ValidationException($e->getMessage());
        }

        if (!$this->ftp->check()) {
            throw new ValidationException(trans('front.invalid_ftp_settings'));
        }

        return $this->ftp;
    }
}
