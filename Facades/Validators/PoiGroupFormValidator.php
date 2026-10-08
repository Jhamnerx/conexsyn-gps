<?php

namespace CustomFacades\Validators;

use Illuminate\Support\Facades\Facade;

class PoiGroupFormValidator extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Tobuli\Validation\PoiGroupFormValidator::class;
    }
}