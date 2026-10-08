<?php


namespace Tobuli\Services\EntityLoader\Filters;


class FilterValue
{
    /**
     * @var string
     */
    protected $field;

    /**
     * @var mixed
     */
    protected $value;

    /**
     * @var bool
     */
    protected $status;

    public function __construct($field, $status, $value)
    {
        $this->field = $field;
        $this->status = filter_var($status, FILTER_VALIDATE_BOOLEAN);
        $this->value = $value;
    }

    public function getField()
    {
        return $this->field;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function getValue()
    {
        return $this->value;
    }

    public function getValues()
    {
        return !is_array($this->value) ? [$this->value] : $this->value;
    }

    public function append($value)
    {
        if (!is_array($this->value)) {
            $this->value = [$this->value];
        }

        $this->value[] = $value;
    }
}