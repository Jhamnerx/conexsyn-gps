<?php

namespace Tobuli\Helpers\Alerts\Filter;

use Tobuli\Entities\User;
use Tobuli\Helpers\Alerts\Notification\AbstractNotification;

class EnabledFilter implements FilterInterface
{
    public function passes(AbstractNotification $notification, ?User $user = null): bool
    {
        return $user === null || $notification->isEnabled($user);
    }
}