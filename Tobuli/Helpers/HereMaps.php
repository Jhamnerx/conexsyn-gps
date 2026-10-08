<?php

namespace Tobuli\Helpers;

class HereMaps
{
    public static function getPoliticalViews(): array
    {
        $apiKey = settings('main_settings.here_api_key');

        if (!$apiKey) {
            return [];
        }

        try {
            return \Cache::remember('here_maps.political_views', 3600, function () use ($apiKey) {
                $response = file_get_contents('https://maps.hereapi.com/v3/politicalViews?apiKey=' . $apiKey);
                $response = json_decode($response, true);

                return $response['base'] ?? [];
            });
        } catch (\Exception $e) {
            return [];
        }
    }

    public static function getPoliticalViewsOptions(): array
    {
        $views = self::getPoliticalViews();
        $views = array_combine($views, $views);

        return [null => trans('front.none')] + $views;
    }
}
