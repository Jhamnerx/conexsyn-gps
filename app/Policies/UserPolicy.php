<?php

namespace App\Policies;

use Illuminate\Database\Eloquent\Model;
use Tobuli\Entities\User;

class UserPolicy extends Policy
{
    protected $permisionKey = 'users';

    protected function ownership(User $user, Model $entity)
    {
        if ($user->isSupervisor() && !$entity->isAdmin()) {
            return true;
        }

        if ($user->isManager() && $user->id == $entity->manager_id)
            return true;

        if ($user->id == $entity->id)
            return true;

        if ($entity && !$entity->exists)
            return true;

        return false;
    }

    protected function hasPermission(User $user, Model $entity, $mode)
    {
        if ($user->id == $entity->id && in_array($mode, ['view', 'edit']))
            return true;

        return parent::hasPermission($user, $entity, $mode);
    }

    public function destroy(User $user, Model $entity = null)
    {
        if (!$this->additionalCheck($user, $entity, 'remove')) {
            return false;
        }

        if ($user->id == $entity->id)
            return false;

        if ($user->isAdmin())
            return true;

        return $this->clean($user, $entity);
    }

    public function login_as(User $user, Model $entity = null)
    {
        if (!$this->additionalCheck($user, $entity, 'login_as')) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if (!$this->hasPermission($user, $entity, 'edit')) {
            return false;
        }

        return $this->ownership($user, $entity);
    }

    /**
     * @param  User|null  $entity
     */
    public function additionalCheck(User $user, ?Model $entity, string $mode): bool
    {
        if ($mode === 'view' || $mode === 'own') {
            return true;
        }

        return !$entity->untouchable || $user->id === $entity->id;
    }
}
