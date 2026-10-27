<?php

namespace App\Console;

use Illuminate\Support\Facades\Redis;

/**
 * Cola de posiciones en Redis.
 *
 * Cada dispositivo tiene una lista "positions.<imei>" (LPUSH por el productor,
 * lectura completa por el writer). Los workers de insert:run reparten el
 * trabajo a traves de la lista "list.imeis": se rellena con todos los IMEIs
 * que tienen posiciones pendientes (via SCAN, que no bloquea Redis como KEYS)
 * y cada worker toma lotes de ella con RPOP en pipeline.
 */
class PositionsStack
{
    const PREFIX = 'positions';

    const LIST_KEY = 'list.imeis';

    const LIST_LOCK = 'list.imeis.process';

    /** Claves por iteracion de SCAN. */
    const SCAN_COUNT = 1000;

    /** Argumentos maximos por LPUSH al rellenar la lista. */
    const PUSH_CHUNK = 5000;

    protected $redis;

    public function __construct()
    {
        $this->redis = Redis::connection('process');
    }

    public function add($data)
    {
        return $this->redis->lPush(self::PREFIX . '.' . $data['imei'], json_encode($data));
    }

    public function getImeis()
    {
        $keys = $this->getKeys();

        $imeis = array_map(function ($key) {
            return str_replace(self::PREFIX . '.', '', $key);
        }, $keys);

        return $imeis;
    }

    /**
     * Claves "positions.*" sin bloquear Redis (SCAN en vez de KEYS).
     */
    public function getKeys()
    {
        return $this->scanKeys(self::PREFIX . '.*');
    }

    /**
     * Recorre el keyspace con SCAN y devuelve las claves que casan con el patron.
     * SCAN puede repetir claves entre iteraciones: se deduplican.
     *
     * @return string[]
     */
    public function scanKeys(string $pattern): array
    {
        $cursor = '0';
        $keys = [];

        do {
            $result = $this->redis->scan($cursor, ['match' => $pattern, 'count' => self::SCAN_COUNT]);

            // phpredis devuelve false al terminar; predis devuelve [cursor, claves]
            if ($result === false) {
                break;
            }

            [$cursor, $batch] = $result;

            foreach ((array) $batch as $key) {
                $keys[$key] = true;
            }
        } while ((string) $cursor !== '0');

        return array_keys($keys);
    }

    public function count($imei = null)
    {
        if (is_null($imei))
            return $this->allCount();

        return $this->oneCount(self::PREFIX . '.' . $imei);
    }

    public function getData($imei, $remove = true)
    {
        return $this->getKeyData(self::PREFIX . '.' . $imei, $remove);
    }

    /**
     * Toma todas las posiciones de una lista y la borra, de forma atomica
     * (MULTI/EXEC): un LPUSH del productor no puede colarse entre el LRANGE y
     * el DEL, que es como se perdian posiciones con el pipeline anterior.
     */
    public function getKeyDataList($key)
    {
        $replies = $this->redis->transaction(function ($tx) use ($key) {
            $tx->lrange($key, 0, -1);
            $tx->del($key);
        });

        $items = isset($replies[0]) && is_array($replies[0]) ? $replies[0] : [];

        return array_map(function ($item) {
            return json_decode($item, true);
        }, array_reverse($items));
    }

    public function getKeyData($key, $remove = true)
    {
        if ($remove)
            $data = $this->redis->rPop($key);
        else
            $data = $this->redis->lIndex($key, -1);

        if ( ! $data)
            return null;

        $data = json_decode($data, true);

        if ( ! empty($data['deviceId']))
            $data['imei'] = $data['deviceId'];

        if ( ! empty($data['uniqueId']))
            $data['imei'] = $data['uniqueId'];

        if (empty($data['imei']))
            return false;

        $data['protocol'] = isset($data['protocol']) ? $data['protocol'] : null;

        return $data;
    }

    public function oneCount($key)
    {
        return $this->redis->lLen($key);
    }

    public function allCount() {
        $count = 0;

        $keys = $this->getKeys();

        if ( ! $keys)
            return $count;

        $data = $this->redis->pipeline(function($pipe) use ($keys) {
            foreach ($keys as $key)
                $pipe->lLen($key);
        });

        return array_sum($data);
    }

    public function deleteImei($imei)
    {
        $this->redis->del(self::PREFIX . '.' . $imei);
    }

    /**
     * Toma hasta $limit IMEIs de la lista de reparto en un solo viaje a Redis.
     * Si la lista esta vacia, un solo proceso la rellena (candado con expiracion)
     * mientras los demas esperan brevemente. Devuelve [] si no hay nada pendiente.
     *
     * @return string[]
     */
    public function nextBatch($limit = 200, $depth = 0)
    {
        if ($depth > 5)
            return [];

        $limit = max(1, (int) $limit);

        $results = $this->redis->pipeline(function ($pipe) use ($limit) {
            for ($i = 0; $i < $limit; $i++) {
                $pipe->rpop(self::LIST_KEY);
            }
        });

        $imeis = array_values(array_filter((array) $results, function ($imei) {
            return ! is_null($imei) && $imei !== false;
        }));

        if ($imeis)
            return $imeis;

        if ( ! $this->redis->set(self::LIST_LOCK, true, 'ex', 60, 'nx')) {
            usleep(50000); // otro proceso esta rellenando: 50 ms y se reintenta
            return $this->nextBatch($limit, $depth + 1);
        }

        $imeis = $this->getImeis();

        $this->requeue($imeis);

        $this->redis->del(self::LIST_LOCK);

        if ( ! $imeis)
            return [];

        return $this->nextBatch($limit, $depth + 1);
    }

    /**
     * Devuelve IMEIs a la lista de reparto (p. ej. los que un worker no llego
     * a procesar antes de agotar su tiempo).
     */
    public function requeue(array $imeis)
    {
        $imeis = array_values(array_filter($imeis, 'strlen'));

        foreach (array_chunk($imeis, self::PUSH_CHUNK) as $chunk) {
            $this->redis->lpush(self::LIST_KEY, ...$chunk);
        }
    }

    public function listCount()
    {
        return (int) $this->redis->lLen(self::LIST_KEY);
    }

    /**
     * Un IMEI cada vez. Se mantiene por compatibilidad; insert:run usa nextBatch().
     */
    public function next($depth = 0)
    {
        $depth++;

        if ($depth > 5)
            return null;

        $imei = $this->redis->rPop(self::LIST_KEY);

        if ( ! is_null($imei) && $imei !== false)
            return $imei;

        if ( ! $this->redis->set(self::LIST_LOCK, true, 'ex', 60, 'nx')) {
            usleep(50);
            return $this->next($depth);
        }

        $this->requeue($this->getImeis());

        $this->redis->del(self::LIST_LOCK);

        return $this->next($depth);
    }
}
