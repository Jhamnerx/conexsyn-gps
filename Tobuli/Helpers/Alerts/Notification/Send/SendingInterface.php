<?php

namespace Tobuli\Helpers\Alerts\Notification\Send;

use Tobuli\Entities\SendQueue;

interface SendingInterface
{
    public function canSend(SendQueue $sendQueue): bool;

    public function send(SendQueue $sendQueue, $receiver): void;
}