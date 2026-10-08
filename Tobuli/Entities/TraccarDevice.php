<?php namespace Tobuli\Entities;

use App\Jobs\DatabaseImportJob;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use Tobuli\Relations\HasManyTable;
use Tobuli\Services\DatabaseService;

class TraccarDevice extends AbstractEntity {
    const MODE_ONLY_NEW = 1;
    const MODE_OVERWRITE = 2;
    const MODE_APPEND = 3;

	protected $table = 'traccar_devices';

    protected $fillable = array(
        'database_id',
        'name',
        'uniqueId',
        'latestPosition_id',
        'lastValidLatitude',
        'lastValidLongitude',
        'device_time',
        'server_time',
        'ack_time',
        'time',
        'speed',
        'other',
        'altitude',
        'power',
        'course',
        'address',
        'protocol',
        'latest_positions'
    );

    public $timestamps = false;

    public function positions()
    {
        $instance = new TraccarPosition();

        if ($connection = $this->getDatabaseName())
            $instance->setConnection($connection);

        return new HasManyTable($instance->newQuery(), $this, $instance->getTable() . "_" . $this->id);
    }

    public function getDatabaseName()
    {
        return DatabaseService::instance()->getDatabaseName($this->database_id);
    }

    public function copyTo($database_id, $mode = null)
    {
        $from = DatabaseService::instance()->getDatabaseConfig($this->database_id);
        $to   = DatabaseService::instance()->getDatabaseConfig($database_id);
        $table = "positions_{$this->id}";

        $schema = Schema::connection(DatabaseService::instance()->getDatabaseName($this->database_id));
        if ($schema->hasColumn($table, 'device_id')) {
            $schema->table($table, function($t) use ($table) {
                $t->dropColumn('device_id');
                $t->dropColumn('power');
            });
        }

        switch ($mode) {
            case self::MODE_OVERWRITE:
                $options = '';
                break;

            case self::MODE_APPEND:
                $schema = Schema::connection(DatabaseService::instance()->getDatabaseName($database_id));
                $hasTable = $schema->hasTable($table);

                if ($hasTable && $schema->hasColumn($table, 'device_id')) {
                    $schema->table($table, function($t) use ($table) {
                        $t->dropColumn('device_id');
                        $t->dropColumn('power');
                    });
                }

                if ($hasTable) {
                    $options = '--insert-ignore --no-create-info';
                } else {
                    $options = '--insert-ignore --skip-add-drop-table';
                }

                break;

            default:
                $options = '--insert-ignore --skip-add-drop-table';

        }

        $command = implode(' | ', [
            "mysqldump -h {$from['host']} -u '{$from['username']}' -p'{$from['password']}' --port={$from['port']} $options {$from['database']} $table",
            "mysql -h {$to['host']} -u '{$to['username']}' -p'{$to['password']}' --port={$to['port']} {$to['database']}"
        ]);

        $process = Process::fromShellCommandline($command);
        $process->setTimeout(0);
        $process->run();
        $process->wait();

        if ( ! $process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        $connection = $this->getDatabaseName();

        if (Schema::connection($connection)->hasTable($table)) {
            DB::connection($connection)->table($table)->truncate();
            Schema::connection($connection)->dropIfExists($table);
        }

        $this->database_id = $database_id;
        $this->save();
    }

    public function getLastConnectionAttribute()
    {
        $timestamp = $this->lastConnectTimestamp;

        if ( ! $timestamp)
            return null;

        return Carbon::createFromTimestamp($timestamp);
    }

    public function getLastConnectTimestampAttribute() {
        return strtotime($this->server_time);
    }
}
