<?php


namespace Tobuli\Relations;


use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HasManyTable extends HasMany
{
    public function __construct(Builder $query, Model $parent, $table = null, $localKey = 'id')
    {
        static::noConstraints(function () use ($query, $parent, $localKey) {
            parent::__construct($query, $parent, null, $localKey);
        });

        if (is_null($table)) {
            $table = $this->related->getTable();
        }

        $this->related->setTable($table);
        $this->query->from($table);
    }

    protected function setForeignAttributesForCreate(Model $model)
    {
        $model->setTable($this->related->getTable());
    }

    public function __clone()
    {
        $this->query = clone $this->query;
    }
}