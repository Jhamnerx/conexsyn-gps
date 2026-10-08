<?php

namespace Tobuli\Helpers\Templates\Builders;

class ExpiringSimTemplate extends ExpiringDeviceTemplate
{
    protected function variables($event)
    {
        $variables = parent::variables($event);

        $daysLeft = \Carbon::now()->diffInDays($event->device->sim_expiration_date);

        $variables['[days_left]'] = $daysLeft;

        return $variables;
    }

    protected function placeholders()
    {
        $placeholders = parent::placeholders();

        $placeholders['[days_left]'] = 'Days left to device\'s SIM expiration';

        return $placeholders;
    }
}