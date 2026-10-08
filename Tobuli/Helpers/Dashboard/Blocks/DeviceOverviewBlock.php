<?php namespace Tobuli\Helpers\Dashboard\Blocks;

use Carbon\Carbon;
use Tobuli\Lookups\Tables\DevicesIdleLookupTable;
use Tobuli\Lookups\Tables\DevicesInactiveLookupTable;
use Tobuli\Lookups\Tables\DevicesMoveLookupTable;
use Tobuli\Lookups\Tables\DevicesNeverConnectedLookupTable;
use Tobuli\Lookups\Tables\DevicesOfflineLookupTable;
use Tobuli\Lookups\Tables\DevicesParkLookupTable;
use Tobuli\Lookups\Tables\DevicesStopLookupTable;

class DeviceOverviewBlock extends Block
{
    protected function getName()
    {
        return 'device_overview';
    }

    protected function getContent()
    {
        $event_type = $this->getConfig("options.event_type");

        $events = $this->user->perm('events', 'view')
            ? $this->user
                ->events()
                ->with(['device', 'geofence'])
                ->latest()
                ->limit(7)
                ->where(function($query) use ($event_type){
                    if ($event_type)
                        $query->where('type', $event_type);
                })
                ->get()
            : collect();

        $devices = $this->user->devices();

        return [
            'statuses' => $this->getStatuses($devices),
            'total'    => (clone $devices)->count(),
            'events'   => $events,
            'event_type' => $event_type,
        ];
    }

    protected function getStatuses($devices)
    {
        return [
            [
                'label' => trans('front.move'),
                'data' => (clone $devices)->move()->count(),
                'color' => $this->getConfig("options.colors.move"),
                'url' => DevicesMoveLookupTable::route('index')
            ],
            [
                'label' => trans('front.idle'),
                'data' => (clone $devices)->idle()->count(),
                'color' => $this->getConfig("options.colors.idle"),
                'url' => DevicesIdleLookupTable::route('index')
            ],
            [
                'label' => trans('front.stop'),
                'data' => (clone $devices)->park()->count(),
                'color' => $this->getConfig("options.colors.stop"),
                'url' => DevicesParkLookupTable::route('index')
            ],
            [
                'label' => trans('front.offline'),
                'data' => (clone $devices)->offline()->count(),
                'color' => $this->getConfig("options.colors.offline"),
                'url'  => DevicesOfflineLookupTable::route('index')
            ],
            [
                'label' => trans('front.inactive'),
                'data' => (clone $devices)->inactive()->count(),
                'color' => $this->getConfig("options.colors.inactive"),
                'url' => DevicesInactiveLookupTable::route('index')
            ],
            [
                'label' => trans('front.never_connected'),
                'data' => (clone $devices)->neverConnected()->count(),
                'color' => $this->getConfig("options.colors.never_connected"),
                'url' => DevicesNeverConnectedLookupTable::route('index')
            ]
        ];
    }
}