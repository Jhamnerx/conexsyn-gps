<?php

namespace App\Transformers\ClientLite;

use Tobuli\Entities\Route;

class RoutesTransformer extends BaseTransformer {

    /**
     * @param Route $entity
     * @return array|null
     */
    public function transform($entity)
    {
        return [
            'id'          => intval($entity->id),
            'group_id'    => $entity->group_id,
            'name'        => $entity->name,
            'active'      => (bool)$entity->active,
            'color'       => $entity->color,
            'coordinates' => $entity->coordinates,
        ];
    }
}