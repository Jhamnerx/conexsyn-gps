<?php

namespace Tobuli\History\Actions;

use Illuminate\Support\Collection;

class AppendFuelPeaks extends AppendSensorValuePeaks
{
    public static string $peakKey = 'fuel';

    protected function getSensors(): Collection
    {
        return $this->getDevice()->sensors->filter(fn ($sensor) => $sensor->type == 'fuel_tank');
    }
}