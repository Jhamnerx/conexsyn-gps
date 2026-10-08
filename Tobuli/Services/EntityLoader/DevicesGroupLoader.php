<?php


namespace Tobuli\Services\EntityLoader;


use stdClass;
use Tobuli\Entities\DeviceGroup;
use Tobuli\Entities\User;
use Tobuli\Services\EntityLoader\Filters\GroupIdFilter;
use Tobuli\Services\EntityLoader\Filters\GroupIdWithSearchFilter;
use Tobuli\Services\EntityLoader\Filters\IdFilter;
use Tobuli\Services\EntityLoader\Filters\SearchFilter;

class DevicesGroupLoader extends EnityGroupLoader
{
    /**
     * @var User
     */
    protected $user;

    public function __construct(User $user)
    {
        $this->user = $user;

        $this->setQueryItems(
            $this->user->accessibleDevicesWithGroups()->clearOrdersBy()
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

    protected function transform($device)
    {
        $item = new stdClass();

        $item->id = $device->id;
        $item->name = $device->name;

        $group_id = $device->group_id;
        $group_id = is_null($device->pivot) ? $group_id : $device->pivot->group_id;
        $item->group_id = empty($group_id) ? 0 : $group_id;

        return $item;
    }

    protected function scopeOrderDefault($query)
    {
        return $query->orderBy('user_device_pivot.group_id', 'asc')->orderBy('devices.name', 'asc');
    }
}
