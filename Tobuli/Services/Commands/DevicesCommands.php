<?php


namespace Tobuli\Services\Commands;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Collection AS EloquentCollection;

interface DevicesCommands
{
    /**
     * @param EloquentCollection|Builder $devices
     */
    public function get($devices, bool $intersect) : Collection;
}