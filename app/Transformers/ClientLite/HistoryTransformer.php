<?php

namespace App\Transformers\ClientLite;


use Tobuli\Entities\UserDriver;
use Formatter;

class HistoryTransformer extends BaseTransformer {

    /**
     * @param $historyData
     * @return array|null
     */
    public function transform($historyData)
    {
        if (empty($historyData['root']))
            return [
                'stats' => null,
                'items' => null
            ];

        $result = [
            'stats' => $this->toStats($historyData['root'], [
                'driver',
                'distance',
                'drive_duration',
                'stop_duration',
                'speed_max',
                'fuel_consumption',
                'engine_hours'
            ]),
            'items' => []
        ];

        foreach ($historyData['groups']->all() as $group) {
            $result['items'][] = $this->toItem($group);
        }

        return $result;
    }

    protected function toItem($group)
    {
        switch ($group->getKey()) {
            case 'stop':
            case 'drive':
                $stats = $group->getKey() == 'stop'
                    ? [
                        'driver',
                        'speed',
                        'altitude',
                        'duration',
                        'fuel_consumption',
                        'engine_hours']
                    : [
                        'driver',
                        'speed_max',
                        'distance',
                        'duration',
                        'fuel_consumption',
                        'engine_hours'];

                return [
                    'status'    => $group->getKey(),
                    'title'     => trans('front.' . $group->getKey()),
                    'start'     => $this->toPositionLocation($group->getStartPosition()),
                    'end'       => $this->toPositionLocation($group->getEndPosition()),
                    'stats'     => $this->toStats($group, $stats),
                    'positions' => array_map(
                        [$this, 'toPosition']
                        , $group->getStat('positions')->value()
                    ),
                ];
            case 'event':
                return [
                    'status'    => $group->getKey(),
                    'title'     => $group->name,
                    'start'     => $this->toPositionLocation($group->getStartPosition()),
                    'end'       => null,
                    'stats'     => null,
                    'positions' => null,
                ];
        }
    }

    protected function toPosition($position)
    {
        return [
            'id' => $position->id,
            't' => Formatter::time()->convert($position->time),
            's' => (string)Formatter::speed()->format($position->speed),
            'c' => $position->color,
            'lat' => $position->latitude,
            'lng' => $position->longitude,
        ];
    }

    protected function toPositionLocation($position)
    {
        return [
            'time'  => $this->serializeDateTime($position->time),
            'coordinates' => [
                'lat'    => $position->latitude,
                'lng'    => $position->longitude,
            ],
        ];
    }

    protected function toStats($group, $keys)
    {
        $stats = [];

        foreach ($keys as $key) {
            switch ($key) {
                case 'altitude':
                    $stats[] = [
                        'key' => $key,
                        'title' => trans('front.altitude'),
                        'value' => Formatter::altitude()->human($group->getStartPosition()->altitude)
                    ];

                    break;
                case 'speed':
                    $stats[] = [
                        'key' => $key,
                        'title' => trans('front.speed'),
                        'value' => Formatter::speed()->human($group->getStartPosition()->speed)
                    ];

                    break;
                case 'speed_max':
                    $stats[] = [
                        'key' => $key,
                        'title' => trans('front.top_speed'),
                        'value' => $group->getStat('speed_max')->human()
                    ];

                    break;
                case 'speed_avg':
                    $stats[] = [
                        'key' => $key,
                        'title' => trans('front.average_speed'),
                        'value' => $group->getStat('speed_avg')->human()
                    ];

                    break;
                case 'duration':
                    $stats[] = [
                        'key' => $key,
                        'title' => trans('front.duration'),
                        'value' => $group->getStat('duration')->human()
                    ];

                    break;
                case 'distance':
                    $stats[] = [
                        'key' => $key,
                        'title' => trans('front.route_length'),
                        'value' => $group->getStat('distance')->human()
                    ];

                    break;
                case 'driver':
                    $drivers = $group->getStat('drivers')->get();
                    $driver = empty($drivers) ? '-' : runCacheEntity(UserDriver::class, $drivers)->implode('name', ', ');

                    $stats[] = [
                        'key' => $key,
                        'title' => trans('front.driver'),
                        'value' => $driver
                    ];

                    break;
                case 'drive_duration':
                    $stats[] = [
                        'key' => $key,
                        'title' => trans('front.move_duration'),
                        'value' => $group->getStat('drive_duration')->human()
                    ];
                    break;
                case 'stop_duration':
                    $stats[] = [
                        'key' => $key,
                        'title' => trans('front.stop_duration'),
                        'value' => $group->getStat('stop_duration')->human()
                    ];

                    break;
                case 'fuel_consumption':
                    if ($group->hasStat('fuel_consumption')) {
                        $_stats = $group->stats()->like('fuel_consumption_');

                        foreach ($_stats as $stat_key => $stat) {
                            $stats[] = [
                                'key' => $stat_key,
                                'title' => trans('front.fuel_consumption') . " ({$stat->getName()})",
                                'value' => $stat->human()
                            ];
                        }
                    }
                    break;
                case 'engine_hours':
                    if ($group->hasStat('engine_hours')) {
                        $stats[] = [
                            'key' => $key,
                            'title' => trans('front.engine_hours'),
                            'value' => $group->getStat('engine_hours')->human()
                        ];
                    }
                    break;
            }
        }

        return $stats;
    }
}