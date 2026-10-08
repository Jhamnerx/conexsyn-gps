<?php

namespace App\Transformers\TrackerLite;

use App\Transformers\BaseTransformer;
use FractalTransformer;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;
use Tobuli\Entities\TaskSetLocation;

class TasksSetLocationTransformer extends BaseTransformer
{
    /**
     * @return string[]
     */
    protected static function requireLoads()
    {
        return ['tasks'];
    }

    /**
     * @var string[]
     */
    protected $availableIncludes = [
        'tasks'
    ];

    /**
     * @var string[]
     */
    protected $defaultIncludes = [
        'tasks'
    ];

    /**
     * @param TaskSetLocation $location
     * @return array
     */
    public function transform(TaskSetLocation $location): array
    {
        return [
            'id' => $location->id,
            'order'     => $location->order + 1,
            'coordinates' => [
                'lat' => $location->lat,
                'lng' => $location->lng,
            ],
            'address'   => getGeoAddress($location->lat, $location->lng),
            'time_from' => $location->time_from,
            'time_to'   => $location->time_to,
        ];
    }

    /**
     * @param TaskSetLocation $location
     * @return \League\Fractal\Resource\Collection
     */
    public function includeTasks(TaskSetLocation $location)
    {
        $tasks = $location->tasks()
            ->orderBy('task_set_location_tasks_pivot.task_order')
            ->paginate(15, ['*'], 'tp')
            ->setPageName('tp')
            ->setPath(route('trackerlite.task-set-location.tasks', [
                'id' => $location->task_set_id,
                'location' => $location->id
            ]));

        return $this->collection($tasks, new TaskTransformer())
            ->setPaginator(new IlluminatePaginatorAdapter($tasks));
    }
}