<?php

namespace Tobuli\History\Actions;

use Tobuli\History\Stats\StatAvg;
use Tobuli\History\Stats\StatMax;
use Tobuli\History\Stats\StatMin;

class Rpm extends ActionStat
{
    public static function required()
    {
        return [AppendRpm::class];
    }

    public function boot()
    {
        $this->registerStat('rpm_max', new StatMax());
        $this->registerStat('rpm_min', new StatMin());
        $this->registerStat('rpm_avg', new StatAvg());
    }

    public function proccess($position)
    {
        $this->history->applyStat('rpm_max', $position->rpm);
        $this->history->applyStat('rpm_min', $position->rpm);

        if ($position->rpm > 0) {
            $this->history->applyStat('rpm_avg', $position->rpm);
        }
    }
}