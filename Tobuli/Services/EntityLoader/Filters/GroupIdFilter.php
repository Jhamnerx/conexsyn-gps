<?php


namespace Tobuli\Services\EntityLoader\Filters;


use Illuminate\Database\Eloquent\Builder;

class GroupIdFilter implements Filter
{
    protected $table = null;

    public function __construct($table)
    {
        $this->table = $table;
    }

    public function key()
    {
        return 'group_id';
    }

    public function isSelectedRequest($item, $value)
    {
        return $item->group_id == $value;
    }

    public function querySelect(Builder $query, array $values)
    {
        if (in_array(0, $values))
            $query->orWhereNull("{$this->table}.group_id");

        $query->orWhereIn("{$this->table}.group_id", $values);
    }

    public function queryDeselect(Builder $query, array $values)
    {
        if (in_array(0, $values))
            $query->orWhereNotNull("{$this->table}.group_id");

        $query->orWhereNotIn("{$this->table}.group_id", $values);
    }
}