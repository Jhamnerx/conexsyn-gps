<?php


namespace Tobuli\Services\EntityLoader\Filters;


use Illuminate\Database\Eloquent\Builder;

class GroupIdWithSearchFilter implements Filter
{
    protected $table = null;

    /**
     * @var GroupIdFilter
     */
    protected $groupFilter;

    /**
     * @var SearchFilter
     */
    protected $searchFilter;

    public function __construct($table)
    {
        $this->groupFilter = new GroupIdFilter($table);
        $this->searchFilter = new SearchFilter(null);
    }

    public function key()
    {
        return 's_group_id';
    }

    public function isSelectedRequest($item, $value)
    {
        list($group_id, $search) = explode('|', $value, 2);

        return $this->groupFilter->isSelectedRequest($item, $group_id)
            && $this->searchFilter->isSelectedRequest($item,$search);
    }

    public function querySelect(Builder $query, array $values)
    {
        foreach ($values as $value) {
            $query->orWhere(function($q) use ($value) {

                list($group_id, $search) = explode('|', $value, 2);

                //wrap for AND logic
                $q->where( function ($qq) use ($group_id) {
                    $this->groupFilter->querySelect($qq, [$group_id]);
                });

                //wrap for AND logic
                $q->where( function ($qq) use ($search) {
                    $this->searchFilter->querySelect($qq, [$search]);
                });
            });
        }
    }

    public function queryDeselect(Builder $query, array $values)
    {
        foreach ($values as $value) {

            $query->orWhere(function($q) use ($value) {

                list($group_id, $search) = explode('|', $value, 2);

                //wrap for AND logic
                $q->where( function ($qq) use ($group_id) {
                    $this->groupFilter->queryDeselect($qq, [$group_id]);
                });

                //wrap for AND logic
                $q->where( function ($qq) use ($search) {
                    $this->searchFilter->queryDeselect($qq, [$search]);
                });
            });
        }
    }
}