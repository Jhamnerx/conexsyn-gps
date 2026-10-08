<?php


namespace Tobuli\Services\EntityLoader\Filters;


use Illuminate\Database\Eloquent\Builder;

interface Filter
{
    /**
     * @return string
     */
    public function key();

    /**
     * @param $item
     * @param $value
     * @return boolean
     */
    public function isSelectedRequest($item, $value);

    public function querySelect(Builder $query, array $values);

    public function queryDeselect(Builder $query, array $values);
}