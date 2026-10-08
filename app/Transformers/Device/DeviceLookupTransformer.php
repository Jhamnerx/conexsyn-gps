<?php

namespace App\Transformers\Device;

use Formatter;
use App\Transformers\BaseTransformer;
use Tobuli\Entities\Device;

class DeviceLookupTransformer extends DeviceTransformer {

    public function transform(Device $entity)
    {
        $expirationDate = $this->canView($entity, 'expiration_date');
        $expirationDate = $expirationDate ? Formatter::time()->convert($expirationDate) : null;

        return [
            'id'                  => (int)$entity->id,
            'active'              => (boolean)$entity->active,
            'name'                => $entity->name,
            'imei'                => $this->canView($entity, 'imei'),
            'sim_number'          => $this->canView($entity, 'sim_number'),
            'device_model'        => $this->canView($entity, 'device_model'),
            'plate_number'        => $this->canView($entity, 'plate_number'),
            'vin'                 => $this->canView($entity, 'vin'),
            'registration_number' => $this->canView($entity, 'registration_number'),
            'object_owner'        => $this->canView($entity, 'object_owner'),
            'additional_notes'    => $this->canView($entity, 'additional_notes'),
            'protocol'            => $this->canView($entity, 'protocol'),
            'expiration_date'     => $expirationDate,
        ];
    }
}