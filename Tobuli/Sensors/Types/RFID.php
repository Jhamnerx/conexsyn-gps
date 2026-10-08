<?php


namespace Tobuli\Sensors\Types;

class RFID extends Textual
{
    public static function getType(): string
    {
        return 'rfid';
    }

    public static function getTypeTitle(): string
    {
        return trans('validation.attributes.rfid');
    }

    public static function isUnique() : bool
    {
        return true;
    }
}