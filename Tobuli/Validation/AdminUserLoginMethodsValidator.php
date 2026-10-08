<?php

namespace Tobuli\Validation;

use Illuminate\Validation\Factory;
use Tobuli\Services\Auth\AbstractAuth;

class AdminUserLoginMethodsValidator extends Validator
{
    /**
     * @var array Validation rules for the test form, they can contain in-built Laravel rules or our custom rules
     */
    public $rules = [
        'update' => [
            'login_methods' => 'required|array',
            'user_individual_config' => 'boolean',
        ],
    ];

    public function __construct(Factory $validator)
    {
        parent::__construct($validator);

        /** @var AbstractAuth $auth */
        foreach (app()->tagged('auths') as $auth) {
            $this->rules['update']['login_methods.' . $auth->getKey()] = 'required|boolean';
        }
    }
}
