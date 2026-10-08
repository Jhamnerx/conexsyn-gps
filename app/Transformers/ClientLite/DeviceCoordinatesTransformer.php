<?php

namespace App\Transformers\ClientLite;

use Tobuli\Entities\Device;
use Formatter;

class DeviceCoordinatesTransformer extends BaseTransformer
{
    public function transform(Device $entity)
    {
        $lat = $entity->lat;
        $lng = $entity->lng;

        if (is_null($lng) || is_null($lat))
            return [];

        return [
            'lat' => $lat,
            'lng' => $lng,
        ];
    }
}