<?php

namespace App\Transformers\ClientLite;

use Tobuli\Entities\Device;

abstract class DeviceTransformer extends BaseTransformer {
    protected $availableIncludes = [
        'position',
        'tail',
        'icon',
        'status',
        'coordinates',
        'sensors',
        'services',
        'driver',
        'stats'
    ];

    public function includeStatus(Device $device) {
        return $this->item($device, new DeviceStatusTransformer(), false);
    }

    public function includeIcon(Device $device) {
        return $this->item($device, new DeviceIconTransformer(), false);
    }

    public function includeTail(Device $device) {
        return $this->item($device, new DeviceTailTransformer(), false);
    }

    public function includeCoordinates(Device $device) {
        if (is_null($device->lat) || is_null($device->lng))
            return null;

        return $this->item($device, new DeviceCoordinatesTransformer(), false);
    }

    public function includePosition(Device $device) {
        return $this->item($device, new DevicePositionTransformer(), false);
    }

    public function includeSensors(Device $device) {
        return $this->item($device, new DeviceSensorsTransformer(), false);
    }

    public function includeServices(Device $device) {
        return $this->item($device, new DeviceServicesTransformer(), false);
    }

    public function includeDriver(Device $device) {
        if ( ! $device->driver)
            return null;

        return $this->item($device->driver, new DriverTransformer(), false);
    }

    public function includeStats(Device $device) {
        return $this->item($device, new DeviceStatsTransformer(), false);
    }

    public static function colorConvert($color)
    {
        $colors = [
            'green'  => '#008000',
            'yellow' => '#FFFF00',
            'red'    => '#FF0000',
            'blue'   => '#0000FF',
            'orange' => '#FFA500',
            'black'  => '#000000',
        ];

        return $colors[$color] ?? $color;
    }

    public static function serializeDeviceDateTime(Device $device)
    {
        $timestamp = strtotime($device->time);

        if (empty($timestamp))
            return null;

        return [
            'timestamp' => $timestamp,
            'formatted' => $device->time
        ];
    }
}