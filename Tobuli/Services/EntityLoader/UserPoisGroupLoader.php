<?php

namespace Tobuli\Services\EntityLoader;

use stdClass;
use Tobuli\Entities\PoiGroup;
use Tobuli\Entities\User;
use Tobuli\Services\EntityLoader\Filters\GroupIdFilter;
use Tobuli\Services\EntityLoader\Filters\GroupIdWithSearchFilter;
use Tobuli\Services\EntityLoader\Filters\IdFilter;
use Tobuli\Services\EntityLoader\Filters\SearchFilter;

class UserPoisGroupLoader extends EnityGroupLoader
{
    protected User $user;

    public function __construct(User $user)
    {
        $this->user = $user;

        $this->setQueryItems(
            $this->user->pois()
        );

        $this->setQueryGroups(
            PoiGroup::userOwned($this->user)
        );

        $this->filters = [
            new IdFilter('user_map_icons'),
            new GroupIdFilter('user_map_icons'),
            new GroupIdWithSearchFilter('user_map_icons'),
            new SearchFilter(null)
        ];
    }

    protected function transform($item)
    {
        $poi = new stdClass();

        $poi->id = $item->id;
        $poi->name = $item->name;

        $group_id = $item->group_id;
        $poi->group_id = empty($group_id) ? 0 : $group_id;

        return $poi;
    }
}