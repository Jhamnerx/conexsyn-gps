<?php

namespace Tobuli\Services\Auth;

use Illuminate\Contracts\Auth\Authenticatable;

class PhoneAuth implements AuthInterface, InternalInterface
{
    public static function getKey(): string
    {
        return 'phone';
    }

    public function prepareLogout(Authenticatable $authenticatable)
    {
    }

    public function getInputTitle(): string
    {
        return trans('validation.attributes.phone');
    }

    public function getUserColumn(): string
    {
        return 'phone_number';
    }
}