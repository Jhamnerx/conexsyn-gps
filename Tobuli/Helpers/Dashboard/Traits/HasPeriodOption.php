<?php namespace Tobuli\Helpers\Dashboard\Traits;

use Carbon\Carbon;

trait HasPeriodOption
{
    private static $PERIOD_DAY = 'day';
    private static $PERIOD_WEEK = 'week';
    private static $PERIOD_MONTH = 'month';

    private function getPeriod()
    {
        $from = Carbon::now();

        switch ($this->getConfig('options.period')) {
            case self::$PERIOD_WEEK:
                $from->subWeek();
                break;
            case self::$PERIOD_MONTH:
                $from->subMonth();
                break;
            default:
                $from->subDay();
                break;
        }

        return $from->toDateTimeString();
    }
}