<?php


namespace Tobuli\Validation\Rules;

use Illuminate\Contracts\Validation\ImplicitRule;
use Illuminate\Contracts\Validation\Rule;
use Tobuli\Services\EntityLoader\EnityLoader;

abstract class EntityLoader implements Rule, ImplicitRule
{
    protected EnityLoader $loader;

    public function __construct(EnityLoader $loader)
    {
        $this->loader = $loader;
    }
}