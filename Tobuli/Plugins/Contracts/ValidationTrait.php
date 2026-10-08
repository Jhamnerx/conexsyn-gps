<?php

namespace Tobuli\Plugins\Contracts;

use Illuminate\Support\Facades\Validator;
use Illuminate\Support\MessageBag;

trait ValidationTrait
{
    public function validate(array $input): MessageBag
    {
        return Validator::make($input, $this->rules, [], $this->customAttributes)->errors();
    }
}