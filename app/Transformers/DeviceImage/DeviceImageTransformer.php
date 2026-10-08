<?php

namespace App\Transformers\DeviceImage;

use App\Transformers\BaseTransformer;
use Tobuli\Entities\Device;
use Tobuli\Services\DeviceImageService;

class DeviceImageTransformer extends BaseTransformer
{
    public function transform(Device $entity): array
    {
        $path = (new DeviceImageService($entity))->get();

        return [
            'url' => $path ? route('api.device.image.index', $entity->id) : null,
        ];
    }
}