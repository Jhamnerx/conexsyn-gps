<?php


namespace Tobuli\Sensors\Types;


class DriveBusiness extends Logical
{
    public static function getType(): string
    {
        return 'drive_business';
    }

    public static function getTypeTitle(): string
    {
        return trans('front.drive_business');
    }

    public static function isEnabled() : bool
    {
        return settings('plugins.business_private_drive.status');
    }
}