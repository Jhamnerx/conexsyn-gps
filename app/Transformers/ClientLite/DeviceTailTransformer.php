<?php

namespace App\Transformers\ClientLite;

use Tobuli\Entities\Device;
use Formatter;

class DeviceTailTransformer extends BaseTransformer
{
    public function transform(Device $entity)
    {
        return [
            'coordinates' => $entity->tail,
            'color'       => $entity->tail_color,
        ];
    }
}