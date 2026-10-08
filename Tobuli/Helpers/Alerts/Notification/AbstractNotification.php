<?php

namespace Tobuli\Helpers\Alerts\Notification;

use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Tobuli\Entities\User;

abstract class AbstractNotification
{
    protected array $rules = [];

    public function validate(array &$data): \Illuminate\Validation\Validator
    {
        if (empty($this->rules)) {
            return Validator::make($data, []);
        }
        
        if (!$this->isActive($data)) {
            return Validator::make($data, []);
        }

        $this->prepareDataForValidation($data);
        
        return Validator::make($data, $this->rules);
    }

    protected function prepareDataForValidation(array &$data): void
    {
    }

    protected function isActive(array $data): bool
    {
        return !empty($data['active']);
    }

    public static function getKey(): string
    {
        return Str::snake(substr(class_basename(static::class), 0, -12));
    }

    public function isEnabled(User $user): bool
    {
        return true;
    }
}