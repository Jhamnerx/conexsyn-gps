<?php namespace Tobuli\Helpers\Dashboard\Blocks;

use Illuminate\Support\Carbon;
use Tobuli\Helpers\Dashboard\DeviceDistance\Periods\DayPeriods;
use Tobuli\Helpers\Dashboard\DeviceDistance\Periods\WeekPeriods;
use Tobuli\Helpers\Dashboard\Traits\HasPeriodOption;

class DeviceDistanceBlock extends Block implements BlockInterface
{
    use HasPeriodOption;

    /**
     * @return string
     */
    protected function getName()
    {
        return 'device_distance';
    }

    /**
     * Devices distances grouped by selected intervals.
     *
     * @return array
     */
    protected function getContent()
    {
        $devices = $this->user
            ->devices()
            ->orderBy('updated_at','desc')
            ->limit(10)
            ->get()
            ->sortBy('timestamp');

        $periods = match ($this->getConfig('options.period')) {
            'day' => new DayPeriods($this->user, $devices),
            'week' => new WeekPeriods($this->user, $devices),
            default => throw new \InvalidArgumentException('Unsupported period')
        };

        $results = $periods->process(Carbon::now(), 5);

        return array_map(fn ($item) => json_encode($item), $results);
    }
}