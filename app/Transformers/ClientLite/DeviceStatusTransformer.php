<?php

namespace App\Transformers\ClientLite;

use Tobuli\Entities\Device;
use Formatter;

class DeviceStatusTransformer extends DeviceTransformer
{
    public function transform(Device $entity)
    {
        $status = $entity->getStatus();

        return [
            'type'  => $status,
            'title' => self::translateStatus($status),
            'color' => self::colorConvert( $entity->getStatusColor($status)),
        ];
    }

    protected static function translateStatus($status)
    {
        switch ($status) {
            case Device::STATUS_OFFLINE:
                return trans('global.offline');
            case Device::STATUS_ONLINE:
                return trans('global.online');
            case Device::STATUS_ACK:
                return trans('global.ack');
            case Device::STATUS_ENGINE:
                return trans('global.engine');
            case Device::STATUS_BLOCKED:
                return trans('front.blocked');
            default:
                return $status;
        }
    }
}