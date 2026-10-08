<?php

namespace Tobuli\History\Actions;

class AppendFuelTheft extends ActionAppend
{
    public static function required()
    {
        if (config('addon.fuel_strategy_peaks')) {
            return [AppendFuelTheftByPeaks::class];
        }

        return [AppendFuelTheftChange::class];
    }

    public function boot()
    {
    }

    public function proccess(&$position)
    {
    }
}