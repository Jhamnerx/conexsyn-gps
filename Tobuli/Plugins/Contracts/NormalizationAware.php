<?php

namespace Tobuli\Plugins\Contracts;

interface NormalizationAware
{
    public function normalize(array &$input);
}