<?php

namespace Tobuli\Helpers\Alerts\Notification;

use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\ServerException;
use Illuminate\Support\Arr;
use Tobuli\Entities\Alert;
use Tobuli\Entities\SendQueue;
use Tobuli\Entities\SmsTemplate;
use Tobuli\Entities\User;
use Tobuli\Exceptions\ValidationException;
use Tobuli\Helpers\Alerts\Notification\Input\InputAwareInterface;
use Tobuli\Helpers\Alerts\Notification\Input\InputMeta;
use Tobuli\Helpers\Alerts\Notification\Send\SendException;
use Tobuli\Helpers\Alerts\Notification\Send\SendingInterface;
use Tobuli\Helpers\SMS\SMSGatewayManager;

class SmsNotification extends AbstractNotification implements InputAwareInterface, SendingInterface
{
    public function __construct()
    {
        $this->rules = [
            'input' => 'required|array_max:' . config('tobuli.limits.alert_phones')
        ];
    }

    public function getInput(Alert $alert): InputMeta
    {
        $key = static::getKey();

        $alertData = Arr::get($alert->notifications ?? [], $key);

        return (new InputMeta($key, trans('validation.attributes.sms_notification')))
            ->setActive(Arr::get($alertData, 'active', false))
            ->setInput(Arr::get($alertData, 'input', ''))
            ->setType(InputMeta::TYPE_STRING)
            ->setDescription(trans('front.sms_semicolon'));
    }

    public function isEnabled(User $user): bool
    {
        return $user->canSendSMS();
    }

    public function canSend(SendQueue $sendQueue): bool
    {
        if ($this->isEnabled($sendQueue->user)) {
            return true;
        }

        $config = settings('sms_gateway');

        return $config && $config['enabled'] && $config['use_as_system_gateway'];
    }

    protected function prepareDataForValidation(array &$data): void
    {
        $data['input'] = semicol_explode(Arr::get($data, 'input'));
    }

    public function send(SendQueue $sendQueue, $receiver): void
    {
        $template = SmsTemplate::getTemplate($sendQueue->type, $sendQueue->user, 'event');
        $smsManager = new SMSGatewayManager();

        $gatewayArgs = $sendQueue->sender === SendQueue::SENDER_SYSTEM && settings('sms_gateway.use_as_system_gateway')
            ? ['request_method' => 'system']
            : null;

        try {
            $smsSenderService = $smsManager->loadSender($sendQueue->user, $gatewayArgs);
            $sms = $template->buildTemplate($sendQueue->data);

            $smsSenderService->send($receiver, $sms['body']);
        } catch (ConnectException | ClientException | ServerException | ValidationException $e) {
            throw new SendException($e->getMessage(), $e->getCode(), $e);
        }
    }
}