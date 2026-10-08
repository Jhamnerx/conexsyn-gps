<?php

namespace CustomFacades\Validators;

use Illuminate\Support\Facades\Facade;

class GeofenceGroupFormValidator extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Tobuli\Validation\GeofenceGroupFormValidator::class;
    }
}