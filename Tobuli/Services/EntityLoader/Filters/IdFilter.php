<?php


namespace Tobuli\Services\EntityLoader\Filters;


use Illuminate\Database\Eloquent\Builder;

class IdFilter implements Filter
{
    protected $table = null;

    public function __construct($table)
    {
        $this->table = $table;
    }

    public function key()
    {
        return 'id';
    }

    public function isSelectedRequest($item, $value)
    {
        return $item->id == $value;
    }

    public function querySelect(Builder $query, array $values)
    {
        $query->orWhereIn("{$this->table}.id", $values);
    }

    public function queryDeselect(Builder $query, array $values)
    {
        $query->orWhereNotIn("{$this->table}.id", $values);
    }
}