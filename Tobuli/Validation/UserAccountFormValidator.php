<?php namespace Tobuli\Validation;

class UserAccountFormValidator extends Validator {

    /**
     * @var array Validation rules for the test form, they can contain in-built Laravel rules or our custom rules
     */
    public $rules = [
        'update' => [
            'email' => 'required|email|unique:users,email,%s|unique_table_msg:user_secondary_credentials,email',
            'password' => 'secure_password|confirmed'
        ],
        'password' => [
            'password' => 'required|secure_password|confirmed'
        ],
    ];

    public function validate(string $name, array $data, $id = null): void
    {
        if (settings('password.change_required_current')) {
            $this->rules['password']['current_password'] = 'required|password';
        }

        parent::validate($name, $data, $id);
    }
}