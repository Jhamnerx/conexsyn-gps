<?php

namespace App\Transformers\ClientLite;

use Tobuli\Entities\MapIcon;
use Tobuli\Entities\Poi;

class PoiTransformer extends BaseTransformer {

    protected $availableIncludes = [
        'icon',
    ];

    protected $defaultIncludes = [
        'icon'
    ];

    /**
     * @param Poi $entity
     * @return array|null
     */
    public function transform($entity)
    {
        return [
            'id'          => intval($entity->id),
            'group_id'    => $entity->group_id,
            'active'      => (bool)$entity->active,
            'name'        => $entity->name,
            'description' => $entity->description,
            'coordinates' => $entity->coordinates,
        ];
    }

    public function includeIcon(Poi $poi) {
        return $this->item($poi->mapIcon, new MapIconTransformer(), false);
    }

    protected static function requireLoads()
    {
        return ['mapIcon'];
    }
}