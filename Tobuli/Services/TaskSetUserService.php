<?php

namespace Tobuli\Services;

use App\Exceptions\Manager;
use Tobuli\Entities\TaskSet;
use Tobuli\Entities\User;

class TaskSetUserService extends TaskSetService
{
    protected User $user;
    protected Manager $manager;

    public function __construct(User $user)
    {
        parent::__construct();

        $this->user = $user;
        $this->manager = new Manager($user);
    }

    protected function normalize(array $data)
    {
        return ['user_id' => $this->user->id] + parent::normalize($data);
    }

    public function create(array $data): TaskSet
    {
        $this->check('create');

        return parent::create($data);
    }

    public function edit($model, array $data)
    {
        $this->check('edit', $model);

        return parent::edit($model, $data);
    }

    public function remove($model)
    {
        $this->check('remove', $model);

        return parent::remove($model);
    }

    protected function check($action, $model = null)
    {
        $this->manager->check('tasks', $action, $model);
    }
}
