<?php

namespace Tobuli\History\Actions;

use Tobuli\Entities\DeviceRouteType;

class AppendDrivePrivateByRoute extends ActionAppend
{
    public static function required()
    {
        return [AppendDriveRouteType::class];
    }

    public function boot()
    {
    }

    public function proccess(&$position)
    {
        if (is_null($position->drive_route_type))
            return;

        if (property_exists($position, 'drive_private')) {
            return;
        }

        if ($position->drive_route_type === DeviceRouteType::TYPE_PRIVATE)
            $position->drive_private = true;
        else
            $position->drive_private = null;
    }
}