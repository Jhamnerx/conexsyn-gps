<?php

namespace CustomFacades\Validators;

use Illuminate\Support\Facades\Facade;

class AsaasConfigFormValidator extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Tobuli\Validation\AsaasConfigFormValidator::class;
    }
}