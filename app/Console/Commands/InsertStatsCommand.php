<?php

namespace App\Console\Commands;

use App\Console\PositionsStack;
use App\Console\PositionsStats;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Tobuli\Entities\Device;

/**
 * Radiografia de la cola de posiciones: cuanto hay pendiente en Redis, en que
 * dispositivos se concentra, cuantos workers insert:run estan registrados, si
 * Redis descarta claves por memoria y que IMEIs se estan desechando por no
 * estar registrados.
 *
 *  --connected    cruza las sesiones abiertas en el servidor GPS (connected.*)
 *                 con los dispositivos y la edad de su ultima posicion escrita
 *  --watch=N      mide durante N segundos las posiciones que entran (LPUSH) y
 *                 en que termina cada una (contadores de PositionsStats)
 *  --device=IMEI  todo lo que se sabe de un equipo: sesion, tiempos, cola
 *
 * Solo lectura.
 */
class InsertStatsCommand extends Command
{
    protected $signature = 'insert:stats
        {--top=10 : Cuantos IMEIs con mayor cola mostrar}
        {--connected : Cruza las claves connected.* con los dispositivos y su ultima posicion}
        {--watch=0 : Segundos de muestreo para medir entradas y destino de las posiciones}
        {--device= : IMEI a inspeccionar (sesion, tiempos del dispositivo y cola)}';

    protected $description = 'Estado de la cola de posiciones en Redis y de los procesos insert:run (solo lectura).';

    /** @var \Illuminate\Redis\Connections\Connection */
    private $redis;

    /** @var PositionsStack */
    private $stack;

    public function handle(): int
    {
        $this->redis = Redis::connection('process');
        $this->stack = new PositionsStack();

        if ($imei = $this->option('device')) {
            $this->deviceReport((string) $imei);

            return 0;
        }

        $this->queueReport();
        $this->keyspaceReport();
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

    // ------------------------------------------------------------ keyspace

    /**
     * Claves por prefijo (texto antes del primer "." o ":") con su tipo y, para
     * las listas, el total de elementos. Sirve para ver si alguien empuja a
     * listas distintas de positions.* (cmdstat_lpush cuenta todo el servidor).
     */
    private function keyspaceReport(): void
    {
        $keys = $this->stack->scanKeys('*');

        if (! $keys) {
            return;
        }

        $groups = [];

        foreach ($keys as $key) {
            $prefix = preg_split('/[.:]/', $key, 2)[0];
            $groups[$prefix][] = $key;
        }

        $rows = [];

        foreach ($groups as $prefix => $members) {
            $sample = array_slice($members, 0, 200);

            $types = $this->redis->pipeline(function ($pipe) use ($sample) {
                foreach ($sample as $key) {
                    $pipe->type($key);
                }
            });

            $types = array_map(function ($t) {
                return is_object($t) ? (string) $t : (string) $t;
            }, (array) $types);

            $type = implode(',', array_unique($types));
            $elements = '-';

            if (in_array('list', $types, true)) {
                $elements = number_format(array_sum($this->lengths($members)));
            }

            $rows[] = [$prefix, number_format(count($members)), $type, $elements];
        }

        usort($rows, function ($a, $b) {
            return (int) str_replace(',', '', $b[1]) <=> (int) str_replace(',', '', $a[1]);
        });

        $this->info('Claves por prefijo (base "process")');
        $this->table(['Prefijo', 'Claves', 'Tipo', 'Elementos en listas'], array_slice($rows, 0, 12));
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
     * ese IMEI. Si aqui hay actividad reciente, hay equipos enviando datos que
     * nunca llegaran a la base.
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
        $keys = $this->stack->scanKeys('connected.*');

        $imeis = array_map(function ($key) {
            return substr($key, strlen('connected.'));
        }, $keys);

        $onlineMinutes = (int) settings('main_settings.default_object_online_timeout');
        $now = Carbon::now();

        $registered = 0;
        $online = 0;
        $silentHour = 0;
        $silentDay = 0;
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

        foreach (array_slice($keys, 0, 2) as $key) {
            $this->line('  Valor de ' . $key . ': ' . $this->short((string) $this->redis->get($key)));
        }

        $this->line('Nota: connected.* la escribe el servidor GPS al abrir la sesion TCP y no caduca; un equipo puede mantener la sesion abierta sin enviar posiciones.');
    }

    // ------------------------------------------------------------ muestreo

    /**
     * Mide durante $seconds: posiciones que entran a Redis (LPUSH), destino de
     * cada una segun los contadores del writer, variacion de la cola y
     * dispositivos escritos en ese intervalo.
     */
    private function watchReport(int $seconds): void
    {
        $this->info("Muestreo de {$seconds} s...");

        $stats = new PositionsStats();

        $pushes0 = $this->lpushCalls();
        $pending0 = array_sum($this->lengths($this->stack->getKeys()));
        $counters0 = $stats->snapshot();
        $from = Carbon::now();

        sleep($seconds);

        $pushes1 = $this->lpushCalls();
        $pending1 = array_sum($this->lengths($this->stack->getKeys()));
        $counters1 = $stats->snapshot();
        $written = Device::connectedAfter($from)->count();

        $factor = 60 / max($seconds, 1);
        $perMinute = function ($n) use ($factor) {
            return number_format($n * $factor);
        };

        $in = ($pushes1 !== null && $pushes0 !== null) ? ($pushes1 - $pushes0) : null;
        $d = PositionsStats::diff($counters0, $counters1);

        $skipped = $d['skipped_no_imei'] + $d['skipped_osmand'] + $d['skipped_outdated'] + $d['skipped_future'];
        $discarded = $d['discarded_unregistered'] + $d['discarded_inactive'] + $d['discarded_invalid_imei'];
        $accounted = $d['processed'] + $d['failed'] + $d['skipped_no_traccar'] + $d['ack_history'] + $skipped + $discarded;
        $queueDelta = $pending1 - $pending0;

        $rows = [
            ['Entran a Redis (LPUSH)', $in === null ? 'INFO commandstats no disponible' : $perMinute($in)],
            ['Procesadas (validas, actualizan el dispositivo)', $perMinute($d['processed'])],
            ['  de ellas, guardadas en la tabla de posiciones', $perMinute($d['stored'])],
            ['  de ellas, "historia" (mas antiguas que la ultima del equipo: no cuentan como conectado)', $perMinute($d['history'])],
            ['Con error al procesar (se pierden; detalle en storage/logs/laravel.log)', $perMinute($d['failed'])],
            ['Sin fila traccar_devices (no se pueden escribir)', $perMinute($d['skipped_no_traccar'])],
            ['Ignoradas: ack con tiempo antiguo', $perMinute($d['ack_history'])],
            ['Ignoradas por validacion (sin imei / osmand / >90 dias / futuro)', $perMinute($skipped) . sprintf('  (%d / %d / %d / %d)', $d['skipped_no_imei'], $d['skipped_osmand'], $d['skipped_outdated'], $d['skipped_future'])],
            ['Desechadas (no registrado / inactivo / imei invalido)', $perMinute($discarded) . sprintf('  (%d / %d / %d)', $d['discarded_unregistered'], $d['discarded_inactive'], $d['discarded_invalid_imei'])],
            ['Cola al inicio / al final', number_format($pending0) . ' / ' . number_format($pending1)],
            ['Dispositivos con posicion escrita en el intervalo', number_format($written)],
        ];

        if ($in !== null) {
            $rows[] = ['Sin explicar (entradas - destino conocido - variacion de cola)', $perMinute($in - $accounted - $queueDelta)];
        }

        $this->table(['Dato', 'Por minuto'], $rows);

        if ($in && $accounted === 0 && $queueDelta <= 0) {
            $this->line('Los contadores del writer estan a cero: los workers aun ejecutan la version sin instrumentar (se renuevan solos en un minuto).');
        }

        if ($d['failed'] > 0) {
            $this->warn('Hay posiciones que fallan al procesarse: mira storage/logs/laravel.log (entradas "[insert:run] posicion descartada por error").');
        }

        if ($queueDelta > 0 && $in) {
            $this->warn('La cola crece en el intervalo: entran mas posiciones de las que los workers escriben. Mas workers en paralelo (y vida mayor para que se solapen) o revisar el tiempo por dispositivo con insert:run debug.');
        } elseif ($in === 0 || $in === null) {
            $this->line('No entran posiciones a Redis en el intervalo: el problema esta antes (servidor GPS / endpoint de posiciones), no en insert:run.');
        }
    }

    // ------------------------------------------------------------ un dispositivo

    /**
     * Todo lo visible de un equipo: sesion en el servidor GPS, tiempos del
     * dispositivo, cola pendiente y registro de no registrado si lo hay.
     */
    private function deviceReport(string $imei): void
    {
        $device = Device::where('imei', $imei)->first();
        $connected = $this->redis->get('connected.' . $imei);
        $key = PositionsStack::PREFIX . '.' . $imei;
        $pending = (int) $this->redis->llen($key);

        $rows = [
            ['IMEI', $imei],
            ['Sesion en el servidor GPS (connected.*)', $connected === null || $connected === false ? 'no' : 'si: ' . $this->short((string) $connected)],
            ['Posiciones en cola', number_format($pending)],
        ];

        if ($pending) {
            $rows[] = ['  mas antigua en cola', $this->positionSummary($this->redis->lindex($key, -1))];
            $rows[] = ['  mas reciente en cola', $this->positionSummary($this->redis->lindex($key, 0))];
        }

        if ($device) {
            $traccar = $device->traccar;
            $rows[] = ['Dispositivo', "#{$device->id} {$device->name}" . ($device->active ? '' : ' (INACTIVO: sus posiciones se desechan)')];
            $rows[] = ['Protocolo', $traccar->protocol ?? '?'];
            $rows[] = ['Ultima posicion del equipo (time)', $traccar->time ?? '-'];
            $rows[] = ['Ultima escritura (server_time, lo que cuenta como conectado)', $traccar->server_time ?? '-'];
            $rows[] = ['Ultimo ack (ack_time)', $traccar->ack_time ?? '-'];
            $rows[] = ['Ventana "conectado"', settings('main_settings.default_object_online_timeout') . ' min'];
        } else {
            $rows[] = ['Dispositivo', 'NO REGISTRADO: sus posiciones se desechan'];
        }

        try {
            $log = DB::table('unregistered_devices_log')->where('imei', $imei)->first();

            if ($log) {
                $rows[] = ['Registro de no registrado', "{$log->times} veces, ultimo {$log->date}, ip {$log->ip}, puerto {$log->port}"];
            }
        } catch (\Throwable $e) {
            // tabla no disponible
        }

        $this->table(['Dato', 'Valor'], $rows);
    }

    private function positionSummary($raw): string
    {
        $data = json_decode((string) $raw, true);

        if (! is_array($data)) {
            return $this->short((string) $raw);
        }

        $parts = [];

        foreach (['deviceTime', 'fixTime', 'time', 'serverTime', 'protocol', 'valid', 'latitude', 'longitude', 'speed'] as $field) {
            if (array_key_exists($field, $data) && ! is_array($data[$field])) {
                $parts[] = $field . '=' . var_export($data[$field], true);
            }
        }

        return $parts ? implode(' ', $parts) : $this->short((string) $raw);
    }

    private function short(string $value, int $max = 140): string
    {
        return strlen($value) > $max ? substr($value, 0, $max) . '...' : $value;
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
            // los valores "calls=1,usec=2" de commandstats se conservan tal cual;
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
