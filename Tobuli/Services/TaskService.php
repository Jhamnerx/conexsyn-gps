<?php

namespace Tobuli\Services;

use CustomFacades\Validators\TasksFormValidator;
use Illuminate\Support\Arr;
use Tobuli\Entities\Device;
use Tobuli\Entities\Task;
use Tobuli\Exceptions\ValidationException;
use Tobuli\Services\Tasks\TaskSetService;


class TaskService extends ModelService
{
    public function __construct()
    {
        $this->setValidationRulesStore(
            TasksFormValidator::getFacadeRoot()->rules['create']
        );

        $this->setValidationRulesUpdate(
            TasksFormValidator::getFacadeRoot()->rules['update']
        );
    }

    public function store(array $data): Task
    {
        $task = Task::make($data);
        $task->user_id = $data['user_id'];
        $task->save();

        if ($task->task_set_id) {
            TaskSetService::createTask($task->task_set_id, $task->toArray());
        }

        return $task;
    }

    public function update($model, array $data)
    {
        $this->validateDevice($model, $data);

        $status = $model->update($data);

        if ($model->task_set_id) {
            TaskSetService::createTask($model->task_set_id, $model->toArray());
        }

        return $status;
    }

    public function delete($model)
    {
        return $model->delete();
    }

    protected function validate($data, $rules)
    {
        parent::validate($data, $rules);

        if (isset($data['task_set_id'])) {
            $taskSetDevices = Task::where('task_set_id', $data['task_set_id'])->pluck('device_id')->all();

            if ($taskSetDevices && !in_array($data['device_id'], $taskSetDevices)) {
                $taskSetDevice = Device::find(Arr::first($taskSetDevices));

                throw new ValidationException(['task_set_id' => trans('validation.task_set_dedicated_to_device', [
                    'device' => $taskSetDevice ? $taskSetDevice->getDisplayName() : trans('front.other'),
                ])]);
            }
        }
    }

    private function validateDevice(Task $task, array $data): void
    {
        if (!array_key_exists('device_id', $data)) {
            return;
        }

        $taskSetId = array_key_exists('task_set_id', $data) ? $data['task_set_id'] : $task->task_set_id;

        if (!$taskSetId) {
            return;
        }

        $tasksDevices = Task::where('task_set_id', $taskSetId)->pluck('device_id', 'id')->all();

        if (empty($tasksDevices)) {
            return;
        }

        if (count($tasksDevices) === 1 && array_key_first($tasksDevices) === $task->id) {
            return;
        }

        if (in_array($data['device_id'], $tasksDevices)) {
            return;
        }

        $taskSetDevice = Device::find(Arr::first($tasksDevices));

        throw new ValidationException(['task_set_id' => trans('validation.task_set_dedicated_to_device', [
            'device' => $taskSetDevice->getDisplayName(),
        ])]);
    }

    protected function normalize(array $data)
    {
        if (array_key_exists('task_set_id', $data) && !$data['task_set_id']) {
            $data['task_set_id'] = null;
        }

        return $data;
    }
}
