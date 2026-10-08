<?php

namespace Tobuli\Helpers\Alerts\Filter;

use Tobuli\Entities\User;
use Tobuli\Helpers\Alerts\Notification\AbstractNotification;

interface FilterInterface
{
    public function passes(AbstractNotification $notification, ?User $user = null): bool;
}