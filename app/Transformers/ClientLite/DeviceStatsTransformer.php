<?php

namespace App\Transformers\ClientLite;

use Tobuli\Entities\Device;
use Formatter;

class DeviceStatsTransformer extends BaseTransformer
{
    public function transform(Device $entity)
    {
        return [
            [
                'title' => trans('front.stop_duration'),
                'value' => $entity->stop_duration,
            ],
            [
                'title' => trans('front.idle_duration'),
                'value' => $entity->idle_duration,
            ],
            [
                'title' => trans('global.distance'),
                'value' => (string)$entity->getTotalDistance(),
            ],
        ];
    }
}