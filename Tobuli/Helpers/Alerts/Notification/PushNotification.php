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
use Tobuli\Helpers\Formatter\Facades\Formatter;
use Tobuli\Services\FcmService;

class PushNotification extends AbstractNotification implements InputAwareInterface, SendingInterface
{
    private FcmService $fcmService;

    public function __construct()
    {
        $this->fcmService = new FcmService();
    }

    public function getInput(Alert $alert): InputMeta
    {
        $key = static::getKey();

        $alertData = Arr::get($alert->notifications ?? [], $key);

        return (new InputMeta($key, trans('validation.attributes.push_notification')))
            ->setActive(Arr::get($alertData, 'active', !$alert->exists))
            ->setInput(null);
    }

    public function canSend(SendQueue $sendQueue): bool
    {
        return $this->isEnabled($sendQueue->user);
    }

    public function send(SendQueue $sendQueue, $receiver): void
    {
        $device = $sendQueue->data instanceof Device ? $sendQueue->data : $sendQueue->data->device;

        switch ($sendQueue->type) {
            case 'expiring_user':
            case 'expired_user':
                $title = $sendQueue->data->email;
                $body = '';
                break;
            default:
                $title = ($device->name ?? '') . ' ' . $sendQueue->data->message;
                $body = trans('front.speed') . ': ' . Formatter::speed()->human($sendQueue->data->speed);

                if (in_array($sendQueue->type, ['zone_out', 'zone_in'])) {
                    $body .= "\n" . trans('front.geofence') . ': ' . $sendQueue->data->geofence->name;

                    $sendQueue->data->makeHidden('geofence');
                }
                break;
        }

        $data = $sendQueue->data ? $sendQueue->data->toArray() : [];

        try {
            $this->fcmService->send($sendQueue->user, $title, $body, $data);
        } catch (ConnectException | ClientException | ServerException $e) {
            throw new SendException($e->getMessage(), $e->getCode(), $e);
        }
    }
}