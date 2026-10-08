<?php


namespace Tobuli\Services\EntityLoader;

use stdClass;
use Tobuli\Entities\User;
use Tobuli\Services\EntityLoader\Filters\IdFilter;
use Tobuli\Services\EntityLoader\Filters\SearchFilter;

class UsersLoader extends EnityLoader
{
    /**
     * @var User
     */
    protected $user;

    protected $orderStored = true;

    public function __construct(User $user)
    {
        $this->user = $user;

        $this->setQueryItems(User::userAccessible($this->user)->clearOrdersBy());

        $this->setRequestKey('users');

        $this->filters = [
            new IdFilter('users'),
            new SearchFilter(null)
        ];
    }

    protected function transform($user)
    {
        $item = new stdClass();

        $item->id = $user->id;
        $item->name = $user->email;

        return $item;
    }

    protected function scopeOrderDefault($query)
    {
        return $query->orderBy('users.email', 'asc');
    }
}