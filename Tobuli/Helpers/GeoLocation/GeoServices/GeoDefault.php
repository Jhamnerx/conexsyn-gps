<?php

namespace Tobuli\Helpers\GeoLocation\GeoServices;

use GuzzleHttp\Promise\Utils;
use Illuminate\Support\Facades\Cache;
use Tobuli\Helpers\GeoLocation\Exceptions\GeocoderServerException;
use Tobuli\Helpers\GeoLocation\GeoSettings;

class GeoDefault extends GeoNominatim
{
    const LOCK_TTL = 60;

    protected ?array $servers = null;

    public function __construct(GeoSettings $settings)
    {
        parent::__construct($settings);

        $this->servers = config('services.nominatims');

        $this->setServerCurrent();
    }

    protected function request($method, $options)
    {
        try {
            return parent::request($method, $options);
        } catch (GeocoderServerException $e) {
            $this->setServerLock($this->url);
            $this->setServerCurrent();
        }

        return $this->request($method, $options);
    }

    protected function setServerCurrent(): void
    {
        $this->url = $this->getFastestAvailableServer();
    }

    protected function getFastestAvailableServer(): string
    {
        $cacheKey = 'geo_default.servers_response_times.' . gethostname();

        $serverTimes = Cache::get($cacheKey);

        if ($serverTimes === null) {
            $serverTimes = $this->measureServerTimes();

            asort($serverTimes);

            Cache::put($cacheKey, $serverTimes, 600);
        }

        foreach ($serverTimes as $server => $time) {
            if (!$this->hasServerLock($server)) {
                return $server;
            }
        }

        throw new \RuntimeException('No available servers');
    }

    protected function measureServerTimes(): array
    {
        $promises = [];
        $results = [];
        $options = $this->getRequestOptions(['search' => 'test-geo-location']);

        foreach ($this->servers as $server) {
            $start = microtime(true);
            $promises[$server] = $this->client->getAsync($server, $options)
                ->then(
                    function () use ($server, $start, &$results) {
                        $results[$server] = microtime(true) - $start;
                    },
                    function () use ($server, &$results) {
                        $results[$server] = false;
                    }
                );
        }

        Utils::settle($promises)->wait();

        return array_filter($results, fn ($time) => $time !== false);
    }

    protected function hasServerLock(string $server) : bool
    {
        return Cache::has(self::lockKey($server));
    }

    protected function setServerLock(string $server)
    {
        Cache::put(self::lockKey($server), true, self::LOCK_TTL);
    }

    protected static function lockKey(string $server)
    {
        return "GeoDefault.lock." . md5($server);
    }
}