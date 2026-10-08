<?php

namespace Tobuli\History\Actions;

class AppendAnonymizerCoordinates extends ActionAppend
{
    protected ?float $lastLat = null;
    protected ?float $lastLng = null;

    public static function required()
    {
        return [AppendAnonymized::class];
    }

    public static function after()
    {
        return [AppendDistanceGPS::class];
    }

    public function boot(){}

    public function proccess(&$position)
    {
        if ($position->anonymized) {
            $position->latitude = $this->lastLat;
            $position->longitude = $this->lastLng;
        } else {
            $this->lastLat = $position->latitude;
            $this->lastLng = $position->longitude;
        }
    }
}