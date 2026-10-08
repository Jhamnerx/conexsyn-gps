<?php

namespace Tobuli\History\Actions;

class AppendLoadChange extends ActionAppend
{
    private $sensor;
    private $startedAt = null;
    private $startLoad = null;
    private int $minLoadDuration = 300;
    private int $minDetectChange = 25;

    static public function required()
    {
        return [
            AppendMoveState::class,
        ];
    }

    public function boot()
    {
        $this->sensor = $this->getDevice()->getLoadSensor();

        if ($this->history->hasConfig('min_load_duration')) {
            $this->minLoadDuration = $this->history->config('min_load_duration');
        }

        if ($this->history->hasConfig('min_detect_change')) {
            $this->minDetectChange = $this->history->config('min_detect_change');
        }
    }

    public function proccess(&$position)
    {
        if (!$this->sensor) {
            return;
        }

        if ($this->isStarting($position)) {
            $this->startedAt = $position->time;
            $this->startLoad = $this->getSensorValue($this->sensor, $position);

            return;
        }

        if ($this->isOngoing($position)) {
            return;
        }

        if (!$this->isEnding($position)) {
            return;
        }


        if (!$this->checkLoadDuration($position)) {
            $this->resetLoad();
            return;
        }

        $prevPosition = $this->getPrevPosition();
        $prevValue = $this->getSensorValue($this->sensor, $prevPosition);

        $loading = $prevValue > $this->startLoad;
        $difference = abs($prevValue - $this->startLoad);
        $changePercentage = ($difference / ($loading ? $prevValue : $this->startLoad)) * 100;

        if ($changePercentage < $this->minDetectChange) {
            $this->resetLoad();
            return;
        }

        $position->loadChange = [
            'state'             => (int)$loading,
            'time'              => $this->startedAt,
            'previous_load'     => $this->startLoad,
            'current_load'      => $prevValue,
            'difference'        => $difference,
        ];

        $this->resetLoad();
    }

    private function checkLoadDuration($position): bool
    {
        if (!$this->minLoadDuration) {
            return true;
        }

        if (!$this->startedAt) {
            return false;
        }

        return (strtotime($position->time) - strtotime($this->startedAt)) >= $this->minLoadDuration;
    }

    private function isEnding($position): bool
    {
        return $this->startedAt !== null && $position->moving === AppendMoveState::MOVING;
    }

    private function isStarting($position): bool
    {
        return $this->startedAt === null
            && $position->moving === AppendMoveState::STOPED
            && $this->getSensorValue($this->sensor, $position) !== null;
    }

    private function isOngoing($position): bool
    {
        return $this->startedAt !== null && $position->moving === AppendMoveState::STOPED;
    }

    private function resetLoad(): void
    {
        $this->startedAt = null;
        $this->startLoad = null;
    }
}