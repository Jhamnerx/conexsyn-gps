<?php

namespace Tobuli\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Tobuli\Exceptions\ValidationException;
use Tobuli\Helpers\SetFlag;

class ConditionService
{
    const TYPE_EQUAL = 1;
    const TYPE_MORE = 2;
    const TYPE_LESS = 3;
    const TYPE_NOT_EQUAL = 4;
    const TYPE_EMPTY = 5;

    /**
     * @return array
     */
    public static function getList()
    {
        return [
            self::TYPE_EQUAL     => trans('front.event_type_1'),
            self::TYPE_MORE      => trans('front.event_type_2'),
            self::TYPE_LESS      => trans('front.event_type_3'),
            self::TYPE_NOT_EQUAL => trans('front.event_type_4'),
            self::TYPE_EMPTY     => trans('front.event_type_5'),
        ];
    }

    /**
     * @param $type
     * @param $value
     * @param $equal
     * @return bool
     */
    public static function check($type, $value, $equal)
    {
        switch ($type) {
            case self::TYPE_EQUAL:
                return $value == $equal;

            case self::TYPE_MORE:
                $number = parseNumber($value);
                return is_numeric($number) && $number > $equal;

            case self::TYPE_LESS:
                $number = parseNumber($value);
                return is_numeric($number) && $number < $equal;

            case self::TYPE_NOT_EQUAL:
                return $value != $equal;

            case self::TYPE_EMPTY:
                return !is_numeric($value) && empty($value);
        }

        return false;
    }

    public static function validate($type, $equal)
    {
        switch ($type) {
            case self::TYPE_EQUAL:
            case self::TYPE_NOT_EQUAL:
                return is_numeric($equal) || !empty($equal);
            case self::TYPE_MORE:
            case self::TYPE_LESS:
                if ($setflag = SetFlag::singleCropValue($equal)) {
                    $equal = $setflag->getValue();
                }
                return is_numeric($equal);
            case self::TYPE_EMPTY:
                return true;
        }

        return false;
    }
}
