<?php

namespace Tobuli\Services;

use Illuminate\Support\Arr;

class RegistrationFieldsService
{
    private array $settings;

    public function __construct(array $settings)
    {
        $this->setSettings($settings);
    }

    public function getFlat(string $prefix = ''): array
    {
        $formatter = fn ($key, $prefix) => $prefix ? "{$prefix}[$key]" : $key;

        return $this->flatten($this->settings, $formatter, $prefix);
    }

    public function getRules(string $prefix = ''): array
    {
        $formatter = fn ($key, $prefix) => $prefix ? "$prefix.$key" : $key;

        $fields = ($this->flatten($this->settings, $formatter, $prefix));
        $rules = [];

        foreach ($fields as $key => $conf) {
            if ($conf['present']) {
                $rules[$key] = [];
            }

            if ($conf['required']) {
                $rules[$key][] = 'required';
            }
        }

        foreach ($this->settings as $key => $conf) {
            if (is_array(Arr::first($conf))) {
                $rules[$key][] = 'array';
            }
        }

        if (isset($rules['email'])) {
            $rules['email'][] = 'email';
            $rules['email'][] = 'unique:users,email';
        }

        if (isset($rules['phone_number'])) {
            $rules['phone_number'][] = 'phone';
            $rules['phone_number'][] = 'unique:users,phone_number';
        }

        if (isset($rules['client.birth_date'])) {
            $rules['client.birth_date'][] = 'date';
        }

        return $rules;
    }

    private function flatten(array $settings, \Closure $formatter, string $prefix): array
    {
        $flat = [];

        foreach ($settings as $key => $conf) {
            $name = $formatter($key, $prefix);

            if (is_array(Arr::first($conf))) {
                $flat += $this->flatten($conf, $formatter, $name);
            } else {
                $flat[$name] = $conf;
                $flat[$name]['attribute'] = $key;
            }
        }

        return $flat;
    }

    public function getSettings(): array
    {
        return $this->settings;
    }

    public function setSettings(array &$settings): self
    {
        foreach ($settings as &$field) {
            if (is_array(Arr::first($field))) {
                $this->setSettings($field);

                continue;
            }

            if (!empty($field['required'])) {
                $field['present'] = $field['required'];
            }
        }

        $this->settings = $settings;

        return $this;
    }
}