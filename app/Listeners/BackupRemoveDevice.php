<?php

namespace App\Listeners;

use App\Events\DeviceDeleted;
use Tobuli\Entities\BackupProcess;
use Tobuli\Helpers\Backup\Process\DevicesPositionsBackuper;

class BackupRemoveDevice
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
     * @param  DeviceDeleted  $event
     * @return void
     */
    public function handle(DeviceDeleted $event)
    {
        BackupProcess::whereUnexpired()
            ->whereNull('completed_at')
            ->where('type', DevicesPositionsBackuper::class)
            ->where('source', (int)$event->device->database_id)
            ->where('last_item_id', '<', $event->device->id)
            ->update([
                'total' => \DB::raw('total - 1'),
            ]);
    }
}
