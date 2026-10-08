<?php

namespace Tobuli\History\Actions;

use Tobuli\Entities\DeviceSensor;

class AppendFuelFillingByPeaks extends ActionAppend
{
    private array $sensors;
    private int $minFuelChange;
    private ?int $fuelDetectSecAfterStop;

    public static function required()
    {
        return [
            AppendFuelPeaks::class,
            AppendMoveStartAt::class,
            AppendLastStopAt::class,
        ];
    }

    public function boot()
    {
        $this->sensors = $this->getDevice()->sensors->filter(fn ($sensor) => $sensor->type == 'fuel_tank')
            ->keyBy('id')
            ->all();
        $this->minFuelChange = $this->history->config('min_fuel_fillings');
        $this->fuelDetectSecAfterStop = $this->getDevice()->fuel_detect_sec_after_stop;
    }

    public function proccess(&$position)
    {
        $peaks = $position->peaks[AppendFuelPeaks::$peakKey] ?? [];

        foreach ($peaks as $sensorId => $peak) {
            if ($peak['is_begin']) {
                continue;
            }

            if ($peak['type'] !== AppendFuelPeaks::STATE_INC) {
                continue;
            }

            $sensor = $this->sensors[$sensorId];
            $diff = $peak['peak_value'] - $peak['begin_value'];

            if ($diff < $this->getMinFuelChange($sensor)) {
                continue;
            }

            if (!$this->checkFillingTiming($position)) {
                continue;
            }

            $position->fuel_filling = [
                'sensor_id' => $sensor->id,
                'name' => $sensor->name,
                'previous' => $peak['begin_value'],
                'current' => $peak['peak_value'],
                'diff' => $diff,
                'unit' => $sensor->unit_of_measurement
            ];
//            dump($position->fuel_filling, $position->time); // todo: remove

            return;
        }
    }

    protected function checkFillingTiming($position): bool
    {
        if (!$this->fuelDetectSecAfterStop) {
            return true;
        }

        $positionTime = strtotime($position->time);

        if ($position->move_start_at
            && $positionTime - strtotime($position->move_start_at) <= $this->fuelDetectSecAfterStop
        ) {
            return true;
        }

        return $position->last_stop_at
            && $positionTime - strtotime($position->last_stop_at) <= $this->fuelDetectSecAfterStop;
    }

    protected function getMinFuelChange(DeviceSensor $sensor)
    {
        if ($this->minFuelChange != 10) {
            return $this->minFuelChange;
        }

        $maxTank = $sensor->getMaxTankValue();

        if ($maxTank < 100) {
            return $this->minFuelChange;
        }

        return $maxTank * 0.1;
    }
}