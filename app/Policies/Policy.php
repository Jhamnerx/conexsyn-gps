<?php

namespace App\Policies;

use Exception;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

use Tobuli\Entities\User;

class Policy
{
    use HandlesAuthorization;

    protected $permisionKey = null;

    public function additionalCheck(User $user, ?Model $entity, string $mode)
    {
        return true;
    }

    public function viewAny(User $user)
    {
        return true;
    }

    public function view(User $user, Model $entity = null)
    {
        if (! $this->additionalCheck($user, $entity, 'view')) {
            return false;
        }

        if ($user->isAdmin())
            return true;

        if(!$this->hasPermission($user, $entity,'view'))
            return false;

        if ($entity && $entity->exists)
            return $this->ownership($user, $entity);

        return true;
    }

    public function create(User $user, Model $entity = null)
    {
        if (! $this->additionalCheck($user, $entity, 'edit')) {
            return false;
        }

        if ($this->isReadOnlyUser($user))
            return false;

        if ($user->isAdmin())
            return true;

        if(!$this->hasPermission($user, $entity,'edit'))
            return false;

        if ($entity && $entity->exists)
            return $this->ownership($user, $entity);

        return true;

    }

    public function store(User $user, Model $entity = null)
    {
        if ($this->isReadOnlyUser($user))
            return false;

        return $this->create($user, $entity);
    }

    public function show(User $user, Model $entity)
    {
        if (! $this->additionalCheck($user, $entity, 'view')) {
            return false;
        }

        if ($user->isAdmin())
            return true;

        if(!$this->hasPermission($user, $entity,'view'))
            return false;

        return $this->ownership($user, $entity);
    }

    public function edit(User $user, Model $entity = null)
    {
        if (! $this->additionalCheck($user, $entity, 'edit')) {
            return false;
        }

        if ($user->isAdmin())
            return true;

        if(!$this->hasPermission($user, $entity, 'edit'))
            return false;

        return $this->ownership($user, $entity);

    }

    public function update(User $user, Model $entity)
    {
        if ($this->isReadOnlyUser($user))
            return false;

        return $this->edit($user, $entity);
    }

    public function remove(User $user, Model $entity = null)
    {
        return $this->destroy($user, $entity);
    }

    public function destroy(User $user, Model $entity = null)
    {
        return $this->clean($user, $entity);
    }

    public function clean(User $user, Model $entity = null)
    {
        if (! $this->additionalCheck($user, $entity, 'remove')) {
            return false;
        }

        if ($this->isReadOnlyUser($user))
            return false;

        if ($user->isAdmin())
            return true;

        if(!$this->hasPermission($user, $entity, 'remove'))
            return false;

        if ($entity && $entity->exists)
            return $this->ownership($user, $entity);

        return true;
    }

    public function active(User $user, Model $entity)
    {
        if (! $this->additionalCheck($user, $entity, 'active')) {
            return false;
        }

        if ($user->isDemo())
            return false;

        if ($this->isReadOnlyUser($user))
            return false;

        return $this->view($user, $entity);
    }

    public function own(User $user, Model $entity) {
        if (! $this->additionalCheck($user, $entity, 'own')) {
            return false;
        }

        return $this->ownership($user, $entity);
    }

    protected function hasPermission(User $user, Model $entity, $mode)
    {
        if (!$this->permisionKey)
            return true;

        return $user->perm($this->permisionKey, $mode);
    }

    protected function ownership(User $user, Model $entity)
    {
        if ($user->isSupervisor()) {
            return true;
        }

        if (method_exists($entity, 'users') && $entity->users() instanceof BelongsToMany)
            return $this->ownershipMany($user, $entity);

        if (method_exists($entity, 'users') && $entity->users() instanceof HasMany)
            return $this->ownershipMany($user, $entity);

        if (method_exists($entity, 'user') && $entity->user() instanceof BelongsTo)
            return $this->ownershipOne($user, $entity);

        if (method_exists($entity, 'user') && $entity->user() instanceof HasOne)
            return $this->ownershipOne($user, $entity);

        throw new Exception("Class '".get_class($entity)."' dont have User relations");
    }

    protected function ownershipOne(User $user, Model $entity)
    {
        if ( ! $entity->user)
            return false;

        if ($entity->user->id === $user->id)
            return true;

        if ($user->isManager() && $entity->user->manager_id === $user->id)
            return true;

        return false;
    }

    protected function ownershipMany(User $user, Model $entity)
    {
        if ( ! $entity->users)
            return false;

        if ($entity->users->contains($user->id))
            return true;

        if ($user->isManager() && $entity->users->where('manager_id', $user->id)->all())
            return true;

        return false;
    }

    protected function isReadOnlyUser(User $user)
    {
        $secondaryCredentials = $user->getLoginSecondaryCredentials();

        if (empty($secondaryCredentials)) {
            return false;
        }

        return $secondaryCredentials->readonly ?? false;
    }
}
