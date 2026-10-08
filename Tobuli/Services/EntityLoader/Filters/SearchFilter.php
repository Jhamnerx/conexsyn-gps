<?php


namespace Tobuli\Services\EntityLoader\Filters;


use Illuminate\Database\Eloquent\Builder;

class SearchFilter implements Filter
{
    public function __construct($entity)
    {

    }

    public function key()
    {
        return 's';
    }

    public function isSelectedRequest($item, $value)
    {
        if (empty($value))
            return true;

        if (str_contains(strtolower($item->name), strtolower($value)))
            return true;

        return false;
    }

    public function querySelect(Builder $query, array $values)
    {
        foreach ($values as $search) {
            $query->orWhere(function($q) use ($search){
                $q->search($search);
            });
        }
    }

    public function queryDeselect(Builder $query, array $values)
    {
        foreach ($values as $search) {
            $query->orWhere(function($q) use ($search){
                $q->searchExclude($search);
            });
        }
    }
}