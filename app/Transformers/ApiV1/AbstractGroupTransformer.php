<?php

namespace App\Transformers\ApiV1;

use App\Transformers\BaseTransformer;
use Tobuli\Entities\AbstractGroup;

class AbstractGroupTransformer extends BaseTransformer
{
    public function transform(?AbstractGroup $entity): ?array
    {
        if (!$entity) {
            return null;
        }

        return [
            'id' => (int)$entity->id,
            'user_id' => (int)$entity->user_id,
            'title' => (string)$entity->title,
            'open' => (bool)$entity->open,
        ];
    }
}
