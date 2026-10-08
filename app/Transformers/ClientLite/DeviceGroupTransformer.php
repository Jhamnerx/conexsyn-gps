<?php

namespace App\Transformers\ClientLite;

use Tobuli\Entities\DeviceGroup;

class DeviceGroupTransformer extends BaseTransformer
{

    public function transform(DeviceGroup $entity)
    {
        return [
            'id'        => $entity->id,
            'title'     => $entity->title,
            'count'     => $entity->items_count,
            'active'    => $entity->items_count === $entity->items_visible_count,
        ];
    }
}
