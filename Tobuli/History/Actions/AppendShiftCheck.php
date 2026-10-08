<?php

namespace Tobuli\History\Actions;

use Formatter;
use Illuminate\Support\Facades\Validator;

class AppendShiftCheck extends ActionAppend
{
    private ?array $shiftWeekdays;
    private ?string $shiftStart;
    private ?string $shiftFinish;
    private bool $shiftPassesDay;

    public function boot()
    {
        $parameters = $this->history->allConfig();

        $this->shiftWeekdays  = $this->resolveWeekdays($parameters['weekdays'] ?? null);
        $this->shiftStart     = $this->resolveTime($parameters['shift_start'] ?? null);
        $this->shiftFinish    = $this->resolveTime($parameters['shift_finish'] ?? null);

        $this->shiftPassesDay = $this->shiftStart > $this->shiftFinish;
    }

    public function proccess(&$position)
    {
        $position->inShift = $this->isDayInShift($position) && $this->isTimeInShift($position);
    }

    private function resolveTime(?string $value): ?string
    {
        if (empty($value)) {
            return null;
        }

        $time = Formatter::time()->reverse($value, 'H:i:s');

        if ($time === false) {
            throw new \InvalidArgumentException("Invalid shift time value: $value");
        }

        return $time;
    }

    private function resolveWeekdays(?array $weekdays): ?array
    {
        if (empty($weekdays)) {
            return null;
        }

        $validator = Validator::make(
            ['weekdays' => $weekdays],
            ['weekdays.*' => ['min:1', 'max:7']]
        );

        if ($errors = $validator->errors()->all()) {
            throw new \InvalidArgumentException(implode(". ", $errors));
        }

        return array_unique($weekdays);
    }

    private function isTimeInShift($position): bool
    {
        if (!$this->isTimeDefined()) {
            return true;
        }

        $time = (new \DateTime($position->time))->format('H:i:s');

        return $this->shiftPassesDay
            ? $time >= $this->shiftStart || $time < $this->shiftFinish
            : $time >= $this->shiftStart && $time < $this->shiftFinish;
    }

    private function isDayInShift($position): bool
    {
        if (!$this->isDayDefined()) {
            return true;
        }

        $day = Formatter::date()->convert($position->time, 'N');

        return in_array($day, $this->shiftWeekdays);
    }

    private function isDayDefined(): bool
    {
        return !empty($this->shiftWeekdays);
    }

    private function isTimeDefined(): bool
    {
        return !empty($this->shiftStart) && !empty($this->shiftFinish);
    }
}