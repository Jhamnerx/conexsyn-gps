<?php

namespace Tobuli\Services\Auth;

use Illuminate\Contracts\Auth\Authenticatable;

interface AuthInterface
{
    public static function getKey(): string;

    public function prepareLogout(Authenticatable $authenticatable);
}