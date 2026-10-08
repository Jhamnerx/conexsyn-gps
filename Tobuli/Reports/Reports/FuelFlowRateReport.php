<?php

namespace Tobuli\Reports\Reports;

use Illuminate\Database\QueryException;
use Tobuli\Entities\Device;
use Tobuli\Entities\TraccarPosition;
use Tobuli\Reports\DeviceReport;

class FuelFlowRateReport extends DeviceReport
{
    const TYPE_ID = 83;

    public function typeID()
    {
        return self::TYPE_ID;
    }

    public function title()
    {
        return trans('front.fuel_flow_rate');
    }

    protected function precheckError($device)
    {
        if ($device->getSensorByType('fuel_consumption')) {
            return null;
        }

        return trans('global.dont_exist', [
            'attribute' => trans('front.sensor') . ' (' . trans('front.fuel_consumption') . ')'
        ]);
    }

    protected function generateDevice(Device $device)
    {
        if ($error = $this->precheckError($device)) {
            return [
                'meta' => $this->getDeviceMeta($device),
                'error' => $error
            ];
        }

        $sensor = $device->getSensorByType('fuel_consumption');

        $item = [
            'meta'                      => $this->getDeviceMeta($device),
            'fuel_consumption_custom'   => null,
            'engine_hours_custom'       => null,
            'flow_rate_m3_h'            => null,
            'flow_rate_l_s'             => null,
            'location'                  => null,
        ];

        try {
            $engineHoursAttr = $this->getDeviceEngineHoursAttribute($device);

            $positions = $device->positions()
                ->whereBetween('time', [$this->date_from, $this->date_to])
                ->cursor();

            $position = $positions->first();

            $engineHoursFrom = $position ? $this->getEngineHours($position, $engineHoursAttr) : null;

            foreach ($positions as $position) {
                $fuelConsumption = $sensor->getValuePosition($position);

                $item['fuel_consumption_custom'] += $fuelConsumption;

                if ($item['location'] === null) {
                    $item['location'] = $this->getLocation($position);
                }
            }

            if ($item['fuel_consumption_custom']) {
                $item['fuel_consumption_custom'] = round($item['fuel_consumption_custom'], 2);
            }

            $engineHoursTo = isset($position) ? $this->getEngineHours($position, $engineHoursAttr) : null;

            if ($engineHoursFrom !== null && $engineHoursTo !== null) {
                $item['engine_hours_custom'] = round($engineHoursTo - $engineHoursFrom, 2);
            }

            $item['flow_rate_m3_h'] = $item['engine_hours_custom'] && $item['fuel_consumption_custom']
                ? round($item['fuel_consumption_custom'] / $item['engine_hours_custom'], 2)
                : null;

            if ($item['flow_rate_m3_h'] !== null) {
                $item['flow_rate_l_s'] = round($item['flow_rate_m3_h'] * 1000 / 3600, 2);
            }
        } catch (QueryException $e) {}

        return $item;
    }

    private function getEngineHours(TraccarPosition $position, string $attr): float
    {
        $value = $attr === TraccarPosition::ENGINE_HOURS_KEY
            ? $position->getEngineHours()
            : $position->getVirtualEngineHours();

        if (empty($value)) {
            return $this->lastEngineHours;
        }

        $value = round($value / 3600, 2);

        return $this->lastEngineHours = $value;
    }

    private function getDeviceEngineHoursAttribute(Device $device): string
    {
        $positions = $device->positions()
            ->orderliness('asc')
            ->whereBetween('time', [$this->date_from, $this->date_to])
            ->limit(1000)
            ->cursor();

        foreach ($positions as $position) {
            if (!$position->hasParameter(TraccarPosition::ENGINE_HOURS_KEY)) {
                continue;
            }

            return TraccarPosition::ENGINE_HOURS_KEY;
        }

        return TraccarPosition::VIRTUAL_ENGINE_HOURS_KEY;
    }

    public static function isAvailable(): bool
    {
        return config('addon.report_fuel_tank_usage');
    }
}