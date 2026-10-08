<?php

namespace Tobuli\History\Actions;

use Illuminate\Support\Collection;
use Tobuli\Entities\DeviceSensor;

abstract class AppendSensorValuePeaks extends ActionAppend
{
    public const STATE_INC = true;
    public const STATE_STILL = null;
    public const STATE_DEC = false;

    private const GAP_DURATION = 60 * 60;
    private const INVL_MIN_DURATION = 5 * 60;
    private const INVL_MIN_AMOUNT = 10;
    private const POST_MAX_DIFF_PERCENT = 0.2;
    private const POST_MIN_SCORE = 0.8;

    public static string $peakKey;

    protected Collection $sensors;
    protected DeviceSensor $sensor;
    protected int $sensorId;

    private array $positionsValues = [];

    public function boot()
    {
        $this->sensors = $this->getSensors();
    }

    final public function proccess(&$position)
    {
    }

    public function preproccess($positions)
    {
        if (count($positions) < 2) {
            return;
        }

        foreach ($this->sensors as $sensor) {
            $this->sensorId = $sensor->id;
            $this->sensor = $sensor;

            $prev = null;
            $prevDiffType = self::STATE_STILL;
            $peak = null;

            foreach ($positions as $curr) {
                $currValue = $this->getPositionValue($curr, $sensor);

                if ($currValue === null) {
                    continue;
                }

                if ($prev === null) {
                    $prev = $curr;

                    continue;
                }

                $diffType = $this->getDiffType($prev, $curr);

                if (!$peak && $diffType !== $prevDiffType && $prevDiffType === self::STATE_STILL) {
                    $peak = [
                        'positions' => [$prev, $curr],
                        'diffs' => [$diffType, $diffType],
                    ];

                    $this->assessPeak($peak);
                } elseif ($peak) {
                    $peak['positions'][] = $curr;
                    $peak['diffs'][] = $diffType;

                    $this->assessPeak($peak);
                }

                $prevDiffType = $diffType;
                $prev = $curr;
            }
        }
    }

    private function assessPeak(array &$peak): void
    {
        $positions = $peak['positions'];

        $begin = $positions[0];
        $end = end($positions);

        $duration = strtotime($end->time) - strtotime($begin->time);
        $isGap = count($positions) < self::INVL_MIN_AMOUNT && $duration >= self::GAP_DURATION;

        if (count($positions) < self::INVL_MIN_AMOUNT && !$isGap) {
            return;
        }

        if ($isGap && count($positions) < 2) {
            return;
        }

        if ($duration < self::INVL_MIN_DURATION) {
            return;
        }

        $diffs = $peak['diffs'];

        $beginValue = $this->getPositionValue($begin, $this->sensor);
        $endValue = $this->getPositionValue($end, $this->sensor);

        // positions are accumulated until amount and duration is satisfied,
        // without direction being checked, therefore it may have changed since first position
        $peakType = $endValue > $beginValue ? self::STATE_INC : self::STATE_DEC;

        if (end($diffs) === $peakType && !$isGap) {
            return;
        }

        $afterPeakAll = [];
        $afterPeakDiffs = [];

        while (end($diffs) !== $peakType && $diffs) {
            array_unshift($afterPeakDiffs, array_pop($diffs));
            array_unshift($afterPeakAll, array_pop($positions));
        }

        $peakPosition = end($positions);

        if (!$peakPosition) {
            $peak = null;
            return;
        }

        $peakValue = $this->getPositionValue($peakPosition, $this->sensor);

        if (!$peakValue || !$afterPeakAll) {
            $validPostPeakLevels = true;
        } else {
            $afterPeakMatches = array_filter(
                $afterPeakAll,
                fn ($pos) => abs($this->getPositionValue($pos, $this->sensor) / $peakValue - 1) < self::POST_MAX_DIFF_PERCENT
            );

            $validPostPeakLevels = count($afterPeakMatches) / count($afterPeakAll) > self::POST_MIN_SCORE;
        }

        if ($validPostPeakLevels || $isGap) {
            $peakData = [
                'begin_id' => $begin->id,
                'begin_time' => $begin->time,
                'begin_value' => $beginValue,
                'peak_id' => $peakPosition->id,
                'peak_time' => $peakPosition->time,
                'peak_value' => $peakValue,
                'type' => $peakType,
                'gap' => $isGap,
            ];

            $this->appendDiff($peakPosition, ['is_begin' => false] + $peakData);
            $this->appendDiff($begin, ['is_begin' => true] + $peakData);
        }

        $peak = null;

        // if in the interval after peak there was other position with direction, assess it with one position before it
        array_unshift($afterPeakAll, $peakPosition);
        array_unshift($afterPeakDiffs, reset($afterPeakDiffs));

        while ($afterPeakDiffs && reset($afterPeakDiffs) === self::STATE_STILL) {
            array_shift($afterPeakDiffs);
            $lastStillPos = array_shift($afterPeakAll);
        }

        if (isset($lastStillPos) && $afterPeakAll) {
            array_unshift($afterPeakDiffs, $this->getDiffType($lastStillPos, reset($afterPeakAll)));
            array_unshift($afterPeakAll, $lastStillPos);
        }

        if ($afterPeakDiffs) {
            $peak = [
                'positions' => $afterPeakAll,
                'diffs' => $afterPeakDiffs,
            ];
        }
    }

    private function appendDiff(object $position, array $peakData): void
    {
        if (isset($position->peaks[static::$peakKey][$this->sensorId])) {
            $prevData = $position->peaks[static::$peakKey][$this->sensorId];

            $prevDiff = abs($prevData['peak_value'] - $prevData['begin_value']);
            $currDiff = abs($peakData['peak_value'] - $peakData['begin_value']);

            $skipDiff = $prevDiff >= $currDiff;
        }

        if (!empty($skipDiff)) {
            return;
        }

        $position->peaks[static::$peakKey][$this->sensorId] = $peakData;
    }

    private function getDiffType(object $begin, object $end): ?bool
    {
        $duration = (strtotime($end->time) - strtotime($begin->time)) / 60;
        $size = $this->getPositionValue($end, $this->sensor)
            - $this->getPositionValue($begin, $this->sensor);

        $speed = $duration ? $size / $duration : $size;

        if ($speed > 0.5) {
            return self::STATE_INC;
        }

        if ($speed < -0.5) {
            return self::STATE_DEC;
        }

        return self::STATE_STILL;
    }

    protected function getPositionValue(object $position, DeviceSensor $sensor)
    {
        if (isset($this->positionsValues[$sensor->id])
            && array_key_exists($position->id, $this->positionsValues[$sensor->id])
        ) {
            return $this->positionsValues[$sensor->id][$position->id];
        }

        // $this->getSensorValue($sensor, $position) spoils usual data retrieve for other actions
        $value = $sensor->getValuePosition($position);

        if ($value !== null) {
            $value = floatval($value);
        }

        return $this->positionsValues[$sensor->id][$position->id] = $value;
    }

    abstract protected function getSensors(): Collection;
}
