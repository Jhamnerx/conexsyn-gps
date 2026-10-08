<?php

namespace Tobuli\Validation;

class AsaasConfigFormValidator extends Validator
{
    public array $rules = [
        'update' => [
            'environment'     => 'required',
            'api_key'         => 'required',
            'access_token'    => 'required',
        ],
    ];
}