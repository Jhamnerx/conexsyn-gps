<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;

class CacheShellCommandResultJob implements ShouldQueue
{
    use InteractsWithQueue, SerializesModels;

    private string $command;
    private string $key;
    private int $ttl;

    public function __construct(string $command, string $key, int $ttl)
    {
        $this->command = $command;
        $this->key = $key;
        $this->ttl = $ttl;
    }

    public function handle(): void
    {
        Cache::remember($this->key, $this->ttl, fn () => exec($this->command));
    }

    public static function hasResult(string $key): bool
    {
        return Cache::has($key);
    }

    public static function getResult(string $key, $default = null)
    {
        return Cache::get($key, $default);
    }
}
