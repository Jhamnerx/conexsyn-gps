<?php

namespace App\Transformers\ClientLite;

use Tobuli\Entities\Geofence;

class GeofenceTransformer extends BaseTransformer {

    /**
     * @param Geofence $entity
     * @return array|null
     */
    public function transform($entity)
    {
        return [
            'id'          => intval($entity->id),
            'group_id'    => $entity->group_id,
            'name'        => $entity->name,
            'active'      => (bool)$entity->active,
            'color'       => $entity->polygon_color,
            'type'        => $entity->type,

            'coordinates' => $entity->type == Geofence::TYPE_POLYGON
                ? json_decode($entity->coordinates, true)
                : null,
            'radius' => $entity->type == Geofence::TYPE_CIRCLE
                ? $entity->radius
                : null,
            'center' => $entity->type == Geofence::TYPE_CIRCLE
                ? $entity->center
                : null,
        ];
    }
}