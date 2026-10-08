<?php

namespace Tobuli\History\Actions;

use Tobuli\History\Stats\StatCount;

class AppendPosition extends ActionAppend
{
    public function boot() {}

    public function proccess(&$position)
    {
        $position->timestamp = strtotime($position->time);

        if ($position->latitude)
            $position->latitude = round($position->latitude, 6);

        if ($position->longitude)
            $position->longitude = round($position->longitude, 6);
    }
}