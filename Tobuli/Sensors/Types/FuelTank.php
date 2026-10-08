<?php


namespace Tobuli\Sensors\Types;


class FuelTank extends Numerical
{
    protected $precision = 0;

    protected $fullTank;
    protected $fullTankValue;

    public static function getType(): string
    {
        return 'fuel_tank';
    }

    public static function getTypeTitle(): string
    {
        return trans('front.fuel_tank');
    }

    public static function isPositionValue() : bool
    {
        return true;
    }

    public static function getInputs() : array
    {
        return [
            'default' => [
                'tag_name' => true,
                'skip_empty' => true,
                'formula' => true,
                'calibration' => true,
                'full_tank' => true,
                'unit' => true,
                'add_to_history' => true,
                'add_to_graph' => true,
            ],
        ];
    }

    public function setFullTank($fullTank)
    {
        $this->fullTank = $fullTank;
    }

    public function setFullTankValue($fullTankValue)
    {
        $this->fullTankValue = $fullTankValue;
    }
}