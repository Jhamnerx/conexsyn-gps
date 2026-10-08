<?php


namespace Tobuli\Sensors;


use Tobuli\Entities\SensorIcon;

class Value
{
    /**
     * @var mixed
     */
    protected $value;

    /**
     * @var string
     */
    protected $formatted;

    protected $icon;

    public function __construct($value, $formatted, $icon)
    {
        $this->value = $value;
        $this->formatted = $formatted;
        $this->icon = $icon;
    }

    public function getValue()
    {
        return $this->value;
    }

    public function getFormatted()
    {
        return $this->formatted;
    }

    public function getIcon()
    {
        return runCacheEntity(SensorIcon::class, $this->icon)->first();
    }
}