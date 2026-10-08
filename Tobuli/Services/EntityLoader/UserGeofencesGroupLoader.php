<?php


namespace Tobuli\Services\EntityLoader;


use stdClass;
use Tobuli\Entities\GeofenceGroup;
use Tobuli\Entities\User;
use Tobuli\Services\EntityLoader\Filters\GroupIdFilter;
use Tobuli\Services\EntityLoader\Filters\GroupIdWithSearchFilter;
use Tobuli\Services\EntityLoader\Filters\IdFilter;
use Tobuli\Services\EntityLoader\Filters\SearchFilter;

class UserGeofencesGroupLoader extends EnityGroupLoader
{
    /**
     * @var User
     */
    protected $user;

    public function __construct(User $user)
    {
        $this->user = $user;

        $this->setQueryItems(
            $this->user->geofences()
        );

        $this->setQueryGroups(
            GeofenceGroup::userOwned($this->user)
        );

        $this->filters = [
            new IdFilter('geofences'),
            new GroupIdFilter('geofences'),
            new GroupIdWithSearchFilter('geofences'),
            new SearchFilter(null)
        ];
    }

    protected function transform($geofence)
    {
        $item = new stdClass();

        $item->id = $geofence->id;
        $item->name = $geofence->name;

        $group_id = $geofence->group_id;
        $item->group_id = empty($group_id) ? 0 : $group_id;

        return $item;
    }
}