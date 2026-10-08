<?php


namespace Tobuli\Sensors\Types;


class RouteColor extends Logical
{
    public static function getType(): string
    {
        return 'route_color';
    }

    public static function getTypeTitle(): string
    {
        return trans('front.route_color');
    }

    public static function isEnabled() : bool
    {
        return settings('plugins.route_color.status');
    }
}