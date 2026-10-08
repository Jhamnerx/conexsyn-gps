<?php

namespace Tobuli\Helpers\Templates\Builders;

use Tobuli\Entities\Event;
use Tobuli\Helpers\Templates\Replacers\DeviceReplacer;

class ExpiredDeviceTemplate extends TemplateBuilder
{
    /**
     * @param Event $event
     * @return array
     */
    protected function variables($event)
    {
        $deviceReplacer = (new DeviceReplacer())->setUser($this->user)->setPrefix('device');

        $daysPassed = \Carbon::now()->diffInDays($event->device->expiration_date);

        return array_merge([
            '[days]'        => settings('main_settings.expire_notification.days_after'),
            '[days_passed]' => $daysPassed,
        ], $deviceReplacer->replacers($event->device));
    }

    protected function placeholders()
    {
        return array_merge([
            '[days]'        => 'Days after expiration',
            '[days_passed]' => 'Days passed since device\'s expiration',
        ], (new DeviceReplacer())->setPrefix('device')->placeholders());
    }
}