<?php

namespace App\Transformers\Tag;

use App\Transformers\BaseTransformer;
use App\Transformers\Device\DeviceListTransformer;
use App\Transformers\User\UserBasicTransformer;
use Tobuli\Entities\Tag;

class TagFullTransformer extends BaseTransformer
{
    protected $availableIncludes = [
        'user',
        'devices',
    ];

    protected $defaultIncludes = [
        'devices',
    ];

    public function transform(Tag $entity)
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

    public function includeDevices(Tag $entity)
    {
        return $this->collection($entity->devices, new DeviceListTransformer(), false);
    }
}