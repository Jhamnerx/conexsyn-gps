<?php

namespace Tobuli\Helpers\Dashboard\DeviceDistance\Periods;

use Illuminate\Support\Carbon;

class WeekPeriods extends AbstractPeriods
{
    protected function getPeriodLabel(string $from, string $to): string
    {
        return date('M j', strtotime($from)) . ' – ' . date('M j', strtotime($to));
    }

    protected function initFrom(Carbon $to): Carbon
    {
        return $to->clone()->startOfWeek($this->user->week_start_day);
    }

    protected function nextFrom(Carbon $date): void
    {
        $date->subWeek();
    }
}