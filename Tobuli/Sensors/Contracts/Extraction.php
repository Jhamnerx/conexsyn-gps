<?php


namespace Tobuli\Sensors\Contracts;


interface Extraction
{
    public function parse($value);
}