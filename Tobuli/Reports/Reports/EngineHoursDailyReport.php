<?php

namespace Tobuli\Reports\Reports;

use Formatter;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Support\Arr;
use Tobuli\Entities\Device;
use Tobuli\Entities\TraccarPosition;
use Tobuli\Reports\DeviceReport;

class EngineHoursDailyReport extends DeviceReport
{
    const TYPE_ID = 29;

    private $result;
    private $lastValue;

    protected $sensor;
    protected $callback;

    protected $disableFields = ['geofences', 'speed_limit', 'stops', 'show_addresses', 'zones_instead'];

    public function typeID()
    {
        return self::TYPE_ID;
    }

    public function title()
    {
        return trans('front.engine_hours') . ' ' . trans('validation.attributes.daily');
    }

    protected function generate()
    {
        $this->totals['device_count'] = $this->getDevicesQuery()->count();
        $this->totals['diff'] = 0;

        $this->getDevicesQuery()->chunk(1000, function ($devices) {
            foreach ($devices as $device) {
                $data = $this->generateDevice($device);

                $this->items[] = $data;

                if (empty($data['table']['rows']))
                    continue;

                $this->totals['diff'] += array_sum(Arr::pluck($data['table']['rows'], 'diff'));
            }
        });

        $this->totals['diff'] = round($this->totals['diff'], 2);
    }

    protected function processPosition($position)
    {
        $date = Formatter::date()->convert($position->time);

        $value = call_user_func($this->callback, $position);

        if (empty($value))
            $value = $this->lastValue;

        if ( ! isset($this->result[$date]['from'])) {
            $this->result[$date]['date'] = $date;
            $this->result[$date]['from'] = round($value, 2);
        }

        $this->result[$date]['to'] = round($value, 2);
        $this->result[$date]['diff'] = round($this->result[$date]['to'] - $this->result[$date]['from'], 2);

        $this->lastValue = $value;
    }

    protected function generateDevice($device)
    {
        $this->result = [];
        $this->lastValue = 0;

        try {
            $this->callback = $this->resolveCallback($device);

            $device->positions()
                ->orderliness('asc')
                ->whereBetween('time', [$this->date_from, $this->date_to])
                ->chunk(2000, function ($positions) {
                    foreach ($positions as $position)
                        $this->processPosition($position);
                });
        } catch (QueryException $e) {}

        if (empty($this->result))
            return [
                'meta' => $this->getDeviceMeta($device),
                'error' => trans('front.nothing_found_request')
            ];


        return [
            'meta' => $this->getDeviceMeta($device),
            'table' => [
                'rows' => $this->result
            ]
        ];
    }

    protected function valueByHours($position)
    {
        return round($position->getEngineHours() / 3600, 2);
    }

    protected function valueByEngineHours($position)
    {
        return round($position->getVirtualEngineHours() / 3600, 2);
    }

    protected function valueBySensor($position)
    {
        return $this->sensor->getValuePosition($position);
    }

    protected function resolveCallback($device)
    {
        $this->sensor = $device->getEngineHoursSensor();

        if ($this->sensor) {
            return [$this, 'valueBySensor'];
        }

        $byAttribute = $this->getDeviceEngineHoursAttribute($device);

        switch ($byAttribute) {
            case TraccarPosition::ENGINE_HOURS_KEY:
                return [$this, 'valueByHours'];

            case TraccarPosition::VIRTUAL_ENGINE_HOURS_KEY:
            default:
                return [$this, 'valueByEngineHours'];
        }
    }

    protected function getDeviceEngineHoursAttribute($device)
    {
        $positions = $device->positions()
            ->orderliness('asc')
            ->whereBetween('time', [$this->date_from, $this->date_to])
            ->limit(1000)
            ->get();

        foreach ($positions as $position) {
            if (!$position->hasParameter(TraccarPosition::ENGINE_HOURS_KEY))
                continue;

            return TraccarPosition::ENGINE_HOURS_KEY;
        }

        return TraccarPosition::VIRTUAL_ENGINE_HOURS_KEY;
    }
}