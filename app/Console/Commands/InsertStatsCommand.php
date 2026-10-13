<?php

namespace App\Console\Commands;

use App\Console\PositionsStack;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Tobuli\Entities\Device;

/**
 * Radiografia de la cola de posiciones: cuanto hay pendiente en Redis, en que
 * dispositivos se concentra, cuantos workers insert:run estan registrados, si
 * Redis descarta claves por memoria y que IMEIs se estan desechando por no
 * estar registrados. Con --connected cruza las sesiones abiertas en el servidor
 * GPS (claves connected.*) con los dispositivos y su ultima posicion escrita;
 * con --watch=N mide durante N segundos el ritmo de entrada (LPUSH) frente al
 * avance de la cola. Solo lectura.
 */
class InsertStatsCommand extends Command
{
    protected $signature = 'insert:stats
        {--top=10 : Cuantos IMEIs con mayor cola mostrar}
        {--connected : Cruza las claves connected.* con los dispositivos y su ultima posicion}
        {--watch=0 : Segundos de muestreo para medir posiciones entrantes por minuto}';

    protected $description = 'Estado de la cola de posiciones en Redis y de los procesos insert:run (solo lectura).';

    /** @var \Illuminate\Redis\Connections\Connection */
    private $redis;

    /** @var PositionsStack */
    private $stack;

    public function handle(): int
    {
        $this->redis = Redis::connection('process');
        $this->stack = new PositionsStack();

        $this->queueReport();
        $this->memoryReport();
        $this->unregisteredReport();

        if ($this->option('connected')) {
            $this->connectedReport();
        }

        if ((int) $this->option('watch') > 0) {
            $this->watchReport((int) $this->option('watch'));
        }

        return 0;
    }

    // ------------------------------------------------------------ cola

    private function queueReport(): void
    {
        $keys = $this->stack->getKeys();
        $lengths = $this->lengths($keys);
        $total = array_sum($lengths);

        $workers = count($this->stack->scanKeys('process.insert:run.*'));
        $locks = count($this->stack->scanKeys('processing.insert:run.*'));

        $this->info('Cola de posiciones (Redis "process")');
        $this->table(['Dato', 'Valor'], [
            ['IMEIs con posiciones pendientes', number_format(count($keys))],
            ['Posiciones pendientes en total', number_format($total)],
            ['IMEIs en la lista de reparto (list.imeis)', number_format($this->stack->listCount())],
            ['Procesos insert:run registrados', $workers . ' (limite ' . config('tobuli.process.insert_limit') . ', vida ' . config('tobuli.process.insert_timeout') . 's, lote ' . config('tobuli.process.insert_batch', 200) . ')'],
            ['IMEIs bloqueados en proceso ahora', $locks],
        ]);

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
    }

    /**
     * @param string[] $keys
     * @return int[]
     */
    private function lengths(array $keys): array
    {
        if (! $keys) {
            return [];
        }

        $lengths = $this->redis->pipeline(function ($pipe) use ($keys) {
            foreach ($keys as $key) {
                $pipe->llen($key);
            }
        });

        return array_map('intval', (array) $lengths);
    }

    // ------------------------------------------------------------ memoria

    private function memoryReport(): void
    {
        $info = $this->info_();

        if ($info === null) {
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

    // ------------------------------------------------------------ no registrados

    /**
     * IMEIs cuyas posiciones se desechan porque no existe un dispositivo con
     * ese IMEI (o protocolo). Si aqui hay actividad reciente, hay equipos
     * enviando datos que nunca llegaran a la base.
     */
    private function unregisteredReport(): void
    {
        try {
            $since = Carbon::now()->subHour();

            $recent = DB::table('unregistered_devices_log')->where('date', '>=', $since);
            $count = (clone $recent)->count();
            $times = (int) (clone $recent)->sum('times');

            $this->info('Desechadas por dispositivo no registrado (ultima hora)');
            $this->table(['Dato', 'Valor'], [
                ['IMEIs no registrados con envios', number_format($count)],
                ['Posiciones desechadas (times)', number_format($times)],
            ]);

            if ($count) {
                $rows = DB::table('unregistered_devices_log')
                    ->where('date', '>=', $since)
                    ->orderByDesc('times')
                    ->limit(5)
                    ->get(['imei', 'port', 'ip', 'times', 'date']);

                $this->table(['IMEI', 'Puerto', 'IP', 'Veces', 'Ultimo'], array_map(function ($r) {
                    return [$r->imei, $r->port, $r->ip, number_format($r->times), $r->date];
                }, $rows->all()));

                $this->warn('Estos equipos envian posiciones pero no existe un dispositivo con ese IMEI: se descartan. Registralos o corrige el IMEI.');
            }
        } catch (\Throwable $e) {
            $this->line('Tabla unregistered_devices_log no disponible: ' . $e->getMessage());
        }
    }

    // ------------------------------------------------------------ connected.*

    /**
     * Cruza las sesiones abiertas en el servidor GPS (connected.<imei>, que el
     * servidor crea al conectar el equipo) con los dispositivos registrados y la
     * fecha de su ultima posicion escrita (traccar_devices.server_time), que es
     * lo que la interfaz cuenta como "conectado".
     */
    private function connectedReport(): void
    {
        $imeis = array_map(function ($key) {
            return substr($key, strlen('connected.'));
        }, $this->stack->scanKeys('connected.*'));

        $onlineMinutes = (int) settings('main_settings.default_object_online_timeout');
        $now = Carbon::now();

        $registered = 0;
        $online = 0;
        $silentHour = 0;
        $silentDay = 0;
        $never = 0;
        $missing = [];

        foreach (array_chunk($imeis, 1000) as $chunk) {
            $found = Device::whereIn('imei', $chunk)->pluck('imei')->all();
            $registered += count($found);
            $missing = array_merge($missing, array_diff($chunk, $found));

            if (! $found) {
                continue;
            }

            $online += Device::whereIn('devices.imei', $found)->online($onlineMinutes)->count();
            $silentHour += Device::whereIn('devices.imei', $found)->connectedBefore($now->copy()->subMinutes($onlineMinutes))->connectedAfter($now->copy()->subHour())->count();
            $silentDay += Device::whereIn('devices.imei', $found)->connectedBefore($now->copy()->subHour())->connectedAfter($now->copy()->subDay())->count();
        }

        $older = $registered - $online - $silentHour - $silentDay;

        $this->info('Sesiones abiertas en el servidor GPS (claves connected.*)');
        $this->table(['Dato', 'Valor'], [
            ['Claves connected.*', number_format(count($imeis))],
            ['  con dispositivo registrado', number_format($registered)],
            ["    con posicion en los ultimos {$onlineMinutes} min (cuenta como conectado)", number_format($online)],
            ["    sin posicion desde hace {$onlineMinutes} min a 1 h", number_format($silentHour)],
            ['    sin posicion desde hace 1 h a 1 dia', number_format($silentDay)],
            ['    sin posicion desde hace mas de 1 dia o nunca', number_format(max($older, 0))],
            ['  sin dispositivo registrado (sus posiciones se desechan)', number_format(count($missing))],
        ]);

        if ($missing) {
            $this->line('  Ejemplos sin registrar: ' . implode(', ', array_slice($missing, 0, 8)));
        }

        $this->line('Nota: connected.* la escribe el servidor GPS al abrir la sesion TCP y no caduca; un equipo puede mantener la sesion abierta sin enviar posiciones.');
    }

    // ------------------------------------------------------------ muestreo

    /**
     * Mide durante $seconds: LPUSH recibidos (posiciones que entran a Redis),
     * variacion de la cola y dispositivos escritos en ese intervalo.
     */
    private function watchReport(int $seconds): void
    {
        $this->info("Muestreo de {$seconds} s...");

        $pushes0 = $this->lpushCalls();
        $pending0 = array_sum($this->lengths($this->stack->getKeys()));
        $from = Carbon::now();

        sleep($seconds);

        $pushes1 = $this->lpushCalls();
        $pending1 = array_sum($this->lengths($this->stack->getKeys()));
        $written = Device::connectedAfter($from)->count();

        $factor = 60 / max($seconds, 1);
        $in = $pushes1 !== null && $pushes0 !== null ? ($pushes1 - $pushes0) : null;

        $this->table(['Dato', 'Valor'], [
            ['Posiciones que entran (LPUSH/min)', $in === null ? 'INFO commandstats no disponible' : number_format($in * $factor)],
            ['Cola al inicio / al final', number_format($pending0) . ' / ' . number_format($pending1)],
            ['Variacion de la cola', ($pending1 - $pending0 >= 0 ? '+' : '') . number_format($pending1 - $pending0)],
            ['Dispositivos con posicion escrita en el intervalo', number_format($written)],
        ]);

        if ($pending1 > $pending0 && $in) {
            $this->warn('La cola crece: entran mas posiciones de las que los workers escriben. Mas workers en paralelo o revisar el tiempo por dispositivo (insert:run debug).');
        } elseif ($in === 0 || $in === null) {
            $this->line('No entran posiciones a Redis en el intervalo: el problema esta antes (servidor GPS / endpoint de posiciones), no en insert:run.');
        }
    }

    /**
     * Llamadas acumuladas a LPUSH segun INFO commandstats (null si no esta disponible).
     */
    private function lpushCalls(): ?int
    {
        $info = $this->info_('commandstats');

        if ($info === null) {
            return null;
        }

        $stat = $info['cmdstat_lpush'] ?? null;

        if ($stat === null) {
            return 0;
        }

        if (is_array($stat)) {
            return (int) ($stat['calls'] ?? 0);
        }

        return preg_match('/calls=(\d+)/', (string) $stat, $m) ? (int) $m[1] : 0;
    }

    /**
     * INFO aplanado: predis agrupa por secciones, phpredis lo devuelve plano.
     */
    private function info_(?string $section = null): ?array
    {
        try {
            $info = $section ? $this->redis->info($section) : $this->redis->info();
        } catch (\Throwable $e) {
            $this->line('Redis INFO no disponible: ' . $e->getMessage());
            return null;
        }

        return $this->flatten((array) $info);
    }

    private function flatten(array $info): array
    {
        $flat = [];

        foreach ($info as $key => $value) {
            // valores "calls=1,usec=2" de commandstats se conservan tal cual;
            // las secciones (arrays sin claves cmdstat_/db) se aplanan
            if (is_array($value) && ! preg_match('/^(cmdstat_|db\d+)/', (string) $key)) {
                $flat = array_merge($flat, $this->flatten($value));
            } else {
                $flat[$key] = $value;
            }
        }

        return $flat;
    }
}
