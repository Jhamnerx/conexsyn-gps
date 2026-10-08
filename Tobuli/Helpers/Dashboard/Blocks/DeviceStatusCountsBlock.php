<?php namespace Tobuli\Helpers\Dashboard\Blocks;

use Tobuli\Lookups\Tables\DevicesExpiredLookupTable;
use Tobuli\Lookups\Tables\DevicesLookupTable;
use Tobuli\Lookups\Tables\DevicesNeverConnectedLookupTable;
use Tobuli\Lookups\Tables\DevicesOfflineLookupTable;
use Tobuli\Lookups\Tables\DevicesOnlineLookupTable;

class DeviceStatusCountsBlock extends Block
{
    protected function getName()
    {
        return 'device_status_counts';
    }

    protected function getContent()
    {
        return [
            'statuses' => [
                [
                    'key'   => 'total',
                    'label' => trans('front.count'),
                    'data' => $this->user->devices()->count(),
                    'url'  => DevicesLookupTable::route('index')
                ],
                [
                    'key'   => 'online',
                    'label' => trans('global.online'),
                    'data' => $this->user->devices()->online()->count(),
                    'url'  => DevicesOnlineLookupTable::route('index')
                ],
                [
                    'key'   => 'offline',
                    'label' => trans('front.offline'),
                    'data' => $this->user->devices()->offline()->count(),
                    'url'  => DevicesOfflineLookupTable::route('index')
                ],
                [
                    'key'   => 'never-connected',
                    'label' => trans('front.never_connected'),
                    'data' => $this->user->devices()->neverConnected()->count(),
                    'url' => DevicesNeverConnectedLookupTable::route('index')
                ],
                [
                    'key'   => 'expired',
                    'label' => trans('front.expired'),
                    'data' => $this->user->devices()->expired()->count(),
                    'url' => DevicesExpiredLookupTable::route('index')
                ],
            ]
        ];

    }
}