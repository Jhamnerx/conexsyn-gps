<?php

namespace Tobuli\Helpers\Alerts\Notification;

use Tobuli\Entities\Device;
use Tobuli\Entities\SendQueue;
use Tobuli\Helpers\Alerts\Notification\Send\SendingInterface;
use Tobuli\Services\Commands\SendCommandService;

class CommandNotification extends AbstractNotification implements SendingInterface
{
    private SendCommandService $sendCommandService;

    public function __construct()
    {
        $this->sendCommandService = new SendCommandService();
    }

    public function canSend(SendQueue $sendQueue): bool
    {
        return $this->isEnabled($sendQueue->user);
    }

    public function send(SendQueue $sendQueue, $receiver): void
    {
        $device = $sendQueue->data instanceof Device ? $sendQueue->data : $sendQueue->data->device;

        if ($device && $sendQueue->user->perm('send_command', 'view')) {
            $command = $receiver;
            $this->sendCommandService->gprs($device, $command, $sendQueue->user);
        }
    }
}