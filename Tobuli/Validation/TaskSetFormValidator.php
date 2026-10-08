<?php

namespace Tobuli\Validation;

class TaskSetFormValidator extends Validator
{
    public array $rules = [
        'create' => [
            'title' => 'required',
            'tasks' => 'array',
        ],
        'update' => [
            'title' => 'required',
            'tasks' => 'array',
        ],
        'assign' => [
            'tasks' => 'required|array',
        ]
    ];
}
