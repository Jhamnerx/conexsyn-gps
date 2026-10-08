<?php


namespace Tobuli\Services\EntityLoader;


use stdClass;
use Tobuli\Entities\User;
use Tobuli\Services\EntityLoader\Filters\IdFilter;
use Tobuli\Services\EntityLoader\Filters\SearchFilter;

class DevicesLoader extends EnityLoader
{
    /**
     * @var User
     */
    protected $user;

    public function __construct(User $user)
    {
        $this->user = $user;

        $this->setQueryItems(
            $this->user->accessibleDevices()
                ->clearOrdersBy()
        );

        $this->setRequestKey('devices');

        $this->filters = [
            new IdFilter('devices'),
            new SearchFilter(null)
        ];
    }

    protected function transform($device)
    {
        $item = new stdClass();

        $item->id = $device->id;
        $item->name = $device->name;

        return $item;
    }

    protected function scopeOrderDefault($query)
    {
        return $query->orderBy('devices.name', 'asc');
    }
}