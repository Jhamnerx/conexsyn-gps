<?php

namespace App\Policies;

use Illuminate\Database\Eloquent\Model;
use Tobuli\Entities\User;


class DevicePolicy extends Policy
{
    protected $permisionKey = 'devices';

    public function enable(User $user, Model $device)
    {
        return $this->update($user, $device);
    }

    public function disable(User $user, Model $device)
    {
        return $this->update($user, $device);
    }

    public function edit(User $user, Model $device = null)
    {
        if (!$user->isAdmin() && $device->user_id && $device->user_id != $user->id) {
            return false;
        }

        return parent::edit($user, $device);
    }

    public function destroy(User $user, Model $device = null)
    {
        if (!$user->isAdmin() && $device->user_id && $device->user_id != $user->id) {
            return false;
        }

        return parent::destroy($user, $device);
    }
}
