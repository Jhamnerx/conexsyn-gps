<?php

namespace App\Transformers\ClientLite;

use Tobuli\Entities\Device;

class DeviceServicesTransformer extends DeviceTransformer {

    public function transform(Device $entity) {
        $result = [];

        foreach ($entity->services as $service)
        {
            $service->setDevice($entity)->setSensors($entity->sensors);

            $result[] = [
                'id'       => (int)$service->id,
                'name'     => $service->name,
                'value'    => $service->expiration(),
                'expiring' => (bool)$service->isExpiring()
            ];
        }

        return $result;
    }
}