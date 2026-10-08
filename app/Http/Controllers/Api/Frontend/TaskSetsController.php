<?php

namespace App\Http\Controllers\Api\Frontend;

use Tobuli\Services\TaskSetUserService;

class TaskSetsController extends BaseController
{
    private TaskSetUserService $service;

    protected function afterAuth($user)
    {
        $this->service = new TaskSetUserService($user);
    }

    public function index()
    {
        $this->checkException('task_sets', 'view');

        $items = $this->user->taskSets()->paginate();

        return ['status' => 1, 'data' => $items];
    }

    public function show(int $id)
    {
        $item = $this->user->taskSets()->findOrFail($id);

        $this->checkException('task_sets', 'show', $item);

        return ['status' => 1, 'data' => $item];
    }

    public function store()
    {
        $this->checkException('task_sets', 'create');

        $this->data['user_id'] = $this->user->id;

        $item = $this->service->store($this->data);

        return ['status' => 1, 'data' => $item];
    }

    public function update(int $id): array
    {
        $item = $this->user->taskSets()->findOrFail($id);

        $this->checkException('task_sets', 'edit', $item);

        $success = $this->service->update($item, \Arr::except($this->data, ['user_id']));

        return ['status' => (int)$success, 'data' => $item->attributesToArray()];
    }

    public function destroy(int $id): array
    {
        $item = $this->user->taskSets()->findOrFail($id);

        $this->checkException('task_sets', 'delete', $item);

        $success = $this->service->delete($item);

        return ['status' => (int)$success];
    }
}
