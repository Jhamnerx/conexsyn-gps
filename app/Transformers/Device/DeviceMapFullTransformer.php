<?php

namespace App\Transformers\Device;

use App\Transformers\BaseTransformer;
use Tobuli\Entities\Device;
use Formatter;

class DeviceMapFullTransformer extends DeviceTransformer  {

    protected $defaultIncludes = [
        'icon',
        //'sensors',
        //'services',
        'driver'
    ];

    protected static function requireLoads()
    {
        return ['icon', 'traccar', 'sensors', 'services', 'driver'];
    }

    public function transform(Device $entity)
    {
        $expirationDate = $this->canView($entity, 'expiration_date');
        $expirationDate = $expirationDate ? Formatter::time()->convert($expirationDate) : null;

        $inaccuracy = config('addon.inaccuracy')
            ? $entity->getParameter('inaccuracy')
            : null;

        return [
            'id'    => (int)$entity->id,
            'name'  => $entity->name,
            'plate_number'  => $entity->plate_number,
            'device_model'  => $entity->device_model,
            'tail'  => $entity->tail,
            'tail_color' => $entity->tail_color,
            'icon_color' => $entity->getStatusColor(),
            'icon_colors' => $entity->icon_colors,
            'active' => $entity->pivot ? (bool)$entity->pivot->active : null,
            'group_id' => $entity->pivot ? (int)$entity->pivot->group_id : 0,
            'online' => $entity->getStatus(),
            'lat' => $entity->lat,
            'lng' => $entity->lng,
            'speed' => $entity->speed,
            'course' => $entity->course,
            'altitude' => $entity->altitude,
            'time' => $entity->time,
            'timestamp' => (int)$entity->timestamp,
            'acktimestamp' => (int)$entity->acktimestamp,
            'moved_timestamp' => (int)$entity->moved_timestamp,

            'protocol'        => $this->canView($entity, 'protocol'),
            'expiration_date' => $expirationDate,

            'detect_engine'      => $entity->detect_engine,
            'engine_hours'       => $entity->engine_hours,

            'engine_status'      => $entity->getEngineStatus(),
            'stop_duration'      => $entity->stop_duration,
            'stop_duration_sec'  => $entity->getStopDuration(),
            'total_distance'     => $entity->getTotalDistance(),
            'inaccuracy'         => is_null($inaccuracy) ? null : intval($inaccuracy),

            'sensors'   => $this->formatSensors($entity),
            'services'  => $this->formatServices($entity),
        ];
    }

    //tmp
    protected function formatSensors($entity)
    {
        if ($entity->isExpired())
            return null;

        $result = [];

        foreach ($entity->sensors->sortBy('id') as $sensor) {
            if (in_array($sensor->type, ['harsh_acceleration', 'harsh_breaking', 'harsh_turning']))
                continue;

            $value = $sensor->getValueCurrent($entity);
            $icon = $value->getIcon();

            $result[] = [
                'id'            => $sensor->id,
                'type'          => $sensor->type,
                'name'          => $sensor->formatName(),
                'show_in_popup' => $sensor->show_in_popup,

                'value'         => htmlspecialchars($value->getFormatted()),
                'val'           => $value->getValue(),
                'scale_value'   => $sensor->getValueScale($value->getValue()),
                'icon'          => $icon ? asset($icon->path) : null
            ];
        }

        return $result;
    }

    protected function formatServices($entity)
    {
        if ($entity->isExpired())
            return null;

        $result = [];

        foreach ($entity->services as $service)
        {
            $service->setDevice($entity)->setSensors($entity->sensors);

            $result[] = [
                'id'       => $service->id,
                'name'     => $service->name,
                'value'    => $service->expiration(),
                'expiring' => $service->isExpiring()
            ];
        }

        return $result;
    }
}