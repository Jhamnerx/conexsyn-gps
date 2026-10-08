<?php

namespace Tobuli\History\Actions;


use Illuminate\Database\QueryException;

class AppendOdometerVirtualDistance extends ActionAppend
{
    protected $sensors = [];

    static public function required(){
        return [
            AppendDistanceGPS::class
        ];
    }

    public function boot()
    {
        $sensors = $this->getDevice()->getSensorsByType('odometer');

        if ( ! $sensors)
            return;

        $distance = null;

        foreach ($sensors as & $sensor)
        {
            if ($sensor->shown_value_by != 'virtual_odometer')
                continue;

            if (is_null($distance)) {
                try {
                    $distance = $this->getDevice()
                        ->positions()
                        ->where('time', '>=', $this->getDateFrom())
                        ->where('valid', '>', 0)
                        ->sum('distance');

                } catch (QueryException $exception) {
                    if ($exception->getCode() != '42S02') {
                        throw $exception;
                    }

                    $distance = 0;
                }
            }

            $value = floatval($sensor->value);

            $sensor->value = $value ? ($value - $distance) : $value;

            $this->sensors[] = $sensor;
        }
    }

    public function proccess(&$position)
    {
        foreach ($this->sensors as $sensor) {
            $sensor->value += $position->distance_gps;
        }
    }
}