<?php


namespace Tobuli\Sensors;

use Illuminate\Support\Collection;
use Tobuli\Entities\DeviceSensor;
use Tobuli\Helpers\SetFlag;
use Tobuli\Sensors\Contracts\Sensor AS SensorInterface;
use Tobuli\Sensors\Extractions\Ascii;
use Tobuli\Sensors\Extractions\Bin;
use Tobuli\Sensors\Extractions\BitCut;
use Tobuli\Sensors\Extractions\Calibration;
use Tobuli\Sensors\Extractions\Correlation;
use Tobuli\Sensors\Extractions\Formula;
use Tobuli\Sensors\Extractions\FormulaSetFlag;
use Tobuli\Sensors\Extractions\Logic;
use Tobuli\Sensors\Extractions\Mapping;
use Tobuli\Sensors\Extractions\Percentages;
use Tobuli\Sensors\Extractions\SkipEmpty;
use Tobuli\Sensors\Types\Acc;
use Tobuli\Sensors\Types\Anonymizer;
use Tobuli\Sensors\Types\Battery;
use Tobuli\Sensors\Types\BatteryExternal;
use Tobuli\Sensors\Types\Blocked;
use Tobuli\Sensors\Types\Counter;
use Tobuli\Sensors\Types\Datetime;
use Tobuli\Sensors\Types\Door;
use Tobuli\Sensors\Types\DriveBusiness;
use Tobuli\Sensors\Types\DrivePrivate;
use Tobuli\Sensors\Types\Engine;
use Tobuli\Sensors\Types\EngineHours;
use Tobuli\Sensors\Types\FuelConsumption;
use Tobuli\Sensors\Types\FuelTank;
use Tobuli\Sensors\Types\GSM;
use Tobuli\Sensors\Types\HarshAcceleration;
use Tobuli\Sensors\Types\HarshBreaking;
use Tobuli\Sensors\Types\HarshTurning;
use Tobuli\Sensors\Types\Ignition;
use Tobuli\Sensors\Types\Load;
use Tobuli\Sensors\Types\Logical;
use Tobuli\Sensors\Types\Numerical;
use Tobuli\Sensors\Types\Odometer;
use Tobuli\Sensors\Types\Plugged;
use Tobuli\Sensors\Types\RFID;
use Tobuli\Sensors\Types\RouteColor;
use Tobuli\Sensors\Types\RouteColor2;
use Tobuli\Sensors\Types\RouteColor3;
use Tobuli\Sensors\Types\Satellites;
use Tobuli\Sensors\Types\Seatbelt;
use Tobuli\Sensors\Types\SpeedECM;
use Tobuli\Sensors\Types\Tachometer;
use Tobuli\Sensors\Types\Temperature;
use Tobuli\Sensors\Types\Textual;
use Tobuli\Sensors\Types\VIN;

class SensorsManager
{
    public static array $types = [
        Logical::class,
        Numerical::class,
        Textual::class,
        Acc::class,
        Engine::class,
        Ignition::class,
        Door::class,
        Seatbelt::class,
        DriveBusiness::class,
        DrivePrivate::class,
        HarshBreaking::class,
        HarshAcceleration::class,
        HarshTurning::class,

        RouteColor::class,
        RouteColor2::class,
        RouteColor3::class,
        Anonymizer::class,
        Plugged::class,
        Blocked::class,

        RFID::class,
        Odometer::class,
        EngineHours::class,
        Satellites::class,
        GSM::class,
        Battery::class,
        BatteryExternal::class,
        FuelConsumption::class,
        FuelTank::class,
        Temperature::class,
        Load::class,
        Tachometer::class,
        SpeedECM::class,

        Counter::class,
        VIN::class,
        Datetime::class,
    ];

    /**
     * @param string $type
     * @return Sensor|null
     */
    public function resolveType($type)
    {
        foreach (self::$types as $class) {
            if ($class::getType() !== $type) {
                continue;
            }

            return new $class();
        }

        return null;
    }

    /**
     * @return SensorInterface[]
     */
    public function getList(): Collection
    {
        $list = collect();

        foreach (self::$types as $typeId => $class) {
            $list->push(new $class());
        }

        return $list;
    }

    /**
     * @return SensorInterface[]
     */
    public function getEnabledList(): Collection
    {
        return $this->getList()
            ->filter(function(SensorInterface $sensor) {
                return $sensor::isEnabled();
            })
            ->sortBy(function(Sensor $sensor) {
                return $sensor::getType();
            });
    }

    public function getEnabledListTitles(): array
    {
        return $this->getEnabledList()->mapWithKeys(function($sensor) {
            return [$sensor->getType() => $sensor->getTypeTitle()];
        })->all();
    }

    public function build(DeviceSensor $entity)
    {
        $sensor = $this->resolveType($entity->type);

        if (!$sensor)
            throw new \Exception("Sensor type '{$entity->type}' unknown");

        $sensor->setEntity($entity);

        $extractions = [];

        foreach ($sensor::getInputsFor($entity->shown_value_by) as $key => $status) {
            if (!$status)
                continue;

            switch ($key) {
                case 'tag_name':
                    $sensor->setTag(new Tag($entity->tag_name));
                    break;
                case 'logic_on':
                    $setFlag = null;
                    $value = $entity->on_tag_value;

                    if ($crop = SetFlag::singleCropValue($value)) {
                        $value = $crop->getValue();
                        $setFlag = new \Tobuli\Sensors\Extractions\SetFlag($crop->getStart(), $crop->getCount());
                    }

                    $onText = $entity->type == 'door' ? trans('front.opened') : trans('front.on');

                    $sensor->setOn(new Logic($entity->on_type, $value, $onText, $setFlag));
                    break;
                case 'logic_off':
                    $setFlag = null;
                    $value = $entity->off_tag_value;

                    if ($crop = SetFlag::singleCropValue($value)) {
                        $value = $crop->getValue();
                        $setFlag = new \Tobuli\Sensors\Extractions\SetFlag($crop->getStart(), $crop->getCount());
                    }

                    $offText = $entity->type == 'door' ? trans('front.closed') : trans('front.off');

                    $sensor->setOff(new Logic($entity->off_type, $value, $offText, $setFlag));
                    break;
                case 'formula':
                    $formula = $entity->formula;

                    if ($formula && $formula != Formula::PLACEHOLDER) {
                        if ($setflags = SetFlag::multiCrop($formula)) {
                            $extractions[] = new FormulaSetFlag($formula, $setflags);
                        } else {
                            $extractions[] = new Formula($formula);
                        }
                    }
                    break;
                case 'calibration':
                    if ($entity->calibrations) {
                        $extractions[] = new Calibration($entity->calibrations, $entity->skip_calibration);
                    }
                    break;
                case 'setflag':
                    $field = $entity->formula;

                    if ($field && $crop = SetFlag::singleCrop($field)) {
                        $extractions[] = new \Tobuli\Sensors\Extractions\SetFlag($crop->getStart(), $crop->getCount());
                    }
                    break;
                case 'bin':
                    if ($entity->decbin) {
                        //$extractions[] = new Bin(10);
                    }
                    if ($entity->hexbin) {
                        //$extractions[] = new Bin(16);
                    }
                    break;
                case 'skip_empty':
                    if ($entity->skip_empty) {
                        $extractions[] = new SkipEmpty();
                    }
                    break;
                case 'minmax':
                    $extractions[] = new Percentages($entity->min_value, $entity->max_value);
                    $sensor->setUnit('%');
                    break;
                case 'unit':
                    $sensor->setUnit($entity->unit_of_measurement);
                    break;
                case 'unit_type':
                    $sensor->setUnitType($entity->unit_type);
                    break;
                case 'full_tank':
                    $sensor->setFullTank($entity->full_tank);
                    $sensor->setFullTankValue($entity->full_tank_value);

                    if (is_numeric($entity->full_tank)
                        && is_numeric($entity->full_tank_value)
                        && ($entity->full_tank != $entity->full_tank_value)) {
                        $extractions[] = new Correlation($entity->full_tank, $entity->full_tank_value);
                    }
                    break;
                case 'bitcut':
                    if ($bitCut = $entity->bitcut) {
                        $extractions[] = new BitCut($bitCut['start'], $bitCut['count'], $bitCut['base'] ?? null);
                    }
                    break;
                case 'mapping':
                    if ($entity->mappings) {
                        $sensor->setMapping(new Mapping($entity->mappings));
                    }
                    break;
                case 'ascii':
                    if ($entity->ascii) {
                        $extractions[] = new Ascii();
                    }
                    break;
            }
        }

        if ($entity->type == FuelTank::getType()) {
            $extractions[] = new SkipEmpty();
        }

        $sensor->setExtraction(new Extraction($extractions));

        return $sensor;
    }

}