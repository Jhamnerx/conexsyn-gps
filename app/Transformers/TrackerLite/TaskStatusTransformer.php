<?php

namespace App\Transformers\TrackerLite;

use App\Transformers\BaseTransformer;
use Tobuli\Entities\Task;
use Tobuli\Entities\TaskStatus;

class TaskStatusTransformer extends BaseTransformer
{
    /**
     * @param Task $entity
     * @return array
     */
    public function transform(Task $entity): array
    {
        return [
            'key' => $entity->status,
            'title' => trans(TaskStatus::$statuses[$entity->status]),
        ];
    }
}