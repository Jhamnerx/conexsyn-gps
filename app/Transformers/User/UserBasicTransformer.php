<?php

namespace App\Transformers\User;

use App\Transformers\BaseTransformer;
use Tobuli\Entities\User;

class UserBasicTransformer extends BaseTransformer {

    public function transform(User $entity)
    {
        return [
            'id' => $entity->id,
            'email' => $entity->email,
            'active' => $entity->active,
        ];
    }
}