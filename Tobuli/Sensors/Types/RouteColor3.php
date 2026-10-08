<?php


namespace Tobuli\Sensors\Types;


class RouteColor3 extends Logical
{
    public static function getType(): string
    {
        return 'route_color_3';
    }

    public static function getTypeTitle(): string
    {
        return trans('front.route_color') . ' 3';
    }

    public static function isEnabled() : bool
    {
        return settings('plugins.route_color.status');
    }
}