<?php

namespace Tobuli\Traits;

trait DisplayTrait
{
    public function getDisplayName(): string
    {
        $displayField = static::$displayField ?? false;

        if (is_string($displayField)) {
            return $this->{static::$displayField};
        }

        if (is_array($displayField)) {
            $value = array_map(fn ($field) => $this->$field, $displayField);

            return implode(' ', $value);
        }

        return '';
    }
}