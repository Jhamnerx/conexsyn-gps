<?php

namespace Tobuli\Sensors\Types;

class Blocked extends Logical
{
    public static function getType(): string
    {
        return 'blocked';
    }

    public static function getTypeTitle(): string
    {
        return trans('front.blocked');
    }

    public static function isEnabled() : bool
    {
        return settings('plugins.device_blocked.status');
    }

    public static function isUnique(): bool
    {
        return true;
    }
}