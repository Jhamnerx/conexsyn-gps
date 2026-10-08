<?php

namespace App\Listeners;

use App\Events\DeviceCreated;
use Tobuli\Entities\BackupProcess;
use Tobuli\Helpers\Backup\Process\DevicesPositionsBackuper;

class BackupAddDevice
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  DeviceCreated  $event
     * @return void
     */
    public function handle(DeviceCreated $event)
    {
        BackupProcess::whereUnexpired()
            ->whereNull('completed_at')
            ->where('type', DevicesPositionsBackuper::class)
            ->where('source', (int)$event->device->database_id)
            ->update([
                'total' => \DB::raw('total + 1'),
            ]);
    }
}
