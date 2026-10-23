<?php

namespace App\Console\Commands;

use App\Services\IccidCollector;
use Illuminate\Console\Command;
use Tobuli\Entities\Device;

class IccidCollectCommand extends Command
{
    protected $signature = 'iccid:collect';

    protected $description = 'Recolecta ICCIDs de dispositivos GT06 desde los parámetros almacenados en traccar.';

    public function handle(): int
    {
        $collector = new IccidCollector();

        $total    = 0;
        $found    = 0;
        $added    = 0;

        Device::whereHas('traccar', function ($q) {
            $q->where('protocol', 'gt06');
        })->with('traccar')->chunkById(200, function ($devices) use ($collector, &$total, &$found, &$added) {
            foreach ($devices as $device) {
                $total++;

                $iccid = $device->getParameter('iccid');

                if (empty($iccid)) {
                    continue;
                }

                $found++;

                if ($collector->add($device->name, $device->imei, (string)$iccid)) {
                    $added++;
                    $this->line("Guardado: {$device->name} | {$device->imei} | {$iccid}");
                }
            }
        });

        $this->info("Dispositivos GT06 procesados : {$total}");
        $this->info("Con ICCID en parámetros      : {$found}");
        $this->info("Entradas nuevas agregadas     : {$added}");

        return 0;
    }
}
