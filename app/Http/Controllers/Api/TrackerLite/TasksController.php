<?php

namespace App\Http\Controllers\Api\TrackerLite;

use App\Exceptions\PermissionException;
use App\Exceptions\ResourseNotFoundException;
use App\Transformers\TrackerLite\TaskTransformer;
use Illuminate\Http\JsonResponse;
use Tobuli\Entities\TaskStatus;
use Tobuli\Exceptions\ValidationException;
use Validator;
use Tobuli\Entities\Task;
use FractalTransformer;


class TasksController extends \App\Http\Controllers\Api\Tracker\TasksController
{
    /**
     * @return JsonResponse
     */
    public function getTasks(): JsonResponse
    {
        $data = Task::where('device_id', $this->deviceInstance->id)
            ->filter(request()->all())
            ->orderByDesc('created_at')
            ->paginate();

        return response()
            ->json(FractalTransformer::paginate($data, new TaskTransformer())->toArray())
            ->setStatusCode(200);
    }

    public function getStatuses() {
        return response()
            ->json(['data' => array_chunk(TaskStatus::$statuses, 1, true)])
            ->setStatusCode(200);
    }

    public function getSignature($taskStatusId) {
        $taskStatus = TaskStatus::find($taskStatusId);

        if ( ! $taskStatus)
            throw new ResourseNotFoundException('global.task_status');

        if ( ! $taskStatus->signature)
            throw new ResourseNotFoundException('global.task_status');

        return response($taskStatus->signature)
            ->header('Content-Type', 'image/jpeg')
            ->header('Pragma', 'public')
            ->header('Content-Disposition', 'inline; filename="photo.jpeg"')
            ->header('Cache-Control', 'max-age=60, must-revalidate');
    }

    public function update($taskId) {
        $validator = Validator::make(request()->all(), [
            'status'    => 'required|in:' . implode(',', array_keys(TaskStatus::$statuses)),
            'signature' => 'required_if:status,' . TaskStatus::STATUS_COMPLETED,
        ]);

        if ( $validator->fails() )
            throw new ValidationException($validator->errors());

        $task = Task::find($taskId);

        if ( ! $task)
            throw new ResourseNotFoundException('global.task');

        if ($task->device_id != $this->deviceInstance->id)
            throw new PermissionException();

        $taskStatus = new TaskStatus();
        $taskStatus->task_id = $task->id;
        $taskStatus->status = request()->input('status');

        if ( ! empty(request()->input('signature'))) {
            $taskStatus->signatureBase64 = request()->input('signature');
        }

        $taskStatus->save();

        $task->status = (int) request()->input('status');
        $task->save();

        return response()
            ->json(FractalTransformer::item($task, new TaskTransformer())->toArray())
            ->setStatusCode(200);
    }
}