<?php

namespace Tobuli\History\Actions;


class AppendGeofences extends ActionAppend
{
    public static function required()
    {
        return [
            AppendPosition::class,
        ];
    }

    public static function after()
    {
        return [
            AppendDiemRateGeofencesOverwrite::class,
            AppendOverspeedingProcessOnly::class,
        ];
    }

    public function boot(){}

    public function proccess(&$position)
    {
        if (property_exists($position, 'geofences'))
            return;

        if (property_exists($position, 'only_overspeeding') && $position->only_overspeeding === false)
            return;

        $previous = $this->getPrevPosition();

        $position->geofences = $this->isPointEqual($position, $previous)
            ? ($previous->geofences ?? [])
            : $this->history->inGeofences($position);
    }

    protected function isPointEqual($position, $prevPosition)
    {
        if ( ! $position)
            return false;

        if ( ! $prevPosition)
            return false;

        if ($position->latitude != $prevPosition->latitude)
            return false;

        if ($position->longitude != $prevPosition->longitude)
            return false;

        return true;
    }
}