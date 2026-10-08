<?php

namespace App\Transformers\UserClient;

use App\Transformers\BaseTransformer;
use Tobuli\Entities\Client;

class UserClientFullTransformer extends BaseTransformer
{
    public function transform(?Client $entity): array
    {
        if (!$entity) {
            return [];
        }

        return [
            'id'            => $entity->id,
            'first_name'    => $entity->first_name,
            'last_name'     => $entity->last_name,
            'birth_date'    => $entity->birth_date,
            'personal_code' => $entity->personal_code,
            'address'       => $entity->address,
            'comment'       => $entity->comment,
        ];
    }
}