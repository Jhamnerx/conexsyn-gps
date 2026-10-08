<?php

namespace App\Policies\Property;

use Tobuli\Entities\User;

class TaskPropertiesPolicy extends PropertyPolicy
{
    protected $entity = 'tasks';

    protected $editable = [
        'task_set_id',
    ];

    protected function taskSetIdEditPolicy(User $user): bool
    {
        return $user->perm('task_sets', 'view');
    }
}
