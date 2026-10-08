<?php


namespace Tobuli\Sensors\Types;


class Odometer extends Numerical
{
    protected $ratio = 1;
    protected $precision = 0;
    protected static $defaultShowType = 'connected_odometer';

    public static function getType(): string
    {
        return 'odometer';
    }

    public static function getTypeTitle(): string
    {
        return trans('front.odometer');
    }

    public static function isPositionValue() : bool
    {
        return true;
    }

    public static function getShowTypes()
    {
        return [
            'connected_odometer' => trans('front.connected_odometer'),
            'virtual_odometer' => trans('front.virtual_odometer'),
        ];
    }

    public static function getInputs() : array
    {
        return [
            'connected_odometer' => [
                'tag_name' => true,
                'formula' => true,
                'unit' => true,
                'add_to_history' => true,
                'add_to_graph' => true,
            ],
            'virtual_odometer' => [
                'value' => true,
                'odometer_unit' => true,
                'add_to_history' => true,
                'add_to_graph' => true,
            ]
        ];
    }

    public function setShowType($showType)
    {
        parent::setShowType($showType);

        switch ($showType) {
            case 'virtual_odometer':
                if ($this->entity->odometer_value_unit == 'mi') {
                    $this->ratio = 0.62137119;
                    $this->setUnit('mi');
                } else {
                    $this->ratio = 1;
                    $this->setUnit('km');
                }

                break;
        }
    }

    public function getDataValue($data)
    {
        if (!$this->tag)
            return $this->getVirtualValue();

        return parent::getDataValue($data);
    }

    public function getParameterValue($data)
    {
        if (!$this->tag)
            return $this->getVirtualValue();

        return parent::getParameterValue($data);
    }

    protected function getVirtualValue()
    {
        return round($this->entity->value * $this->ratio, 3);
    }


}