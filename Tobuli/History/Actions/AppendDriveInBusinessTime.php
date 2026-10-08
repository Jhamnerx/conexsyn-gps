<?php

namespace Tobuli\History\Actions;

use Tobuli\Services\ScheduleService;

class AppendDriveInBusinessTime extends ActionAppend
{
    private ?ScheduleService $scheduleService = null;

    public function boot()
    {
        $settings = settings('plugins.business_private_drive');
        $schedule = $settings['options']['schedule'] ?? null;

        if (!empty($settings['status'])
            && !empty($schedule['enabled'])
            && !empty($schedule['periods'])
        ) {
            $this->scheduleService = new ScheduleService($schedule['periods']);
        }
    }

    public function proccess(&$position)
    {
        if ($this->scheduleService === null) {
            $position->in_business_time = null;

            return;
        }

        $position->in_business_time = $this->scheduleService->inSchedules($position->time);
    }
}