<?php

namespace Tobuli\Services\EntityLoader;

use Tobuli\Entities\User;
use Tobuli\Services\EntityLoader\Filters\IdFilter;
use Tobuli\Services\EntityLoader\Filters\SearchFilter;

class UserDriversLoader extends EnityLoader
{
    protected User $user;

    public function __construct(User $user)
    {
        $this->user = $user;

        $this->setQueryItems(
            $this->user->drivers()
        );

        $this->filters = [
            new IdFilter('user_drivers'),
            new SearchFilter(null)
        ];
    }

    protected function transform($item)
    {
        $driver = new \stdClass();
        $driver->id = $item->id;
        $driver->name = $item->name;

        return $driver;
    }
}