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
use Tobuli\Entities\Mininter;

/**
 * Reparte las tramas pendientes (y las reintentables) de MININTER en lotes (SendDataMininter).
 *
 * Se programa cada minuto desde el Kernel. Marca las filas como `queued` al despacharlas
 * para que dos corridas no envíen la misma trama; las `queued` huérfanas vuelven a `pending`.
 */
class DispatchMininterJobs implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 1;

    public $timeout = 300;

    public $uniqueFor = 300;

    public function __construct()
    {
        $this->onQueue(config('integrations.mininter.queue'));
    }

    public function handle()
    {
        if (! config('integrations.mininter.enabled')) {
            return;
        }

        $log = IntegrationLogger::mininter()->withContext(['job' => 'DispatchMininterJobs']);
        $config = config('integrations.mininter');

        $batches = 0;
        $rows = 0;

        try {
            $stuck = Mininter::stuckQueued((int) $config['queued_ttl_minutes'])
                ->update(['status' => Mininter::STATUS_PENDING]);

            if ($stuck) {
                $log->warning("Reencoladas {$stuck} tramas atascadas en 'queued'");
            }

            Mininter::readyToDispatch((int) $config['max_retries'], (int) $config['retry_after_minutes'])
                ->select('id')
                ->orderBy('id')
                ->chunkById((int) $config['job_chunk'], function ($items) use (&$batches, &$rows) {
                    $ids = $items->pluck('id')->all();

                    Mininter::whereIn('id', $ids)->update(['status' => Mininter::STATUS_QUEUED]);

                    SendDataMininter::dispatch($ids);

                    $batches++;
                    $rows += count($ids);
                });
        } catch (Throwable $e) {
            $log->exception('Error al despachar lotes', $e, ['batches' => $batches, 'rows' => $rows]);

            throw $e;
        }

        if ($rows) {
            $log->info("Despachadas {$rows} tramas en {$batches} lote(s)");
        }
    }
}
