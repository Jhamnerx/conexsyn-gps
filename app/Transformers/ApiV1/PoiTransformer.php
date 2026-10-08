<?php

namespace App\Transformers\ApiV1;

use App\Transformers\BaseTransformer;
use League\Fractal\Resource\Item;
use Tobuli\Entities\Poi;
use Tobuli\Entities\PoiGroup;

class PoiTransformer extends BaseTransformer {

    protected $availableIncludes = [
        'map_icon',
        'group',
    ];

    protected $defaultIncludes = [
        'map_icon',
    ];

    private PoiGroup $groupUngrouped;

    public function transform(?Poi $entity): ?array
    {
        if (! $entity) {
            return null;
        }

        return [
            'id'          => (int) $entity->id,
            'user_id'     => (int) $entity->user_id,
            'map_icon_id' => (int) $entity->map_icon_id,
            'group_id'    => (int) $entity->group_id,
            'active'      => (int) $entity->active,
            'name'        => (string) $entity->name,
            'description' => (string) $entity->description,
            'coordinates' => (string) json_encode($entity->coordinates),
            'created_at'  => (string) $entity->created_at,
            'updated_at'  => (string) $entity->updated_at,
        ];
    }

    public function includeMapIcon(Poi $entity): Item
    {
        return $this->item($entity->mapIcon, new MapIconTransformer(), false);
    }

    public function includeGroup(Poi $entity): Item
    {
        if (!$entity->group_id) {
            $entity->group = $this->getGroupUngrouped();
        }

        return $this->item($entity->group, new GroupTransformer(), false);
    }

    private function getGroupUngrouped(): PoiGroup
    {
        if (isset($this->groupUngrouped)) {
            return $this->groupUngrouped;
        }

        return $this->groupUngrouped = PoiGroup::makeUngrouped($this->user);
    }
}
