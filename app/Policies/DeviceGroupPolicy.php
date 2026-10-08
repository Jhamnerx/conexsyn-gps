<?php

namespace App\Policies;

use Illuminate\Database\Eloquent\Model;
use Tobuli\Entities\User;

class DeviceGroupPolicy extends Policy
{
    protected $permisionKey = null;

    public function store(User $user, Model $entity = null)
    {
        if ($user->isDemo())
            return false;

        return parent::store($user, $entity);
    }

    public function update(User $user, Model $entity)
    {
        if ($user->isDemo())
            return false;

        return parent::update($user, $entity);
    }

    public function destroy(User $user, Model $entity = null)
    {
        if ($user->isDemo())
            return false;

        return parent::destroy($user, $entity);
    }
}
