<?php

namespace Tobuli\Helpers\Templates\Builders;

class ExpiredSimTemplate extends ExpiredDeviceTemplate
{
    protected function variables($event)
    {
        $variables = parent::variables($event);

        $daysPassed = \Carbon::now()->diffInDays($event->device->sim_expiration_date);

        $variables['[days_passed]'] = $daysPassed;

        return $variables;
    }

    protected function placeholders()
    {
        $placeholders = parent::placeholders();

        $placeholders['[days_passed]'] = 'Days passed since device\'s SIM expiration';

        return $placeholders;
    }
}