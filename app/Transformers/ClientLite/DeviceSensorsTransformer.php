<?php

namespace App\Transformers\ClientLite;

use Tobuli\Entities\Device;
use Formatter;

class DeviceSensorsTransformer extends DeviceTransformer
{

    public function transform(Device $entity)
    {
        $sensors = [];

        foreach ($entity->sensors as $sensor) {
            $value = $sensor->getValueCurrent($entity);

            $sensors[] = [
                'id'       => (int)$sensor->id,
                'type'     => $sensor->type,
                'name'     => $sensor->formatName(),
                'value'    => $value->getFormatted(),
                'icon'     => $sensor->getIconAsset($value->getValue()),
            ];
        }

        return $sensors;
    }
}