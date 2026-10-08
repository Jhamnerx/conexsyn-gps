<?php


namespace Tobuli\Sensors\Types;


class DrivePrivate extends Logical
{
    public static function getType(): string
    {
        return 'drive_private';
    }

    public static function getTypeTitle(): string
    {
        return trans('front.drive_private');
    }

    public static function isEnabled() : bool
    {
        return settings('plugins.business_private_drive.status');
    }
}