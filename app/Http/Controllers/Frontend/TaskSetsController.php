<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Tobuli\Entities\Device;
use Tobuli\Services\TaskSetUserService;

class TaskSetsController extends Controller
{
    private TaskSetUserService $service;

    protected function afterAuth($user)
    {
        $this->service = new TaskSetUserService($user);
    }

    public function index()
    {
        return $this->getList('index');
    }

    public function table()
    {
        return $this->getList('table');
    }

    private function getList(string $view)
    {
        $this->checkException('task_sets', 'view');

        $items = $this->user->taskSets()->selectDeviceId()->paginate();

        $devices = Device::whereIn('id', $items->pluck('device_id'))->get();

        $items->through(function ($item) use ($devices) {
            $item['device'] = $devices->find($item['device_id']);

            return $item;
        });

        return view('Frontend.TaskSets.' . $view)->with(compact('items'));
    }

    public function create()
    {
        $this->checkException('task_sets', 'store');

        return view('Frontend.TaskSets.create');
    }

    public function store()
    {
        $this->checkException('task_sets', 'store');

        $this->data['user_id'] = $this->user->id;

        $item = $this->service->store($this->data);

        return ['status' => 1, 'data' => $item];
    }

    public function edit()
    {
        $item = $this->user->taskSets()->findOrFail(request()->id);

        $this->checkException('task_sets', 'edit', $item);

        return view('Frontend.TaskSets.edit')->with(compact('item'));
    }

    public function update(): array
    {
        $item = $this->user->taskSets()->findOrFail(request()->id);

        $this->checkException('task_sets', 'edit', $item);

        $success = $this->service->update($item, \Arr::except($this->data, ['user_id']));

        return ['status' => (int)$success, 'data' => $item->attributesToArray()];
    }

    public function destroy(): array
    {
        $item = $this->user->taskSets()->findOrFail(request()->id);

        $this->checkException('task_sets', 'remove', $item);

        $success = $this->service->delete($item);

        return ['status' => (int)$success];
    }
}
