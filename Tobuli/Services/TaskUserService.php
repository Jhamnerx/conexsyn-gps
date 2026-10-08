<?php

namespace Tobuli\Services;

use App\Exceptions\Manager;
use Tobuli\Entities\Device;
use Tobuli\Entities\Task;
use Tobuli\Entities\User;

class TaskUserService extends TaskService
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
        $data = ['user_id' => $this->user->id] + parent::normalize($data);

        return $data;
    }

    public function create(array $data): Task
    {
        $this->check('create');

        $data = onlyEditables(new Task(), $this->user, $data);

        return parent::create($data);
    }

    public function edit($model, array $data)
    {
        $this->check('edit', $model);

        $data = onlyEditables($model, $this->user, $data);

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

    protected function validate($data, $rules)
    {
        $device = Device::find($data['device_id']);

        $this->manager->check('devices', 'own', $device);

        parent::validate($data, $rules);
    }
}
