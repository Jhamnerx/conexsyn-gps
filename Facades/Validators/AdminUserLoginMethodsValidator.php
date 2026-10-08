<?php

namespace CustomFacades\Validators;

use Illuminate\Support\Facades\Facade;

class AdminUserLoginMethodsValidator extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return \Tobuli\Validation\AdminUserLoginMethodsValidator::class;
    }
}
