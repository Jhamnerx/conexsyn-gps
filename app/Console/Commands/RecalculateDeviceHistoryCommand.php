<?php

namespace App\Console\Commands;

use App\Console\ProcessManager;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Tobuli\Entities\Device;

class RecalculateDeviceHistoryCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'device_history:recalculate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recalculate device data which is related to the historic positions';

    private ProcessManager $processManager;

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->processManager = new ProcessManager('device_history:recalculate', 300, 2);

        do {
            $items = DB::table('history_recalculations')
                ->limit(10)
                ->get();

            foreach ($items as $item) {
                $id = $item->device_id;

                if (!$this->processManager->lock($id)) {
                    continue;
                }

                $exists = DB::table('history_recalculations')
                    ->where('device_id', $id)
                    ->where('date', $item->date)
                    ->delete();

                if ($exists) {
                    $device = Device::with('traccar')->find($id);

                    $this->updateValues($device, $item->date);
                }

                $this->processManager->unlock($id);
            }
        } while (!$items->isEmpty());

        return 0;
    }

    private function updateValues(Device $device, string $time): void
    {
        $positions = $device->positions();

        $posTable = $positions->toBase()->from;
        $conn = $positions->getConnection();

        $oldData = $conn->table($posTable)
            ->where('time', '>=', $time)
            ->where('valid', '>', 0)
            ->select([
                DB::raw('SUM(distance) AS distance'),
                DB::raw('MAX(id) AS id'),
            ])->first();

        if (empty($oldData->id))
            return;

        $initial = $conn->table($posTable)
            ->select(['id', 'latitude', 'longitude'])
            ->where('time', '<', $time)
            ->where('valid', '>', 0)
            ->orderBy('time', 'DESC')
            ->orderBy('id', 'DESC')
            ->first();

        $initId = $initial->id ?? 'NULL';
        $initLat = $initial->latitude ?? 'NULL';
        $initLon = $initial->longitude ?? 'NULL';

        $conn->statement("
                UPDATE $posTable pos
            INNER JOIN (  SELECT p.id,
                                 IF(
                                     @prev_id IS NULL OR (p.latitude = @prev_lat AND p.longitude = @prev_lon), 
                                     0, 
                                     IFNULL(
                                         DEGREES(ACOS(
                                             COS(RADIANS(p.latitude)) * COS(RADIANS(@prev_lat)) *
                                             COS(RADIANS(@prev_lon) - RADIANS(p.longitude)) +
                                             SIN(RADIANS(p.latitude)) * SIN(RADIANS(@prev_lat))
                                         )) * 111.045, 
                                         0
                                     )
                                 ) AS distance,
                                 @prev_id := p.id,
                                 @prev_lat := p.latitude,
                                 @prev_lon := p.longitude
                            FROM (SELECT @prev_id := $initId, @prev_lat := $initLat, @prev_lon := $initLon) vars,
                                 $posTable p
                           WHERE p.time >= ? AND p.valid > 0
                        ORDER BY p.time, p.id
                       ) t ON t.id = pos.id
                   SET pos.distance = t.distance
                 WHERE ROUND(t.distance, 5) != ROUND(pos.distance, 5)", [$time]);

        $newDistance = $conn->table($posTable)
            ->where('time', '>=', $time)
            ->where('valid', '>', 0)
            ->where('id', '<=', $oldData->id)
            ->sum('distance');

        $distanceDiff = $newDistance - $oldData->distance;

        if (!$distanceDiff) {
            return;
        }

        $device->sensors()
            ->where('type', 'odometer')
            ->where('shown_value_by', 'virtual_odometer')
            ->update([
                'value' => DB::raw("CAST(value AS DOUBLE) + $distanceDiff"),
            ]);
    }
}
