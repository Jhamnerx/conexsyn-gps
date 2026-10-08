<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Tobuli\Entities\Device;

class PositionsCsvImportCommand extends Command
{
    private const TYPE_START = 'start';
    private const TYPE_END = 'end';

    protected $signature = 'csv:positions';

    protected $description = 'Command description';

    private Repository $cache;
    private string $sourceFile;
    private array $indexes;

    public function __construct()
    {
        parent::__construct();

        $this->cache = Cache::store('array');
        $this->sourceFile = storage_path('custom/positions_import.csv');
    }

    public function handle(): void
    {
        $handle = $this->getSourceFileHandle();

        if ($handle === false) {
            $this->error('Could not open ' . $this->sourceFile);
            return;
        }

        $success = $this->setIndexes($handle);

        if ($success === false) {
            $this->error('Invalid file header line');
            return;
        }

        $this->importFile($handle);
    }

    /**
     * @param resource $fileHandle
     */
    private function importFile($fileHandle): void
    {
        $source = $this->getDataSource($fileHandle);

        foreach ($source as $row) {
            $plateNumber = $row[$this->indexes['plate_number']];

            $device = $this->getDeviceByPlateNumber($plateNumber);

            if ($device === null) {
                $this->error("Device with plate number `$plateNumber` not found");
                continue;
            }

            $positionStart = $this->getPositionData($row, self::TYPE_START);
            $positionEnd = $this->getPositionData($row, self::TYPE_END);

            try {
                $device->traccar->positions()->createMany([$positionStart, $positionEnd]);
            } catch (\Exception $e) {
                if ($e->getCode() == '42S02') {
                    $device->createPositionsTable();
                    $device->traccar->positions()->createMany([$positionStart, $positionEnd]);
                } else {
                    throw $e;
                }
            }


        }
    }

    private function getPositionData(array $input, string $type): array
    {
        $keyPosition = "{$type}_position";
        $keyTime = "travel_{$type}_time_local";

        [$lat, $lng] = explode(' ', $input[$this->indexes[$keyPosition]]);

        $time = Carbon::parse($input[$this->indexes[$keyTime]])->format('Y-m-d H:i:s');

        return [
            'latitude'      => $lat,
            'longitude'     => $lng,
            'time'          => $time,
            'server_time'   => $time,
            'device_time'   => $time,
        ];
    }

    private function getDeviceByPlateNumber($plateNumber): ?Device
    {
        $key = "positions_csv.device.$plateNumber";

        if ($this->cache->has($key)) {
            return $this->cache->get($key);
        }

        $device = Device::firstWhere('plate_number', $plateNumber);

        $this->cache->put($key, $device);

        return $device;
    }

    /**
     * @param resource $fileHandle
     */
    private function getDataSource($fileHandle): \Generator
    {
        while (($line = fgetcsv($fileHandle)) !== false) {
            yield $line;
        }

        fclose($fileHandle);
    }

    /**
     * @param resource $fileHandle
     */
    private function setIndexes($fileHandle): bool
    {
        $headers = fgetcsv($fileHandle);

        if (!$headers) {
            return false;
        }

        $fields = [
            self::TYPE_START . '_position',
            'travel_' . self::TYPE_START . '_time_local',
            self::TYPE_END . '_position',
            'travel_' . self::TYPE_END . '_time_local',
            'plate_number',
        ];

        $this->indexes = array_flip(array_filter($headers, fn ($value) => in_array($value, $fields)));

        return count($this->indexes) === count($fields);
    }

    /**
     * @return false|resource
     */
    private function getSourceFileHandle()
    {
        if (!file_exists($this->sourceFile)) {
            return false;
        }

        return fopen($this->sourceFile, 'r');
    }
}
