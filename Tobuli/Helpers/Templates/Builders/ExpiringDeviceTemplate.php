<?php namespace Tobuli\Helpers\Templates\Builders;

use Tobuli\Entities\Event;
use Tobuli\Helpers\Templates\Replacers\DeviceReplacer;

class ExpiringDeviceTemplate extends TemplateBuilder
{
    /**
     * @param Event $event
     * @return array
     */
    protected function variables($event)
    {
        $deviceReplacer = (new DeviceReplacer())->setUser($this->user)->setPrefix('device');

        $daysLeft = \Carbon::now()->diffInDays($event->device->expiration_date);

        return array_merge([
            '[days]' => settings('main_settings.expire_notification.days_before'),
            '[days_left]' => $daysLeft,
        ], $deviceReplacer->replacers($event->device));
    }

    protected function placeholders()
    {
        return array_merge([
            '[days]'        => 'Days before expiration',
            '[days_left]'   => 'Days left to device\'s expiration',
        ], (new DeviceReplacer())->setPrefix('device')->placeholders());
    }

}