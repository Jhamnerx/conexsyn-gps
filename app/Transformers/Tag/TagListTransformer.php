<?php

namespace App\Transformers\Tag;

use App\Transformers\BaseTransformer;
use App\Transformers\User\UserBasicTransformer;
use Tobuli\Entities\Tag;

class TagListTransformer extends BaseTransformer
{
    protected $availableIncludes = [
        'user',
    ];

    public function transform(Tag $entity): array
    {
        return [
            'id'        => $entity->id,
            'user_id'   => $entity->user_id,
            'is_common' => $entity->is_common,
            'name'      => $entity->name,
            'color'     => $entity->color,
        ];
    }

    public function includeUser(Tag $entity)
    {
        return $this->item($entity->user, new UserBasicTransformer(), false);
    }
}