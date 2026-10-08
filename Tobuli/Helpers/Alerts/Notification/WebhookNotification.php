<?php

namespace Tobuli\Helpers\Alerts\Notification;

use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\ServerException;
use Illuminate\Support\Arr;
use Tobuli\Entities\Alert;
use Tobuli\Entities\Device;
use Tobuli\Entities\SendQueue;
use Tobuli\Helpers\Alerts\Notification\Input\InputAwareInterface;
use Tobuli\Helpers\Alerts\Notification\Input\InputMeta;
use Tobuli\Helpers\Alerts\Notification\Send\SendException;
use Tobuli\Helpers\Alerts\Notification\Send\SendingInterface;
use Tobuli\Helpers\ParsedCurl;

class WebhookNotification extends AbstractNotification implements InputAwareInterface, SendingInterface
{
    public function __construct()
    {
        $this->rules = [
            'input'   => 'required|array_max:' . config('tobuli.limits.alert_webhooks'),
            'input.*' => 'curl_request',
        ];
    }

    public function getInput(Alert $alert): InputMeta
    {
        $key = static::getKey();

        $alertData = Arr::get($alert->notifications ?? [], $key);

        return (new InputMeta($key, trans('validation.attributes.webhook_notification')))
            ->setActive(Arr::get($alertData, 'active', false))
            ->setInput(Arr::get($alertData, 'input', ''))
            ->setType(InputMeta::TYPE_STRING)
            ->setDescription(trans('front.webhook'));
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
        $data = $this->parseData($sendQueue);

        foreach ($this->parseReceivers($receiver) as $_receiver) {
            $curl = new ParsedCurl($_receiver);

            try {
                sendWebhook($curl->getFullUrl(), $data, $curl->getHeaders());
            } catch (ConnectException | ClientException | ServerException $e) {
                throw new SendException($e->getMessage(), $e->getCode(), $e);
            }
        }
    }

    protected function parseData(SendQueue $sendQueue)
    {
        $data = $sendQueue->data->toArray();

        $data['user'] = [
            'id'    => $sendQueue->user->id,
            'email' => $sendQueue->user->email,
            'phone_number' => $sendQueue->user->phone_number,
        ];

        if (!empty($data['latitude']) && !empty($data['longitude'])) {
            $data['address'] = getGeoAddress($data['latitude'], $data['longitude']);
        }

        $data['geofence'] = $sendQueue->data->geofence;

        $device = $sendQueue->data instanceof Device ? $sendQueue->data : $sendQueue->data->device;

        if ($device) {
            $data['device'] = $device->toArray();
            $data['sensors'] = $device->sensors->map(function ($sensor) use ($device) {
                $value = $sensor->getValueCurrent($device);

                return [
                    'id' => (int)$sensor->id,
                    'type' => $sensor->type,
                    'name' => $sensor->formatName(),
                    'unit' => $sensor->getUnit(),
                    'value' => $value->getValue(),
                    'formatted' => $value->getFormatted(),
                ];
            })->all();
        }

        unset($data['device']['traccar']);

        return $data;
    }

    protected function parseReceivers($receiver)
    {
        if (is_array($receiver))
            return $receiver;

        return semicol_explode($receiver);
    }
}