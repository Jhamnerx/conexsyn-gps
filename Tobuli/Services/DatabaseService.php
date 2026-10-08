<?php

namespace Tobuli\Services;

use App\Jobs\CacheShellCommandResultJob;
use Illuminate\Database\Connection;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as BaseCollection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tobuli\Entities\Database;
use Tobuli\Entities\User;


class DatabaseService
{
    const DEFAULT_CONNECTION = 'traccar_mysql';
    const MAIN_CONNECTION = 'mysql';

    public static function instance() : self
    {
        return new static();
    }

    public static function loadDatabaseConfig()
    {
        Cache::store('array')->remember('device.position.databases', 60, function() {
            $databases = Database::all();

            foreach ($databases as $database)
                config()->set("database.connections." . self::toName($database->id), $database->toArray());

            return $databases;
        });
    }

    protected static function toName($database_id) : string
    {
        return "database{$database_id}";
    }

    public static function toId(string $databaseName): int
    {
        return $databaseName === self::DEFAULT_CONNECTION ? 0 : str_replace('database', '', $databaseName);
    }

    public function getConnection($database_id) : Connection
    {
        return DB::connection($this->getDatabaseName($database_id));
    }

    public function getDatabaseName($database_id) : string
    {
        return $database_id && $this->getDatabaseConfig($database_id)
            ? self::toName($database_id)
            : self::DEFAULT_CONNECTION;
    }

    public function getDatabaseConfig($database_id): ?array
    {
        if (empty($database_id))
            return config("database.connections." . self::DEFAULT_CONNECTION);

        self::loadDatabaseConfig();

        return config("database.connections." . self::toName($database_id));
    }

    public function getDatabases() : Collection
    {
        $config = config("database.connections." . self::DEFAULT_CONNECTION);
        $default = new Database();
        $default->id = 0;
        $default->active = false;
        $default->driver = $config['driver'];
        $default->host = $config['host'];
        $default->port = $config['port'];
        $default->username = $config['username'];
        $default->password = $config['password'];
        $default->database = $config['database'];

        return Database::all()->prepend($default);
    }

    public function getActiveDatabaseId()
    {
        $actives = Cache::store('array')->remember('device.position.active_databases', 60, function() {
            return Database::where('active', 1)->get();
        });

        if ($actives->isEmpty())
            return null;

        return $actives->random()->first()->id;
    }

    public function getUserActiveDatabaseId(User $user): ?int
    {
        $id = $user->id;

        $databases = Cache::store('array')->remember("user.$id.active_databases", 60, function () use ($id) {
            return DB::table('user_database_pivot')->where('user_id', $id)->pluck('database_id');
        });

        if ($databases->isEmpty()) {
            return null;
        }

        return $databases->random();
    }

    public function getDatabaseSizes($database_id): ?BaseCollection
    {
        $connection = $this->getConnection($database_id);

        try {
            return self::getSpaceUsageTableData($connection, ['total', 'free', 'reserved'])->sortBy('id');
        } catch (\Exception $e) {
            return null;
        }
    }

    public function getTotalSize(): int
    {
        $size = $this->getMainTotalSize();

        foreach ($this->getDatabases() as $database) {
            if (empty($database->id))
                continue;

            $connection = $this->getConnection($database->id);

            $size += self::getSpaceUsageTableData($connection, ['used'])->sum('value');
        }

        return $size;
    }

    public function getReservedSize(): int
    {
        $size = $this->getMainReservedSize();

        foreach ($this->getDatabases() as $database) {
            if (empty($database->id))
                continue;

            $connection = $this->getConnection($database->id);

            $size += self::getSpaceUsageTableData($connection, ['reserved'])->sum('value');
        }

        return $size;
    }

    protected function getMainTotalSize(): int
    {
        $size = 0;

        $main = DB::connection(self::MAIN_CONNECTION);
        $positions = DB::connection(self::DEFAULT_CONNECTION);

        if (self::isLocal($main)) {
            $size += $this->getDataDirSize($main);
        } else {
            $size += $this->getConnectionDataSize($main);
            $size += $this->getConnectionDataSize($positions);
            $size += $this->getConnectionReservedSize($main);
        }

        return $size;
    }

    protected function getMainReservedSize(): ?int
    {
        $main = DB::connection(self::MAIN_CONNECTION);

        return $this->getConnectionReservedSize($main);
    }

    protected function getConnectionReservedSize(Connection $connection): ?int
    {
        $key = md5(json_encode($connection->getConfig()));

        return Cache::remember("db.reserved.size.$key", 60, function () use ($connection) {
            $database = $connection->getDatabaseName();

            $sql = "SELECT MAX(data_free) AS total FROM information_schema.tables WHERE table_schema = '$database'";

            $result = $connection->select(DB::raw($sql));

            return $result[0]->total ?? null;
        });
    }

    protected function getConnectionDataSize(Connection $connection): ?int
    {
        $key = md5(json_encode($connection->getConfig()));

        return Cache::remember("db.data.size.$key", 60, function () use ($connection) {
            $database = $connection->getDatabaseName();

            $sql = "SELECT SUM(data_length + index_length) AS total FROM information_schema.tables WHERE table_schema = '$database'";

            $result = $connection->select(DB::raw($sql));

            return $result[0]->total ?? null;
        });
    }

    protected function getDataDir(Connection $connection) : ?string
    {
        $results = $connection->select(
            $connection->raw('SHOW VARIABLES WHERE Variable_name = "datadir"')
        );

        if (empty($results))
            return null;

        return $results[0]->Value ?? null;
    }

    protected function getDataDirSize(Connection $connection): ?int
    {
        if (!self::isLocal($connection))
            return null;

        $dir = $this->getDataDir($connection);

        if (!$dir) {
            return null;
        }

        $cacheKey = 'data_dir_size_' . $this->getConnectionKey($connection);
        $cacheResult = CacheShellCommandResultJob::getResult($cacheKey);

        if ($cacheResult !== null) {
            return $cacheResult;
        }

        dispatch(new CacheShellCommandResultJob("du -msh -B1 $dir | cut -f1", $cacheKey, 30));

        $execTime = 0;

        while ($execTime < 10 && !CacheShellCommandResultJob::hasResult($cacheKey)) {
            sleep(1);
            $execTime++;
        }

        return CacheShellCommandResultJob::getResult($cacheKey);
    }

    protected static function isCluster(Connection $connection) : bool
    {
        $results = $connection->select(
            $connection->raw('SHOW VARIABLES WHERE Variable_name = "wsrep_on"')
        );

        if (empty($results))
            return false;

        $value = $results[0]->Value ?? null;

        return $value == 'ON';
    }

    protected static function isLocal(Connection $connection) : bool
    {
        if (self::isCluster($connection))
            return false;
        
        return in_array($connection->getConfig('host'),
            [
                'localhost',
                '127.0.0.1'
            ]
        );
    }

    protected static function getSpaceUsageTableData(Connection $connection, array $keys = []): BaseCollection
    {
        return  $connection
            ->table('space_usage')
            ->when($keys, function($query) use ($keys) {
                $query->whereIn('id', $keys);
            })
            ->get();
    }

    protected function getConnectionKey(Connection $connection): string
    {
        return md5(json_encode($connection->getConfig()));
    }
}
