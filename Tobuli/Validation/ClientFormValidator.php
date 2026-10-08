<?php namespace Tobuli\Validation;

use Tobuli\Services\AuthManager;

class ClientFormValidator extends Validator {

    /**
     * @var array Validation rules for the test form, they can contain in-built Laravel rules or our custom rules
     */
    public $rules = [
        'create' => [
            'active' => 'boolean',
            'email' => 'required|email|unique:users,email|unique_table_msg:user_secondary_credentials,email',
            'password' => 'required|secure_password|confirmed',
            'available_maps' => 'required|array',
            'group_id' => 'in:1,2,3,4,5,6',
            'role_id' => 'in:1,2,3,4,5,6',
            'devices_limit' => 'required_if:enable_devices_limit,1|integer',
            'expiration_date' => 'required_if:enable_expiration_date,1|date',
            'default_login_methods' => 'boolean',
            'login_methods' => 'array',
            'is_municipalidad' => 'nullable|boolean',
            'ubigeo_muni' => 'nullable|regex:/^\d{6}$/|required_if:is_municipalidad,1',
            'token_muni' => 'nullable|string|max:255|required_if:is_municipalidad,1',
            'codigo_comisaria' => 'nullable|string|max:50',
        ],
        'update' => [
            'active' => 'boolean',
            'email' => 'required|email|unique:users,email,%s|unique_table_msg:user_secondary_credentials,email',
            'password' => 'secure_password|confirmed|required_if:send_account_password_changed_email,1',
            'password_generate' => 'required_with:send_account_password_changed_email',
            'available_maps' => 'required|array',
            'group_id' => 'in:1,2,3,4,5,6',
            'role_id' => 'in:1,2,3,4,5,6',
            'devices_limit' => 'required_if:enable_devices_limit,1|integer',
            'expiration_date' => 'required_if:enable_expiration_date,1|date',
            'default_login_methods' => 'boolean',
            'login_methods' => 'array',
            'is_municipalidad' => 'nullable|boolean',
            'ubigeo_muni' => 'nullable|regex:/^\d{6}$/|required_if:is_municipalidad,1',
            'token_muni' => 'nullable|string|max:255|required_if:is_municipalidad,1',
            'codigo_comisaria' => 'nullable|string|max:50',
        ]
    ];

    public function validate(string $name, array $data, $id = null): void
    {
        $methods = AuthManager::getDefaultAuths();

        foreach ($methods as $method => $enabled) {
            $this->rules[$name]['login_methods.'.$method] = 'boolean';
        }

        $this->rules[$name]['available_maps.*'] = 'integer|in:' . implode(',', array_keys(getAvailableMaps()));

        parent::validate($name, $data, $id);
    }
}