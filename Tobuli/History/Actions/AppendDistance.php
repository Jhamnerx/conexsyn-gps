<?php

namespace Tobuli\History\Actions;


class AppendDistance extends ActionAppend
{
    private int $strategy;

    static public function required()
    {
        $list = [];

        if (config('addon.distance_calc_strategy')) {
            $list[] = AppendOdometer::class;
        }

        $list[] = AppendDistanceGPS::class;

        return $list;
    }

    public function boot()
    {
        $this->strategy = $this->getStrategy();
    }

    private function getStrategy(): int
    {
        if (config('addon.distance_calc_strategy')) {
            $sensor = $this->getDevice()
                ->getSensorsByType('odometer')
                ->firstWhere('shown_value_by', 'connected_odometer');

            if ($sensor) {
                return 1;
            }
        }

        return 0;
    }

    public function proccess(&$position)
    {
        switch ($this->strategy) {
            case 1:
                $prev = $this->getPrevPosition();

                $position->distance = $prev
                    ? $position->odometer - $prev->odometer
                    : 0;
                break;
            default:
                $position->distance = $position->distance_gps;
        }
    }
}