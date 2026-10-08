<?php

namespace Tobuli\Services\Auth;

use Illuminate\Contracts\Auth\Authenticatable;

class EmailAuth implements AuthInterface, InternalInterface
{
    public static function getKey(): string
    {
        return 'email';
    }

    public function prepareLogout(Authenticatable $authenticatable)
    {
    }

    public function getInputTitle(): string
    {
        return trans('validation.attributes.email');
    }

    public function getUserColumn(): string
    {
        return 'email';
    }
}