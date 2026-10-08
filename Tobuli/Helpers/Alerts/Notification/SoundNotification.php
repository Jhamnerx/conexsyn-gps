<?php

namespace Tobuli\Helpers\Alerts\Notification;

use Illuminate\Support\Arr;
use Tobuli\Entities\Alert;
use Tobuli\Helpers\Alerts\Notification\Input\InputAwareInterface;
use Tobuli\Helpers\Alerts\Notification\Input\SelectMeta;
use Tobuli\Services\AlertSoundService;

class SoundNotification extends AbstractNotification implements InputAwareInterface
{
    public function getInput(Alert $alert): SelectMeta
    {
        $key = static::getKey();

        $alertData = Arr::get($alert->notifications ?? [], $key);

        return (new SelectMeta($key, trans('validation.attributes.sound_notification')))
            ->setActive(Arr::get($alertData, 'active', !$alert->exists))
            ->setInput(Arr::get($alertData, 'input', ''))
            ->setOptions(toOptions(AlertSoundService::getList()));
    }
}