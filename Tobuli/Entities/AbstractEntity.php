<?php

namespace Tobuli\Entities;

use Eloquent;
use Illuminate\Database\Eloquent\Relations\Relation;

abstract class AbstractEntity extends Eloquent
{
    /**
     * Prepare a date for array / JSON serialization.
     *
     * @param  \DateTimeInterface  $date
     * @return string
     */
    protected function serializeDate(\DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    public function getRelationMethods(): array
    {
        $reflector = new \ReflectionClass($this);
        $relations = [];
        $relationTypes = [
            \Illuminate\Database\Eloquent\Relations\HasOne::class,
            \Illuminate\Database\Eloquent\Relations\HasMany::class,
            \Illuminate\Database\Eloquent\Relations\BelongsTo::class,
            \Illuminate\Database\Eloquent\Relations\BelongsToMany::class,
            \Illuminate\Database\Eloquent\Relations\MorphToMany::class,
            \Illuminate\Database\Eloquent\Relations\MorphTo::class,
        ];

        foreach ($reflector->getMethods() as $reflectionMethod) {
            $returnType = $reflectionMethod->getReturnType();

            if ($returnType && in_array($returnType->getName(), $relationTypes)) {
                $relations[] = $reflectionMethod;
            }
        }

        return array_map(fn ($relation) => $relation->name, $relations);
    }

    public function getRelationByClass(string $relationClass): ?Relation
    {
        foreach ($this->getRelationMethods() as $method) {
            /** @var Relation $relation */
            $relation = $this->$method();

            if (get_class($relation->getRelated()) === $relationClass) {
                return $relation;
            }
        }

        return null;
    }

    public function hasAttribute(string $key): bool
    {
        return array_key_exists($key, $this->attributes);
    }
}