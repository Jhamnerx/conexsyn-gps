<?php

namespace Tobuli\History\Actions;


class AppendOverspeeding extends ActionAppend
{
    protected $tolerance;
    protected $checkEngine;

    static public function required()
    {
        return [
            AppendSpeed::class,
            AppendEngineStatus::class,
        ];
    }

    static public function after()
    {
        return [
            AppendSpeedLimitStatic::class,
            AppendSpeedLimitRoads::class,
            AppendSpeedLimitGeofence::class,
            AppendSpeedLimitGeofenceMulti::class,
        ];
    }

    public function boot()
    {
        if ($this->history->hasConfig('speed_limit_tolerance')) {
            $this->tolerance = intval($this->history->config('speed_limit_tolerance'));
        } else {
            $this->tolerance = 0;
        }

        $this->checkEngine = settings('plugins.overspeed_only_engine_on.status');
    }

    public function proccess(&$position)
    {
        $position->overspeeding = 0;

        if ( ! $this->isOverspeed($position))
            return;

        $position->overspeeding++;

        $previous = $this->getPrevPosition();

        if ($previous && !empty($previous->overspeeding) && $previous->speed_limit == $position->speed_limit)
            $position->overspeeding += $previous->overspeeding;
    }

    protected function isOverspeed($position)
    {
        if (is_null($position->speed_limit))
            return false;

        if ($this->checkEngine && !$position->engine)
            return false;

        return ($position->speed_limit + $this->tolerance) < $position->speed;
    }
}