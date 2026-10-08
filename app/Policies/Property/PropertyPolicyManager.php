<?php

namespace App\Policies\Property;

use Illuminate\Database\Eloquent\Model;
use Tobuli\Entities\Device;
use Tobuli\Entities\Task;
use Tobuli\Entities\User;

class PropertyPolicyManager
{
    protected $policyMap = [
        User::class => UserPropertiesPolicy::class,
        Device::class => DevicePropertiesPolicy::class,
        Task::class => TaskPropertiesPolicy::class,
    ];

    public function policyFor(Model $entity)
    {
        return new $this->policyMap[get_class($entity)];
    }
}