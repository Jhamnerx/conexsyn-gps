<?php

namespace Tobuli\Helpers;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class FlightsInfoService
{
    private Geohash $geohash;

    public function __construct()
    {
        $this->geohash = new Geohash();
    }

    public function getFlights(float $lat, float $lon): array
    {
        $geohash = $this->geohash->encode($lat, $lon, 3);

        return Cache::remember("flights.$geohash", 60, fn () => $this->fetchData($lat, $lon));
    }

    private function fetchData(float $lat, float $lon): array
    {
        // endpoint max supported dist is 250
        $url = "https://opendata.adsb.fi/api/v2/lat/$lat/lon/$lon/dist/250";

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $response = Http::timeout(5)->get($url);

            if ($response->successful()) {
                return $response->json();
            }

            if ($response->status() === 429) {
                usleep(500 * 1000);
                continue;
            }

            throw new \RuntimeException('Failed to fetch flight data: ' . $response->status());
        }

        throw new \RuntimeException('Exceeded retry limit while fetching flight data.');
    }
}