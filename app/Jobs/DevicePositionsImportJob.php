<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Tobuli\Entities\Device;
use Tobuli\Entities\User;
use Tobuli\Services\DatabaseService;

class DevicePositionsImportJob implements ShouldQueue
{
    use InteractsWithQueue, SerializesModels;

    public Device $device;
    public string $file;
    public ?User $actor;

    public function __construct(Device $device, string $file, ?User $actor)
    {
        $this->device = $device;
        $this->file = $file;
        $this->actor = $actor;
    }

    public function handle(): void
    {
        $config = $this->device->positions()->getConnection()->getConfig();

        $this->import($config);
    }

    private function import(array $config, int $attempt = 1): void
    {
        try {
            dispatch_sync(new DatabaseImportJob($config, $this->file, $this->actor));
        } catch (ProcessFailedException $e) {
            if ($attempt > 3) {
                throw $e;
            }

            if ($this->shouldRepeatProcess($e)) {
                $this->import($config, ++$attempt);
            } else {
                throw $e;
            }
        }
    }

    protected function shouldRepeatProcess(ProcessFailedException $e): bool
    {
        $device = $this->device;

        // Table doesn't exists
        if (str_contains($e->getMessage(), 'ERROR 1146')) {
            $device->createPositionsTable();

            return true;
        }

        // Column count doesn't match value count
        if (str_contains($e->getMessage(), 'ERROR 1136')) {
            $table = "positions_{$device->traccar->id}";
            $dbName = DatabaseService::instance()->getDatabaseName($device->traccar->database_id);
            $schema = Schema::connection($dbName);

            $hasDeviceId = $schema->hasColumn($table, 'device_id');
            $hasPower = $schema->hasColumn($table, 'power');

            if ($hasDeviceId && $hasPower) {
                $schema->table($table, function (Blueprint $table) {
                    $table->dropColumn('device_id');
                });

                return true;
            }

            if (!$hasDeviceId && $hasPower) {
                $schema->table($table, function (Blueprint $table) {
                    $table->dropColumn('power');
                });

                return true;
            }

            if (!$hasDeviceId && !$hasPower) {
                $schema->table($table, function (Blueprint $table) {
                    $table->integer('device_id')->nullable()->after('id');
                    $table->double('power')->nullable()->after('other');
                });

                return true;
            }
        }

        return false;
    }
}
