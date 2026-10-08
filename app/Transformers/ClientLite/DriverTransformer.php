<?php

namespace App\Transformers\ClientLite;

use Tobuli\Entities\UserDriver AS Driver;

class DriverTransformer extends BaseTransformer {

    /**
     * @param Driver $entity
     * @return array|null
     */
    public function transform($entity) {
        if ( ! $entity)
            return null;

        return [
            'id'          => intval($entity->id),
            'name'        => $entity->name,
            'rfid'        => $entity->rfid,
            'phone'       => $entity->phone,
            'email'       => $entity->email,
            'description' => $entity->description,
        ];
    }
}