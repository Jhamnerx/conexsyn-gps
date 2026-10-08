<?php

namespace App\Transformers\Device;

use App\Transformers\BaseTransformer;
use Tobuli\Entities\Device;

class DeviceIconTransformer extends DeviceTransformer {

    public function transform(Device $entity)
    {
        if (empty($entity->icon_id))
            return $this->defaultIcon($entity->getStatusColor());

        return array_merge(
            $entity->icon->toArray(),
            ['color' => null]
        );
    }

    protected function defaultIcon($color)
    {
        return [
            'id'      => 0,
            'user_id' => null,
            'order'   => 1,
            'type'    => 'arrow',
            'width'   => 25,
            'height'  => 33,
            'path'    => 'assets/images/arrow-ack.png',
            'color'   => $color,

            'by_status' => 0,
        ];
    }
}