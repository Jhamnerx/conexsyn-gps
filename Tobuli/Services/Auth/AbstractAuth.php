<?php

namespace Tobuli\Services\Auth;

use Illuminate\Support\Str;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;

abstract class AbstractAuth implements ConfigurableInterface
{
    protected $rules = [];
    protected $config;

    public function __construct()
    {
        $this->config = $this->getConfig();
    }

    public function storeConfig(array $input)
    {
        Validator::validate($input, $this->rules);

        return settings(
            'user_login_methods.config.' . static::getKey(),
            Arr::only($input, array_keys($this->rules))
        );
    }

    public function getConfig()
    {
        $key = 'user_login_methods.config.' . static::getKey();

        return settings($key);
    }

    public function renderConfigForm(): string
    {
        $key = $this->getKey();

        return view('admin::AuthConfig.config.' . $key)
            ->with(['config' => $this->config, 'authKey' => $key])
            ->render();
    }

    public static function getKey(): string
    {
        return Str::snake(class_basename(substr(static::class, 0, -4)));
    }
}