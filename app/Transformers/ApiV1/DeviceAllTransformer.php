<?php

namespace App\Transformers\ApiV1;

use App\Transformers\BaseTransformer;
use Tobuli\Entities\Device;

class DeviceAllTransformer extends BaseTransformer {

    protected static function requireLoads()
    {
        return ['sensors', 'traccar'];
    }

    public function transform(Device $entity)
    {
        return [
            'name' => $entity->name,
            'imei' => $entity->imei,
            'lat'  => floatval($entity->lat),
            'lng'  => floatval($entity->lng),
            'sensors' => $this->formatSensors($entity)
        ];
    }

    protected function formatSensors($entity)
    {
        $result = [];

        foreach ($entity->sensors as $sensor) {
            if (in_array($sensor->type, ['harsh_acceleration', 'harsh_breaking', 'harsh_turning']))
                continue;

            $value = $sensor->getValueCurrent($entity);

            $result[] = [
                'id'            => $sensor->id,
                'type'          => $sensor->type,
                'name'          => $sensor->formatName(),
                'show_in_popup' => $sensor->show_in_popup,
                'value'         => htmlspecialchars($value->getFormatted()),
                'val'           => $value->getValue(),
                'scale_value'   => $sensor->getValueScale($value->getValue()),
                'tag_name'      => $sensor->tag_name,
            ];
        }

        return $result;
    }
}