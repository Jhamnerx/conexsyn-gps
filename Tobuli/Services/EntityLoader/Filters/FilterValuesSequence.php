<?php


namespace Tobuli\Services\EntityLoader\Filters;


class FilterValuesSequence
{
    /**
     * @var bool
     */
    protected $currentStatus;

    /**
     * @var FilterValue[]
     */
    protected $filterValues = [];

    /**
     * @return FilterValue[]
     */
    public function all()
    {
        return $this->filterValues;
    }

    public function hasDeselectAll() : bool
    {
        foreach ($this->filterValues as $filterValue) {
            if ($filterValue->getStatus() === false
                && $filterValue->getField() == 's'
                && $filterValue->getValue() == ''
            ) return true;
        }

        return false;
    }

    public function hasSelectAll() : bool
    {
        foreach ($this->filterValues as $filterValue) {
            if ($filterValue->getStatus() === true
                && $filterValue->getField() == 's'
                && $filterValue->getValue() == ''
            ) return true;
        }

        return false;
    }

    public function hasAttaches() : bool
    {
        foreach ($this->filterValues as $filterValue) {
            if ($filterValue->getStatus() === true)
                return true;
        }

        return false;
    }

    public function hasDetaches() : bool
    {
        foreach ($this->filterValues as $filterValue) {
            if ($filterValue->getStatus() === false)
                return true;
        }

        return false;
    }

    public function isEmpty() : bool
    {
        return empty($this->filterValues);
    }

    public function add(FilterValue $filterValue)
    {
        if ($this->isResettable($filterValue)) {
            $this->reset();
        }

        if ($this->appendPossible($filterValue)) {
            return;
        }

        $this->filterValues[] = $filterValue;
    }

    protected function appendPossible(FilterValue $filterValue) : bool
    {
        $index = count($this->filterValues);

        while($index) {
            $current = $this->filterValues[--$index];

            if ($current->getStatus() !== $filterValue->getStatus()) {
                return false;
            }

            if ($current->getField() !== $filterValue->getField()) {
                continue;
            }

            $current->append($filterValue->getValue());

            return true;
        }

        return false;
    }

    protected function isResettable(FilterValue $filterValue) : bool
    {
        return $filterValue->getField() == 's' && $filterValue->getValue() == '';
    }

    protected function reset()
    {
        $this->filterValues = [];
    }
}