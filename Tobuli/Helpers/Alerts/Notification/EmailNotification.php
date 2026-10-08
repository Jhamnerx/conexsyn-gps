<?php

namespace Tobuli\Helpers\Alerts\Notification;

use Illuminate\Support\Arr;
use Tobuli\Entities\Alert;
use Tobuli\Entities\EmailTemplate;
use Tobuli\Entities\SendQueue;
use Tobuli\Helpers\Alerts\Notification\Input\InputAwareInterface;
use Tobuli\Helpers\Alerts\Notification\Input\InputMeta;
use Tobuli\Helpers\Alerts\Notification\Send\SendingInterface;

class EmailNotification extends AbstractNotification implements InputAwareInterface, SendingInterface
{
    public function __construct()
    {
        $this->rules = [
            'input'   => 'required|array_max:' . config('tobuli.limits.alert_emails'),
            'input.*' => 'email',
        ];
    }

    public function getInput(Alert $alert): InputMeta
    {
        $key = static::getKey();

        $alertData = Arr::get($alert->notifications ?? [], $key);

        return (new InputMeta($key, trans('validation.attributes.email_notification')))
            ->setActive(Arr::get($alertData, 'active', false))
            ->setInput(Arr::get($alertData, 'input', ''))
            ->setType(InputMeta::TYPE_STRING)
            ->setDescription(trans('front.email_semicolon'));
    }

    protected function prepareDataForValidation(array &$data): void
    {
        $data['input'] = semicol_explode(Arr::get($data, 'input'));
    }

    public function canSend(SendQueue $sendQueue): bool
    {
        return $this->isEnabled($sendQueue->user);
    }

    public function send(SendQueue $sendQueue, $receiver): void
    {
        $template = EmailTemplate::getTemplate($sendQueue->type, $sendQueue->user, 'event');

        sendTemplateEmail($receiver, $template, $sendQueue->data);
    }
}