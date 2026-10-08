<?php

namespace Tobuli\History\Actions;


class AppendLastValidPoint extends ActionAppend
{
    protected $lastValidPoint = null;

    static public function required()
    {
        return [
            AppendPosition::class,
        ];
    }

    public function boot(){}

    public function proccess(&$position)
    {
        if (isset($position->lastValidPoint))
            return;

        $position->lastValidPoint = $this->lastValidPoint;

        if (!$position->valid)
            return;

        $this->lastValidPoint = [
            $position->latitude,
            $position->longitude,
        ];
    }
}