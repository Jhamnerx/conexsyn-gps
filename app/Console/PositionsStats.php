<?php

namespace App\Console;

use Illuminate\Support\Facades\Redis;
use InvalidArgumentException;

/**
 * Contadores acumulativos (nunca se reinician, como INFO commandstats) del
 * destino de cada posicion que sale de Redis. insert:stats --watch toma dos
 * instantaneas y muestra la diferencia, de modo que se ve por minuto cuantas
 * posiciones entran y en que termina cada una.
 *
 * El writer acumula en memoria y vuelca con flush() una vez por lote (un
 * pipeline), para no anadir un viaje a Redis por posicion.
 */
class PositionsStats
{
    const PREFIX = 'stats.positions.';

    const COUNTERS = [
        'processed',              // paso la validacion y actualizo (o intento) el dispositivo
        'stored',                 // se inserto en la tabla de posiciones (solo si cambio algo)
        'history',                // mas antigua que la ultima del dispositivo: no actualiza su estado
        'ack_history',            // ack (sin tiempo nuevo) y antigua: se ignora
        'skipped_no_imei',        // payload sin imei
        'skipped_osmand',         // regla osmand del addon de login por app
        'skipped_outdated',       // mas de 90 dias de antiguedad
        'skipped_future',         // mas de 1 dia en el futuro
        'discarded_unregistered', // cola borrada: no existe dispositivo con ese imei
        'discarded_inactive',     // cola borrada: dispositivo inactivo
        'discarded_invalid_imei', // cola borrada: imei invalido o payload sin imei
    ];

    protected $redis;

    /** @var array<string,int> */
    protected $pending = [];

    public function __construct()
    {
        $this->redis = Redis::connection('process');
    }

    /**
     * Acumula en memoria; se escribe en flush().
     */
    public function add(string $counter, int $n = 1): void
    {
        if (! in_array($counter, self::COUNTERS, true)) {
            throw new InvalidArgumentException("Contador desconocido: {$counter}");
        }

        $this->pending[$counter] = ($this->pending[$counter] ?? 0) + $n;
    }

    /**
     * Vuelca lo acumulado en un solo pipeline.
     */
    public function flush(): void
    {
        if (! $this->pending) {
            return;
        }

        $pending = $this->pending;
        $this->pending = [];

        $this->redis->pipeline(function ($pipe) use ($pending) {
            foreach ($pending as $counter => $n) {
                $pipe->incrby(self::PREFIX . $counter, $n);
            }
        });
    }

    /**
     * Suma y escribe de inmediato.
     */
    public function incr(string $counter, int $n = 1): void
    {
        $this->add($counter, $n);
        $this->flush();
    }

    /**
     * Valor actual de todos los contadores.
     *
     * @return array<string,int>
     */
    public function snapshot(): array
    {
        $keys = array_map(function ($counter) {
            return self::PREFIX . $counter;
        }, self::COUNTERS);

        $values = (array) $this->redis->mget($keys);

        return array_combine(self::COUNTERS, array_map('intval', array_pad($values, count($keys), 0)));
    }

    /**
     * Incremento de cada contador entre dos instantaneas.
     *
     * @return array<string,int>
     */
    public static function diff(array $before, array $after): array
    {
        $diff = [];

        foreach (self::COUNTERS as $counter) {
            $diff[$counter] = (int) ($after[$counter] ?? 0) - (int) ($before[$counter] ?? 0);
        }

        return $diff;
    }
}
