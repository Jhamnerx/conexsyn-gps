<?php

use App\Console\PositionsStats;
use Illuminate\Support\Facades\Redis;
use PHPUnit\Framework\TestCase;

/**
 * Contadores de resultado del procesado de posiciones. Necesita un Redis local
 * (usa la base 15 de la conexion "process" y la vacia):
 *   vendor/bin/phpunit --bootstrap vendor/autoload.php tests/Unit/PositionsStatsTest.php
 */
class PositionsStatsTest extends TestCase
{
    const TEST_DB = 15;

    private $redis;

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
    }

    protected function tearDown(): void
    {
        if ($this->redis) {
            $this->redis->flushdb();
        }
    }

    public function test_snapshot_starts_at_zero_for_every_counter()
    {
        $snapshot = (new PositionsStats())->snapshot();

        $this->assertSame(PositionsStats::COUNTERS, array_keys($snapshot));
        $this->assertSame(0, array_sum($snapshot));
    }

    public function test_add_accumulates_locally_and_flush_writes_once()
    {
        $stats = new PositionsStats();

        $stats->add('processed');
        $stats->add('processed');
        $stats->add('stored', 5);

        // sin flush, Redis sigue a cero
        $this->assertSame(0, (new PositionsStats())->snapshot()['processed']);

        $stats->flush();

        $snapshot = (new PositionsStats())->snapshot();
        $this->assertSame(2, $snapshot['processed']);
        $this->assertSame(5, $snapshot['stored']);

        // un segundo flush sin novedades no duplica
        $stats->flush();
        $this->assertSame(2, (new PositionsStats())->snapshot()['processed']);
    }

    public function test_incr_writes_immediately()
    {
        (new PositionsStats())->incr('discarded_unregistered', 3);

        $this->assertSame(3, (new PositionsStats())->snapshot()['discarded_unregistered']);
    }

    public function test_unknown_counter_is_rejected()
    {
        $this->expectException(\InvalidArgumentException::class);

        (new PositionsStats())->add('no_existe');
    }

    public function test_diff_returns_per_counter_increments()
    {
        $before = (new PositionsStats())->snapshot();
        (new PositionsStats())->incr('processed', 7);
        (new PositionsStats())->incr('history', 2);
        $after = (new PositionsStats())->snapshot();

        $diff = PositionsStats::diff($before, $after);

        $this->assertSame(7, $diff['processed']);
        $this->assertSame(2, $diff['history']);
        $this->assertSame(0, $diff['stored']);
    }
}
