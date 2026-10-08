<?php

namespace Tobuli\Plugins\Contracts;

use Illuminate\Support\MessageBag;

interface ValidationAware
{
    public function validate(array $input): MessageBag;
}