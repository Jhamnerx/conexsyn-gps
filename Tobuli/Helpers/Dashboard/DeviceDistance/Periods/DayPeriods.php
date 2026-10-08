<?php

namespace Tobuli\Helpers\Dashboard\DeviceDistance\Periods;

use Illuminate\Support\Carbon;

class DayPeriods extends AbstractPeriods
{
    protected function getPeriodLabel(string $from, string $to): string
    {
        return date('F j', strtotime($from));
    }

    protected function initFrom(Carbon $to): Carbon
    {
        return $to->clone()->startOfDay();
    }

    protected function nextFrom(Carbon $date): void
    {
        $date->subDay();
    }
}