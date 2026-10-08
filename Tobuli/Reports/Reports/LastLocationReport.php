<?php

namespace Tobuli\Reports\Reports;

use Illuminate\Database\QueryException;
use Tobuli\Entities\Device;
use Tobuli\Helpers\Formatter\Facades\Formatter;
use Tobuli\Reports\DeviceReport;

class LastLocationReport extends DeviceReport
{
    const TYPE_ID = 93;

    protected $enableFields = [
        'date_to',
        'to_time',
        'metas',
        'devices',
        'geofences',
        'show_addresses',
    ];
    protected $deviceless = true;

    private bool $hasGeofences;

    public function typeID(): int
    {
        return self::TYPE_ID;
    }

    public function title()
    {
        return trans('front.last_location');
    }

    protected function beforeGenerate()
    {
        $this->hasGeofences = $this->geofences && $this->geofences->isNotEmpty();
    }

    protected function generateDevice(Device $device): array
    {
        try {
            $position = $device->positions()
                ->where('time', '<', $this->date_to)
                ->orderliness('DESC')
                ->first();
        } catch (QueryException $e) {
            if ($e->getCode() !== '42S02') {
                throw $e;
            }
        }

        if (empty($position)) {
            return [
                'meta' => $this->getDeviceMeta($device),
                'error' => trans('front.nothing_found_request')
            ];
        }

        $data = [
            'meta' => $this->getDeviceMeta($device),
            'last_connection' => Formatter::time()->convert($device->last_connect_time),
            'coordinates' => googleMapLink($position->latitude, $position->longitude, "{$position->latitude},{$position->longitude}"),
        ];

        if ($this->show_addresses) {
            $data['address'] = googleMapLink($position->latitude, $position->longitude, $this->getAddress($position));
        }

        if ($this->hasGeofences) {
            $data['geofences'] = googleMapLink($position->latitude, $position->longitude, $this->getGeofencesNames($position));
        }

        return $data;
    }

    public function hasGeofences(): bool
    {
        return $this->hasGeofences;
    }
}