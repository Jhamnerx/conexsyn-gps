<?php

namespace App\Policies;

use Illuminate\Database\Eloquent\Model;
use Tobuli\Entities\User;

class TagPolicy extends Policy
{
    protected $permisionKey = 'tags';

    public function additionalCheck(User $user, ?Model $entity, string $mode)
    {
        return config('addon.tags');
    }
}
