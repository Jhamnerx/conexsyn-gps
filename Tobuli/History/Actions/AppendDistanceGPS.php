<?php

namespace Tobuli\History\Actions;


class AppendDistanceGPS extends ActionAppend
{
    static public function required()
    {
        return [
            AppendPosition::class,
            AppendLastValidPoint::class
        ];
    }

    public function boot(){

    }

    public function proccess(&$position)
    {
        if (isset($position->distance_gps))
            return;

        $position->distance_gps = 0;

        if (!($position->valid && $position->lastValidPoint))
            return;

        $position->distance_gps = getDistance(
            $position->latitude,
            $position->longitude,
            $position->lastValidPoint[0],
            $position->lastValidPoint[1]
        );
    }
}