<?php


namespace Tobuli\Validation\Rules;


use Illuminate\Contracts\Validation\ImplicitRule;
use Illuminate\Contracts\Validation\Rule;
use Tobuli\Services\EntityLoader\EnityLoader;

class EntityLoaderLimit extends EntityLoader
{
    public function __construct(EnityLoader $loader, $min, $max)
    {
        parent::__construct($loader);

        $this->min = $min;
        $this->max = $max;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        $this->loader->setRequestKey($attribute);

        if (empty($this->min) && empty($this->max))
            return true;

        $this->count = $this->loader->getQuerySelected()->count();

        if ($this->min && $this->min > $this->count)
            return false;

        if ($this->max && $this->max < $this->count)
            return false;

        return true;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        if ($this->min && $this->min > $this->count)
            return trans('validation.required');

        return str_replace(':max', $this->max, trans('validation.array_max'));;
    }
}