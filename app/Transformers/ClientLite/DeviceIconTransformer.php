<?php

namespace App\Transformers\ClientLite;


use Tobuli\Entities\Device;

class DeviceIconTransformer extends DeviceTransformer {

    public function transform(Device $entity)
    {
        if (!$entity->icon || $entity->icon->type == 'arrow') {
            return [
                'width'  => null,
                'height' => null,
                'url'    => null,
                'color'  => self::colorConvert( $entity->getStatusColor()),
                'course' => $entity->course,
            ];
        }

        return [
            'width'  => (int) $entity->icon->width,
            'height' => (int) $entity->icon->height,
            'url'    => asset($entity->icon->path),
            'color'  => null,
            'course' => $entity->icon->type == 'rotating' ? $entity->course : null,
        ];
    }
}