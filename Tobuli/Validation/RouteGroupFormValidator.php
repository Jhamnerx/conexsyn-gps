<?php

namespace Tobuli\Validation;

class RouteGroupFormValidator extends Validator
{
    /**
     * @var array Validation rules for the test form, they can contain in-built Laravel rules or our custom rules
     */
    public $rules = [
        'create' => [
            'title'  => 'required|max:255',
            'open'   => 'boolean',
            'routes' => 'nullable|array|exists:routes,id',
        ],
        'update' => [
            'title'  => 'max:255',
            'open'   => 'boolean',
            'routes' => 'nullable|array|exists:routes,id'
        ]
    ];
}