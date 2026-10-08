<?php


namespace Tobuli\Services\EntityLoader;


use Tobuli\Entities\DeviceGroup;
use Tobuli\Entities\User;
use Tobuli\Services\EntityLoader\Filters\GroupIdFilter;
use Tobuli\Services\EntityLoader\Filters\GroupIdWithSearchFilter;
use Tobuli\Services\EntityLoader\Filters\IdFilter;
use Tobuli\Services\EntityLoader\Filters\SearchFilter;

class UserDevicesGroupLoader extends DevicesGroupLoader
{
    protected $user;

    public function __construct(User $user)
    {
        parent::__construct($user);

        $this->setQueryItems(
            $this->user->devices()
                ->clearOrdersBy()
        );

        $this->setQueryGroups(
            DeviceGroup::where('user_id', $this->user->id)
        );

        $this->filters = [
            new IdFilter('devices'),
            new GroupIdFilter('user_device_pivot'),
            new GroupIdWithSearchFilter('user_device_pivot'),
            new SearchFilter(null)
        ];
    }

    protected function scopeOrderDefault($query)
    {
        return $query->orderBy('group_id', 'asc')->orderBy('devices.name', 'asc');
    }
}