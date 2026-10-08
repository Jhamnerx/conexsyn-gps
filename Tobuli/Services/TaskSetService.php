<?php

namespace Tobuli\Services;

use CustomFacades\Validators\TaskSetFormValidator;
use Illuminate\Support\Arr;
use Tobuli\Entities\Device;
use Tobuli\Entities\Task;
use Tobuli\Entities\TaskSet;
use Tobuli\Exceptions\ValidationException;

class TaskSetService extends ModelService
{
    public function __construct()
    {
        $this->setValidationRulesStore(
            TaskSetFormValidator::getFacadeRoot()->rules['create']
        );

        $this->setValidationRulesUpdate(
            TaskSetFormValidator::getFacadeRoot()->rules['update']
        );
    }

    public function store(array $data): TaskSet
    {
        $taskSet = TaskSet::make($data);
        $taskSet->user_id = $data['user_id'];
        $taskSet->save();

        $this->storeTasks($taskSet, $data);

        return $taskSet;
    }

    public function update($model, array $data)
    {
        $success = $model->update($data);

        $this->storeTasks($model, $data);

        return $success;
    }

    public function delete($model)
    {
        return $model->delete();
    }

    protected function storeTasks(TaskSet $taskSet, array $data): void
    {
        if (!isset($data['tasks'])) {
            return;
        }

        $tasks = $taskSet->user->tasks()->whereIn('id', $data['tasks'])->get();

        if ($tasks->isEmpty()) {
            $taskSet->tasks()->update(['task_set_id' => null]);
            return;
        }

        if ($tasks->pluck('id', 'device_id')->count() > 1) {
            throw new ValidationException(trans('validation.tasks_only_one_device'));
        }

        $newDeviceId = $tasks->first()->device_id;
        $deviceIds = $taskSet->tasks()->pluck('device_id')->all();

        if (!in_array($newDeviceId, $deviceIds)) {
            $taskSetDevice = Device::find(Arr::first($deviceIds));

            throw new ValidationException(['task_set_id' => trans('validation.task_set_dedicated_to_device', [
                'device' => $taskSetDevice ? $taskSetDevice->getDisplayName() : trans('front.other'),
            ])]);
        }

        Task::whereIn('id', $tasks->pluck('id'))
            ->update(['task_set_id' => $taskSet->id]);
    }
}
