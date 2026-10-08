<?php

namespace Tobuli\Helpers\Dashboard\DeviceDistance\Periods;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Tobuli\Entities\User;

abstract class AbstractPeriods
{
    public function __construct(
        protected User $user,
        protected Collection $devices,
    ) {}

    public function process(Carbon $to, int $periodsAmount): array
    {
        $from = $this->initFrom($to);
        $i = 0;

        $results = [];
        $keys = [];

        while ($i < $periodsAmount) {
            $fromFormatted = $from->toDateString();

            foreach ($this->devices as $device)
            {
                try {
                    $data = $device->getDistanceBetween($fromFormatted, $to->toDateTimeString());
                } catch (\Exception) {
                    $data = 0;
                }

                $results[$device->name][] = [$i, $data];
            }

            $keys[] = $this->getPeriodLabel($fromFormatted, $to->toDateString());
            $to = clone $from;
            $this->nextFrom($from);
            $i++;
        }

        return [
            'data' => $results,
            'keys' => $keys,
        ];
    }

    abstract protected function getPeriodLabel(string $from, string $to): string;

    abstract protected function initFrom(Carbon $to): Carbon;

    abstract protected function nextFrom(Carbon $date): void;
}