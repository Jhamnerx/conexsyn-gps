<?php

namespace Tobuli\Validation;

class AdminDeviceModelFormValidator extends Validator
{
    /**
     * @var array Validation rules for the test form, they can contain in-built Laravel rules or our custom rules
     */
    public array $rules = [
        'create' => [
            'active'    => 'boolean',
            'title'     => 'required',
            'model'     => 'required',
            'protocol'  => 'required',
        ],
        'update' => [
            'id'        => 'required|exists:device_models,id',
            'title'     => 'sometimes',
            'active'    => 'boolean',
        ],
    ];
}
