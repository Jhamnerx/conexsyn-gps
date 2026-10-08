<?php

namespace Tobuli\Helpers\Templates\Builders;

use Tobuli\Entities\User;
use Tobuli\Helpers\Templates\Replacers\UserReplacer;

class ExpiringUserTemplate extends TemplateBuilder
{
    /**
     * @param User $user
     * @return array
     */
    protected function variables($user)
    {
        $userReplacer = (new UserReplacer())->setUser($this->user);

        $daysLeft = \Carbon::now()->diffInDays($user->expiration_date);

        return array_merge([
            '[days]'        => settings('main_settings.expire_notification.days_before'),
            '[days_left]'   => $daysLeft,
        ], $userReplacer->replacers($user));
    }

    protected function placeholders()
    {
        return array_merge([
            '[days]'        => 'Days before expiration',
            '[days_left]'   => 'Days left to user\'s expiration',
        ], (new UserReplacer())->placeholders());
    }
}