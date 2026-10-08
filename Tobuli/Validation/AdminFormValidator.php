<?php namespace Tobuli\Validation;

class AdminFormValidator extends Validator {

    /**
     * @var array Validation rules for the test form, they can contain in-built Laravel rules or our custom rules
     */
    public $rules = [
        'create' => [
            'email' => 'required|email|unique:users,email',
            'password' => 'required|secure_password|confirmed'
        ],
        'update' => [
            'email' => 'required|email|unique:users,email,%s',
            'password' => 'secure_password|confirmed'
        ]
    ];

}   //end of class


//EOF