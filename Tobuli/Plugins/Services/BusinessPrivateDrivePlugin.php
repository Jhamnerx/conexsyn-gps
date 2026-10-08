<?php

namespace Tobuli\Plugins\Services;

use Tobuli\Plugins\Contracts\NormalizationAware;
use Tobuli\Plugins\Contracts\PluginInterface;
use Tobuli\Plugins\Contracts\ValidationAware;
use Tobuli\Plugins\Contracts\ValidationTrait;
use Tobuli\Services\ScheduleService;

class BusinessPrivateDrivePlugin implements PluginInterface, ValidationAware, NormalizationAware
{
    use ValidationTrait;

    protected array $customAttributes;
    protected array $rules = [
        'options.schedule.periods' => 'required_if:options.schedule.enabled,1|array',
    ];

    public function __construct()
    {
        $this->customAttributes = [
            'options.schedule.periods' => trans('front.business_time'),
        ];
    }

    public function normalize(array &$input)
    {
        $schedule = $input['options']['schedule'] ?? null;

        if (empty($schedule['periods'])) {
            $input['options']['schedule']['periods'] = null;

            return;
        }

        $scheduleService = new ScheduleService();
        $scheduleService->validate($schedule['periods'], 'periods');
        $input['options']['schedule']['periods'] = $scheduleService->setFormSchedules($schedule['periods']);
    }
}