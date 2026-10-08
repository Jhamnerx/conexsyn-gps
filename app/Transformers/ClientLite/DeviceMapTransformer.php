<?php

namespace App\Transformers\ClientLite;

use Tobuli\Entities\Device;
use Formatter;

class DeviceMapTransformer extends DeviceListTransformer  {

    protected $defaultIncludes = [
        'icon',
        'status',
        'coordinates',
        'tail',
        //'position',

    ];

    protected static function requireLoads()
    {
        return ['icon', 'traccar', 'sensors' => function ($query) {
            $query->whereIn('type', ['acc', 'engine', 'ignition']);
        }];
    }

    public function transform(Device $entity)
    {
        return parent::transform($entity);

        return [
            'id'    => (int)$entity->id,
            'name'  => $entity->name,
        ];
    }
}