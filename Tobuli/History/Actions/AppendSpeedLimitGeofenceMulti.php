<?php

namespace Tobuli\History\Actions;

use Tobuli\Entities\Geofence;

class AppendSpeedLimitGeofenceMulti extends ActionAppend
{
    private array $limits = [];

    public static function required()
    {
        return [AppendGeofences::class];
    }

    public function boot()
    {
    }

    public function proccess(&$position)
    {
        $position->speed_limit = $this->getSpeedLimit($position);
    }

    protected function getSpeedLimit($position)
    {
        if (empty($position->geofences)) {
            return null;
        }

        $time = (new \DateTime($position->time))->format('H:i');

        foreach ($position->geofences as $id) {
            $speed_limit = $this->getSpeedLimitGeofence($time, $id);

            if ($speed_limit) {
                break;
            }
        }

        return empty($speed_limit) ? null : $speed_limit;
    }

    private function getSpeedLimitGeofence($time, int $geofenceId)
    {
        $intervals = $this->getGeofenceIntervals($geofenceId);

        if (!$intervals) {
            return null;
        }

        foreach ($intervals as $interval) {
            if ($this->isTimeInInterval($time, $interval)) {
                return $interval['speed_limit'];
            }
        }

        return null;
    }

    private function isTimeInInterval($time, array $interval): bool
    {
        return $interval['from'] > $interval['to']
            ? $time >= $interval['from'] || $time < $interval['to']
            : $time >= $interval['from'] && $time < $interval['to'];
    }

    private function getGeofenceIntervals(int $id): array
    {
        if (isset($this->limits[$id])) {
            return $this->limits[$id];
        }

        $geofences = $this->history->getGeofences();

        /** @var Geofence $geofence */
        foreach ($geofences as $geofence) {
            if ($geofence->id !== $id) {
                continue;
            }

            return $this->limits[$id] = $geofence->additional['intervals'] ?? [];
        }

        return $this->limits[$id] = [];
    }
}