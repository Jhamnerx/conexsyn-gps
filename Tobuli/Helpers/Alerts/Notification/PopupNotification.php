<?php

namespace Tobuli\Helpers\Alerts\Notification;

use Illuminate\Support\Arr;
use Tobuli\Entities\Alert;
use Tobuli\Helpers\Alerts\Notification\Input\InputAwareInterface;
use Tobuli\Helpers\Alerts\Notification\Input\SelectMeta;

class PopupNotification extends AbstractNotification implements InputAwareInterface
{
    protected array $rules = [
        'input' => 'required|in:0,5,10',
    ];

    public function getInput(Alert $alert): SelectMeta
    {
        $key = static::getKey();

        $data = $alert->notifications ?? [];
        $alertData = Arr::get($data, $key);

        return (new SelectMeta($key, trans('validation.attributes.popup_notification')))
            ->setActive(Arr::get($alertData, 'active', !$alert->exists))
            ->setInput(Arr::get($alertData, 'input', Arr::get($data, 'auto_hide.active', true) ? 10 : 0))
            ->setOptions(toOptions([
                0 => trans('front.sticky'),
                5 => '5 ' . trans('front.second_short'),
                10 => '10 ' . trans('front.second_short'),
            ]));
    }
}