<?php

namespace App\Http\Controllers\Api\TrackerLite;

use App\Exceptions\ResourseNotFoundException;
use App\Transformers\TrackerLite\TasksSetLocationTransformer;
use App\Transformers\TrackerLite\TasksSetTransformer;
use FractalTransformer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Tobuli\Entities\TaskSet;
use Tobuli\Entities\TaskSetLocation;
use Validator;

class TasksSetController extends ApiController
{
    /**
     * @param int $id
     * @return JsonResponse
     */
    public function index(int $id): JsonResponse
    {
        $taskSet = TaskSet::whereHas('tasks', function (Builder $builder) {
            $builder->where('device_id', $this->deviceInstance->id);
        })->find($id);

        if (!$taskSet) throw new ResourseNotFoundException('Tasks set');

        return response()
            ->json(FractalTransformer::setIncludes('locations')->item($taskSet, new TasksSetTransformer)->toArray())
            ->setStatusCode(200);
    }

    /**
     * @param int $id
     * @return JsonResponse
     */
    public function taskSetLocations(int $id): JsonResponse
    {
        $taskSet = TaskSet::whereHas('tasks', function (Builder $builder) {
            $builder->where('device_id', $this->deviceInstance->id);
        })->find($id);

        if (!$taskSet) throw new ResourseNotFoundException('Tasks set');

        $locations = $taskSet->locations()->orderBy('order')->paginate();

        return response()
            ->json(FractalTransformer::setIncludes('tasks')->paginate($locations, new TasksSetLocationTransformer)->toArray())
            ->setStatusCode(200);
    }

    /**
     * @param int $id
     * @param int $location
     * @return JsonResponse
     */
    public function locationTasks(int $id, int $location): JsonResponse
    {
        $location = TaskSetLocation::whereHas('tasks', function (Builder $builder) {
            $builder->where('device_id', $this->deviceInstance->id);
        })->where('task_set_id', $id)->find($location);

        if (!$location) throw new ResourseNotFoundException('Task set location');

        return response()
            ->json(FractalTransformer::item($location, TasksSetLocationTransformer::class)->toArray())
            ->setStatusCode(200);
    }
}