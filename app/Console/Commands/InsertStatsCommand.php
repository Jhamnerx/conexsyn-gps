<?php

namespace App\Console\Commands;

use App\Console\PositionsStack;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;

/**
 * Radiografia de la cola de posiciones: cuanto hay pendiente en Redis, en que
 * dispositivos se concentra, cuantos workers insert:run estan registrados y si
 * Redis esta descartando claves por memoria. Solo lectura.
 */
class InsertStatsCommand extends Command
{
    protected $signature = 'insert:stats
        {--top=10 : Cuantos IMEIs con mayor cola mostrar}';

    protected $description = 'Estado de la cola de posiciones en Redis y de los procesos insert:run (solo lectura).';

    public function handle(): int
    {
        $redis = Redis::connection('process');
        $stack = new PositionsStack();

        $keys = $stack->getKeys();

        $lengths = $keys ? $redis->pipeline(function ($pipe) use ($keys) {
            foreach ($keys as $key) {
                $pipe->llen($key);
            }
        }) : [];

        $lengths = array_map('intval', (array) $lengths);
        $total = array_sum($lengths);

        $workers = count($stack->scanKeys('process.insert:run.*'));
        $locks = count($stack->scanKeys('processing.insert:run.*'));

        $this->info('Cola de posiciones (Redis "process")');
        $this->table(['Dato', 'Valor'], [
            ['IMEIs con posiciones pendientes', number_format(count($keys))],
            ['Posiciones pendientes en total', number_format($total)],
            ['IMEIs en la lista de reparto (list.imeis)', number_format($stack->listCount())],
            ['Procesos insert:run registrados', $workers . ' (limite ' . config('tobuli.process.insert_limit') . ', vida ' . config('tobuli.process.insert_timeout') . 's, lote ' . config('tobuli.process.insert_batch', 200) . ')'],
            ['IMEIs bloqueados en proceso ahora', $locks],
        ]);

        $this->memoryReport($redis);

        if ($keys) {
            $top = (int) $this->option('top');

            arsort($lengths);
            $rows = [];

            foreach (array_slice($lengths, 0, $top, true) as $index => $length) {
                $rows[] = [str_replace(PositionsStack::PREFIX . '.', '', $keys[$index]), number_format($length)];
            }

            $this->info("Mayores colas (top {$top})");
            $this->table(['IMEI', 'Posiciones'], $rows);
        }

        if ($workers === 0 && $total > 0) {
            $this->warn('Hay posiciones pendientes y ningun proceso insert:run registrado: revisa el cron/supervisor que los lanza.');
        }

        return 0;
    }

    private function memoryReport($redis): void
    {
        try {
            $info = $this->flatten((array) $redis->info());
        } catch (\Throwable $e) {
            $this->line('Redis INFO no disponible: ' . $e->getMessage());
            return;
        }

        $maxmemory = (int) ($info['maxmemory'] ?? 0);
        $evicted = (int) ($info['evicted_keys'] ?? 0);

        $this->info('Memoria de Redis');
        $this->table(['Dato', 'Valor'], [
            ['Usada', $info['used_memory_human'] ?? '?'],
            ['Limite (maxmemory)', $maxmemory ? ($info['maxmemory_human'] ?? $maxmemory) : 'sin limite'],
            ['Politica', $info['maxmemory_policy'] ?? '?'],
            ['Claves descartadas (evicted_keys)', number_format($evicted)],
        ]);

        if ($evicted > 0) {
            $this->warn('Redis ha descartado claves por falta de memoria: esas posiciones se perdieron sin guardarse. Sube maxmemory o usa la politica noeviction.');
        }
    }

    /**
     * predis agrupa INFO por secciones; phpredis lo devuelve plano. Se aplana.
     */
    private function flatten(array $info): array
    {
        $flat = [];

        foreach ($info as $key => $value) {
            if (is_array($value)) {
                $flat = array_merge($flat, $this->flatten($value));
            } else {
                $flat[$key] = $value;
            }
        }

        return $flat;
    }
}
