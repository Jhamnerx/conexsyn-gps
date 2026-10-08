<?php

namespace App\Transformers\ClientLite;

use Tobuli\Entities\Device;
use Formatter;

class DevicePositionTransformer extends BaseTransformer  {

    public function transform(Device $entity)
    {
        return [
            'speed' => $entity->speed,
            'lat' => $entity->lat,
            'lng' => $entity->lng,
            'cur' => $entity->course,
            'alt' => $entity->altitude,
        ];
    }
}