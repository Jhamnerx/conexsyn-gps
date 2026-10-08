<?php

namespace App\Transformers\TrackerLite;

use App\Transformers\BaseTransformer;
use Tobuli\Entities\Task;
use Tobuli\Entities\TaskStatus;
use Formatter;

class TaskTransformer extends BaseTransformer
{
    protected $defaultIncludes = [
        'status',
        'task_set',
    ];

    /**
     * @return array
     */
    protected static function requireLoads(): array
    {
        return ['taskSet'];
    }

    /**
     * @param Task $entity
     * @return array
     */
    public function transform(Task $entity): array
    {
        return [
            'id'       => $entity->id,
            'title'    => $entity->title,
            'priority' => $entity->priority,
            'comment'  => $entity->comment,
            //'type' => 'PickupDelivery/Location'
            'pickup'   => $this->getPickupAddress($entity),
            'delivery' => $this->getDeliveryAddress($entity),
            'created_at' => Formatter::time()->human($entity->created_at),
            'updated_at' => Formatter::time()->human($entity->updated_at),
        ];
    }

    public function includeStatus(Task $task) {
        return $this->item($task, new TaskStatusTransformer(), false);
    }

    public function includeTaskSet(Task $task)
    {
        if (empty($task->task_set_id))
            return null;

        return $this->item($task->taskSet, new TasksSetTransformer(), false);
    }

    protected function getPickupAddress(Task $entity)
    {
        return $this->getAddress($entity, 'pickup');
    }

    protected function getDeliveryAddress(Task $entity)
    {
        if (is_null($entity->delivery_address_lat))
            return null;

        return $this->getAddress($entity, 'delivery');
    }

    protected function getAddress(Task $entity, $type)
    {
        return [
            'address' => $entity->{$type . '_address'},
            'coordinates' => [
                'lat' => $entity->{$type . '_address_lat'},
                'lng' => $entity->{$type . '_address_lng'},
            ],
            'time_from' => $entity->{$type . '_time_from'},
            'time_to' => $entity->{$type . '_time_to'}
        ];
    }
}