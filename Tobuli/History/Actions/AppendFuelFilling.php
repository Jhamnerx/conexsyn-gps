<?php

namespace Tobuli\History\Actions;

class AppendFuelFilling extends ActionAppend
{
    public static function required()
    {
        if (config('addon.fuel_strategy_peaks')) {
            return [AppendFuelFillingByPeaks::class];
        }

        return [AppendFuelFillingChange::class];
    }

    public function boot()
    {
    }

    public function proccess(&$position)
    {
    }
}