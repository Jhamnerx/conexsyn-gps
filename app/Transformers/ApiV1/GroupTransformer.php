<?php

namespace App\Transformers\ApiV1;

use App\Transformers\BaseTransformer;
use Tobuli\Entities\AbstractGroup;

class GroupTransformer extends BaseTransformer
{
    public function transform(?AbstractGroup $entity): ?array
    {
        if (!$entity) {
            return null;
        }

        return [
            'id' => $entity->id,
            'title' => $entity->title,
            'user_id' => $entity->user_id,
            'open' => (bool)$entity->open,
        ];
    }
}
