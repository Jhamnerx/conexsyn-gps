<?php

namespace Tobuli\History\Actions;


class AppendHarshTurning extends ActionAppend
{
    protected $sensor;

    static public function required()
    {
        return [];
    }

    public function boot()
    {
        $this->sensor = $this->getSensor('harsh_turning');
    }

    public function proccess(&$position)
    {
        $position->harsh_turning = null;

        if ($this->sensor)
            $position->harsh_turning = $this->sensor->getValuePosition($position) ?? false;
    }
}