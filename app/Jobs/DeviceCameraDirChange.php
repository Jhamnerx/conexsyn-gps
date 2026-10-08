<?php

namespace App\Jobs;

use App\Jobs\Job;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\File;
use Tobuli\Entities\Device;
use Tobuli\Entities\DeviceCamera;
use App\Events\DeviceCameraCreated;
use Tobuli\Services\FtpUserService;

class DeviceCameraDirChange extends Job implements ShouldQueue
{
    use InteractsWithQueue, SerializesModels;

    private $device;
    private $oldImei;
    private $ftpUserService;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Device $device, $oldImei)
    {
        $this->device = $device;
        $this->oldImei = $oldImei;
        $this->ftpUserService = new FtpUserService();
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if (empty($this->oldImei))
            return null;

        $dir = cameras_media_path($this->oldImei);

        if (!File::exists($dir))
            return null;

        rename($dir, cameras_media_path($this->device->imei));

        foreach ($this->device->deviceCameras as $camera) {
            $this->ftpUserService->changeCameraFtpUserDir($camera);
        }
    }
}
