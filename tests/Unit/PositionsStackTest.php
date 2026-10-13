<?php

use App\Console\PositionsStack;
use Illuminate\Support\Facades\Redis;
use PHPUnit\Framework\TestCase;

/**
 * Prueba de integracion de la cola de posiciones en Redis. Necesita un Redis
 * local (usa la base 15 de la conexion "process" y la vacia):
 *   vendor/bin/phpunit --bootstrap vendor/autoload.php tests/Unit/PositionsStackTest.php
 */
class PositionsStackTest extends TestCase
{
    const TEST_DB = 15;

    /** @var \Illuminate\Redis\Connections\Connection */
    private $redis;

    /** @var PositionsStack */
    private $stack;

    protected function setUp(): void
    {
        $app = require __DIR__ . '/../../bootstrap/app.php';
        $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

        $this->redis = Redis::connection('process');

        try {
            $this->redis->select(self::TEST_DB);
        } catch (\Throwable $e) {
            $this->markTestSkipped('Redis local no disponible: ' . $e->getMessage());
        }

        $this->redis->flushdb();
        $this->stack = new PositionsStack();
    }

    protected function tearDown(): void
    {
        if ($this->redis) {
            $this->redis->flushdb();
        }
    }

    public function test_next_batch_returns_up_to_the_limit_and_then_the_rest()
    {
        foreach (range(1, 7) as $i) {
            $this->stack->add(['imei' => "86000000000000{$i}", 'lat' => 0]);
        }

        $first = $this->stack->nextBatch(5);
        $second = $this->stack->nextBatch(5);

        $this->assertCount(5, $first);
        $this->assertCount(2, $second);

        $all = array_merge($first, $second);
        sort($all);
        $this->assertSame(array_map(function ($i) { return "86000000000000{$i}"; }, range(1, 7)), $all);

        // mientras las listas sigan en Redis, un relleno las vuelve a repartir
        $this->assertCount(7, $this->stack->nextBatch(10));

        // consumidas (como hace el writer), ya no queda nada que repartir
        foreach ($all as $imei) {
            $this->stack->deleteImei($imei);
        }

        $this->assertSame([], $this->stack->nextBatch(5));
    }

    public function test_next_batch_ignores_keys_that_are_not_position_lists()
    {
        $this->stack->add(['imei' => '860000000000001', 'lat' => 0]);
        $this->redis->set('processing.insert:run.860000000000009', 'x');
        $this->redis->set('filler.1', 'x');

        $this->assertSame(['860000000000001'], $this->stack->nextBatch(10));
    }

    public function test_get_keys_uses_scan_and_finds_every_position_list()
    {
        // mas claves que el COUNT de un solo SCAN para forzar varias iteraciones
        $this->redis->pipeline(function ($pipe) {
            for ($i = 0; $i < 2500; $i++) {
                $pipe->lpush('positions.' . (860000000000000 + $i), '{"imei":1}');
                $pipe->set('filler.' . $i, '1');
            }
        });

        $keys = $this->stack->getKeys();

        $this->assertCount(2500, $keys);
        $this->assertCount(2500, array_unique($keys));
        $this->assertSame(2500, count($this->stack->getImeis()));
    }

    public function test_get_key_data_list_returns_positions_in_arrival_order_and_removes_the_key()
    {
        $this->stack->add(['imei' => '860000000000001', 'n' => 1]);
        $this->stack->add(['imei' => '860000000000001', 'n' => 2]);
        $this->stack->add(['imei' => '860000000000001', 'n' => 3]);

        $data = $this->stack->getKeyDataList('positions.860000000000001');

        $this->assertSame([1, 2, 3], array_column($data, 'n'));
        $this->assertSame(0, $this->redis->exists('positions.860000000000001'));
    }

    public function test_requeue_puts_imeis_back_for_the_next_batch()
    {
        $this->stack->requeue(['860000000000001', '860000000000002']);

        $batch = $this->stack->nextBatch(10);
        sort($batch);

        $this->assertSame(['860000000000001', '860000000000002'], $batch);
    }
}
