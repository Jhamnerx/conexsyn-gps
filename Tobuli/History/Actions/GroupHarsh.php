<?php

namespace Tobuli\History\Actions;

class GroupHarsh extends ActionGroup
{
    private array $stats = [
        'harsh_acceleration',
        'harsh_breaking',
        'harsh_turning',
    ];

    public static function required()
    {
        return [
            AppendHarshAcceleration::class,
            AppendHarshBreaking::class,
            AppendHarshTurning::class,
        ];
    }

    public function boot()
    {
    }

    public function proccess($position)
    {
        foreach ($this->stats as $stat) {
            if (!empty($position->$stat)) {
                $this->history->groupStartEnd($stat, $position);
            }
        }
    }
}