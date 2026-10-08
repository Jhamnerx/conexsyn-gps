<?php

namespace App\Policies\Action;

use Tobuli\Entities\User;

class FlightsInfoPolicy extends ActionPolicy
{
    public function able(User $user): bool
    {
        if (!config('addon.flights_info')) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if (!$user->perm('flights_info', 'view')) {
            return false;
        }

        return true;
    }
}
