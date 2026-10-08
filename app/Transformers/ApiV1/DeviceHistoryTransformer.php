<?php

namespace App\Transformers\ApiV1;

use App\Policies\Property\DevicePropertiesPolicy;
use App\Transformers\BaseTransformer;
use Tobuli\Entities\Device;

class DeviceHistoryTransformer extends BaseTransformer {

    public function transform(Device $entity)
    {
        $policy = new DevicePropertiesPolicy();

        $device = $entity->toArray();

        foreach ($policy->getViewables() as $field) {
            if (!array_key_exists($field, $device))
                continue;

            $device[$field] = $this->canView($entity, $field);
        }

        $device['traccar']['protocol'] = $this->canView($entity, 'protocol');
        $device['traccar']['uniqueId'] = $this->canView($entity, 'imei');

        unset($device['users']);

        return $device;
    }
}