<?php

namespace Tobuli\History\Actions;

use Tobuli\Entities\DeviceRouteType;

class AppendDriveBusinessByRoute extends ActionAppend
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

        if (property_exists($position, 'drive_business')) {
            return;
        }

        if ($position->drive_route_type === DeviceRouteType::TYPE_BUSINESS)
            $position->drive_business = true;
        else
            $position->drive_business = null;
    }
}