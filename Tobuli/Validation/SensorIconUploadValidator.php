<?php

namespace Tobuli\Validation;

class SensorIconUploadValidator extends Validator
{
    /**
     * @var array Validation rules for the test form, they can contain in-built Laravel rules or our custom rules
     */
    public array $rules = [
        'create' => [
            'file' => 'required|image|mimes:jpeg,gif,png,svg|max:20000|dimensions:min_width=10,min_height=10,max_width=30,max_height=30'
        ],
        'update' => [
            'file' => 'required|image|mimes:jpeg,gif,png,svg|max:20000|dimensions:min_width=10,min_height=10,max_width=30,max_height=30'
        ]
    ];

}
