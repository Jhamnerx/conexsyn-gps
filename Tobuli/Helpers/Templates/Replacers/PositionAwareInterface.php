<?php

namespace Tobuli\Helpers\Templates\Replacers;

use Tobuli\Entities\TraccarPosition;

interface PositionAwareInterface
{
    public function setPosition(?TraccarPosition $position): Replacer;
}