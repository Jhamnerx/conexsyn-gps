<?php

namespace App\Transformers\Device;

use App\Transformers\BaseTransformer;
use Tobuli\Entities\Device;

class DeviceSensorsTransformer extends DeviceTransformer {

    public function transform(Device $entity)
    {
        $sensors = [];

        foreach ($entity->sensors as $sensor) {
            $value = $sensor->getValueCurrent($entity->traccar);

            $sensors[] = [
                'id'       => (int)$sensor->id,
                'type'     => $sensor->type,
                'name'     => $sensor->formatName(),
                'unit'     => $sensor->getUnit(),
                'value'    => $value->getValue(),
                'formated' => $value->getFormatted(),
            ];
        }

        return $sensors;
    }
}