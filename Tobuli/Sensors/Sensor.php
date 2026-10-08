<?php


namespace Tobuli\Sensors;

use Illuminate\Support\Arr;
use Tobuli\Entities\DeviceSensor;
use Tobuli\Sensors\Contracts\Sensor AS SensorInterface;
use Tobuli\Sensors\Extractions\Logic;
use Tobuli\Sensors\Extractions\Mapping;

abstract class Sensor implements SensorInterface
{
    /** @var DeviceSensor */
    protected $entity;

    /**
     * @var string|null
     */
    protected $showType;

    /**
     * @var Tag
     */
    protected $tag;

    /**
     * @var Extraction
     */
    protected $extraction;

    /**
     * @var Mapping
     */
    protected $mapping;

    /**
     * @var Logic
     */
    protected $on;

    /**
     * @var Logic
     */
    protected $off;

    /**
     * @var string
     */
    protected $unit;

    /**
     * @var string
     */
    protected $unit_type;

    protected static $defaultShowType = 'default';

    protected static $timeout = null;

    abstract protected function getResult($value);
    abstract public static function getType() : string;
    abstract public static function getTypeTitle() : string;
    abstract public static function getInputs() : array;

    public static function isPersistent() : bool
    {
        return true;
    }

    public static function getInputsFor($showBy)
    {
        if (empty($showBy)) {
            $showBy = static::$defaultShowType;
        }

        return static::getInputs()[$showBy];
    }

    public static function getShowTypes()
    {
        return null;
    }

    public static function isEnabled() : bool
    {
        return true;
    }

    public static function isUnique() : bool
    {
        return false;
    }

    public static function isUpdatable() : bool
    {
        return true;
    }

    public static function isNullableValue() : bool
    {
        return false;
    }

    public static function isPositionValue() : bool
    {
        static $config = null;

        if (is_null($config)) {
            $config = config('addon.sensor_position_all');
        }

        return (bool) $config;
    }

    public static function getTimeout()
    {
        return static::$timeout;
    }

    public function setEntity($entity)
    {
        $this->entity = $entity;

        $this->setShowType($entity->shown_value_by);
    }

    public function setShowType($showType)
    {
        $this->showType = $showType;
    }

    public function setTag(Tag $tag)
    {
        $this->tag = $tag;
    }

    public function setExtraction(Extraction $extraction)
    {
        $this->extraction = $extraction;
    }

    public function setMapping(Mapping $mapping)
    {
        $this->mapping = $mapping;
    }

    public function setOn(Logic $on)
    {
        $this->on = $on;
    }

    public function setOff(Logic $off)
    {
        $this->off = $off;
    }

    public function setUnit($unit)
    {
        $this->unit = $unit;
    }

    public function setUnitType($unit_type)
    {
        $this->unit_type = $unit_type;
    }

    public function getUnit()
    {
        return $this->unit;
    }

    public function getPositionValue($position)
    {
        $value = $this->getDataValue($position->other);

        return $value ?? $this->getPositionStoredValue($position);
    }

    public function getPositionStoredValue($position)
    {
        if (!static::isPositionValue())
            return null;

        if (!$this->entity)
            return null;

        $values = $position->sensors_values ?? null;

        if (empty($values))
            return null;

        $values = is_string($values) ? json_decode($values, true) : $values;

        if (is_string($values))
            $values = json_decode($values, true);

        if (!is_array($values))
            return null;

        foreach ($values as $value) {
            if ($value['id'] != $this->entity->id)
                continue;

            $saved = $value['val'] ?? null;

            return ($saved === '-')
                ? null
                : $this->valueTypeConvert($saved);
        }

        return null;
    }

    public function getDataValue($data)
    {
        return $this->getParameterValue($data);
    }

    public function getPositionParameterValue($position)
    {
        $value = $this->getParameterValue($position->other);

        return $value ?? $this->getPositionStoredValue($position);
    }

    public function getParameterValue($data)
    {
        $value = $this->getTagValue($data);

        if (is_null($value))
            return null;

        $value = $this->getExtractionValue($value);

        if (is_null($value))
            return null;

        return $this->getResult($value);
    }

    public function getValueIcon($value)
    {
        return $this->getMappingIcon($value);
    }

    public function getValueFormatted($value)
    {
        return $value;
    }

    protected function getTagValue($data)
    {
        return $this->tag->parse($data);
    }

    protected function getExtractionValue($value)
    {
        return $this->extraction->parse($value);
    }

    protected function getMappingValue($value)
    {
        if (!$this->mapping)
            return $value;

        return $this->mapping->parse($value);
    }

    protected function getMappingIcon($value)
    {
        if (!$this->mapping)
            return null;

        return $this->mapping->getIcon($value);
    }

    protected function valueTypeConvert($value)
    {
        return $value;
    }

    /**
     * @param $value
     * @return Value
     */
    public function toValue($value)
    {
        if (is_null($value) && !$this->isNullableValue()) {
            return new Value(null, '-', null);
        }

        return new Value($value, $this->getValueFormatted($value), $this->getValueIcon($value));
    }
}