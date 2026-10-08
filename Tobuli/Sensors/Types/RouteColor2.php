<?php


namespace Tobuli\Sensors\Types;


class RouteColor2 extends Logical
{
    public static function getType(): string
    {
        return 'route_color_2';
    }

    public static function getTypeTitle(): string
    {
        return trans('front.route_color') . ' 2';
    }

    public static function isEnabled() : bool
    {
        return settings('plugins.route_color.status');
    }
}