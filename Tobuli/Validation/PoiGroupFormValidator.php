<?php

namespace Tobuli\Validation;

class PoiGroupFormValidator extends Validator
{
    /**
     * @var array Validation rules for the test form, they can contain in-built Laravel rules or our custom rules
     */
    public $rules = [
        'create' => [
            'title' => 'required|max:255',
            'open'  => 'boolean',
            'pois'  => 'nullable|array|exists:user_map_icons,id',
        ],
        'update' => [
            'title' => 'max:255',
            'open'  => 'boolean',
            'pois'  => 'nullable|array|exists:user_map_icons,id'
        ]
    ];
}