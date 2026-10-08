<?php

namespace App\Transformers\TrackerLite;

use App\Transformers\BaseTransformer;
use FractalTransformer;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;
use Tobuli\Entities\TaskSet;

class TasksSetTransformer extends BaseTransformer
{
    /**
     * @var string[]
     */
    protected $availableIncludes = [
        'locations'
    ];

    /**
     * @var string[]
     */
    protected $defaultIncludes = [
        //'locations'
    ];

    /**
     * @return string[]
     */
    protected static function requireLoads(): array
    {
        return ['locations'];
    }

    /**
     * @param TaskSet $entity
     * @return array
     */
    public function transform(TaskSet $entity) {
        return [
            'id' => $entity->id,
            'title' => $entity->title,
        ];
    }

    /**
     * @param TaskSet $entity
     * @return mixed
     */
    public function includeLocations(TaskSet $entity)
    {
        $locations = $entity->locations()
            ->orderBy('order')
            ->paginate(15)
            ->setPath(route('trackerlite.task-set.locations', $entity->id));

        return $this->collection($locations, new TasksSetLocationTransformer())
            ->setPaginator(new IlluminatePaginatorAdapter($locations));
    }
}