<?php

namespace Tobuli\Validation;

use Illuminate\Validation\Factory as IlluminateValidator;
use Illuminate\Validation\Rules\Unique;
use Tobuli\Exceptions\ValidationException;

/**
 * Base Validation class. All entity specific validation classes inherit
 * this class and can override any function for respective specific needs
 */
abstract class Validator
{
    protected IlluminateValidator $_validator;

    public function __construct(IlluminateValidator $validator)
    {
        $this->_validator = $validator;
    }

    public function validate(string $name, array $data, $id = null): void
    {
        $rules = $this->rules[$name];

        if ($id !== null) {
            $this->applyId($rules, $id);
        }

        $validation = $this->_validator->make($data, $rules);

        if ($validation->fails()) {
            throw new ValidationException($validation->messages());
        }
    }

    private function applyId(array &$rules, $id): void
    {
        foreach ($rules as &$rule) {
            switch (true) {
                case is_array($rule):
                    $this->applyId($rule, $id);
                    break;
                case is_string($rule):
                    $rule = sprintf($rule, $id);
                    break;
                case $rule instanceof Unique:
                    $rule->ignore($id);
                    break;
            }
        }
    }

}
