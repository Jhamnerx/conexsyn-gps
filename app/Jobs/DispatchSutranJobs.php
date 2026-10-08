<?php

namespace App\Jobs;

use App\Services\Integrations\IntegrationLogger;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;
use Tobuli\Entities\Sutran;

/**
 * Reparte las posiciones pendientes de SUTRAN en lotes (SendDataSutranBatch).
 *
 * Se programa cada minuto desde el Kernel. ShouldBeUnique evita que se acumulen
 * instancias si la cola va lenta; `queued_at` evita despachar dos veces la misma fila.
 */
class DispatchSutranJobs implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 1;

    public $timeout = 120;

    public $uniqueFor = 300;

    public function __construct()
    {
        $this->onQueue(config('integrations.sutran.queue'));
    }

    public function handle()
    {
        if (! config('integrations.sutran.enabled')) {
            return;
        }

        $log = IntegrationLogger::sutran()->withContext(['job' => 'DispatchSutranJobs']);

        $chunk = (int) config('integrations.sutran.job_chunk', 2000);
        $ttl = (int) config('integrations.sutran.queued_ttl_minutes', 30);

        $batches = 0;
        $rows = 0;

        try {
            Sutran::readyToDispatch($ttl)
                ->select('id')
                ->orderBy('id')
                ->chunkById($chunk, function ($items) use (&$batches, &$rows) {
                    $ids = $items->pluck('id')->all();

                    Sutran::whereIn('id', $ids)->update(['queued_at' => now()]);

                    SendDataSutranBatch::dispatch($ids);

                    $batches++;
                    $rows += count($ids);
                });
        } catch (Throwable $e) {
            $log->exception('Error al despachar lotes', $e, ['batches' => $batches, 'rows' => $rows]);

            throw $e;
        }

        if ($rows) {
            $log->info("Despachadas {$rows} posiciones en {$batches} lote(s)");
        }
    }
}
