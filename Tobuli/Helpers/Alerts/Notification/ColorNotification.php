<?php

namespace Tobuli\Helpers\Alerts\Notification;

use Illuminate\Support\Arr;
use Tobuli\Entities\Alert;
use Tobuli\Helpers\Alerts\Notification\Input\InputAwareInterface;
use Tobuli\Helpers\Alerts\Notification\Input\InputMeta;

class ColorNotification extends AbstractNotification implements InputAwareInterface
{
    protected array $rules = [
        'input' => 'required|css_color',
    ];

    public function getInput(Alert $alert): InputMeta
    {
        $key = static::getKey();

        $alertData = Arr::get($alert->notifications ?? [], $key);

        return (new InputMeta($key, trans('validation.attributes.color')))
            ->setActive(Arr::get($alertData, 'active', false))
            ->setInput(Arr::get($alertData, 'input', ''))
            ->setType(InputMeta::TYPE_COLOR);
    }
}