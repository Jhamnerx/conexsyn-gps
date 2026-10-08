<?php

namespace Tobuli\Services\Tasks;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Tobuli\Entities\Task;
use Tobuli\Entities\TaskSet;
use Tobuli\Entities\TaskSetLocation;

class TaskSetService
{
    public const DISTANCE_OFFSET = 100; // meters

    /**
     * @var array
     */
    protected array $data;

    /**
     * @var Collection
     */
    protected Collection $tasksReferences;

    /**
     * @var Collection
     */
    protected Collection $tasks;

    /**
     * @var Collection
     */
    protected Collection $locations;

    /**
     * @param array $data
     */
    public function __construct(array $data)
    {
        $this->data = $data;
        $this->locations = collect([]);
        $this->tasks = collect([]);
        $this->tasksReferences = collect([]);
    }

    /**
     * @param array $data
     * @return self
     */
    public static function create(array $data): self
    {
        return new static($data);
    }

    public static function createTask(int $taskSetId, array $task)
    {
        $taskSet = TaskSet::find($taskSetId);
        $data = [
            'tasks' => $taskSet->tasks()->when(!empty($task['id']), function (Builder $builder) use ($task) {
                $builder->where('id', '!=', $task['id']);
            })->get()->map(function (Task $task) {
                return $task->toArray();
            })->toArray(),
            'user_id' => $task['user_id'],
            'device_id' => $task['device_id'],
        ];
        $data['tasks'][] = $task;
        $instance = new static($data);
        $instance->update($taskSet);
    }

    /**
     * @return void
     */
    public function store() :void
    {
        $this->mapTasksData($this->data['tasks'])
            ->addTasksToLocations();


        DB::beginTransaction();
        try {
            $taskSet = new TaskSet(array_merge($this->data, [
                'title' => $this->data['task_set_title']
            ]));
            $taskSet->save();
            $this->tasks->each(function (array $task) use ($taskSet) {
                $this->createNewTask($task, $taskSet);
            });

            $this->locations->each(function (array $location, int $order) use ($taskSet) {
                $entity = $taskSet->locations()->create(array_merge($location, ['order' => $order]));
                $entity->tasks()->sync(
                    $this->tasksReferences->whereIn('ref', $location['tasks'])->mapWithKeys(function (array $reference, int $order) {
                        return [$reference['id'] => ['task_order' => $order, 'address_key' => $reference['key']]];
                    })
                );
            });
        } catch (\Exception $exception) {
            DB::rollBack();
            dd($exception->getMessage());
        }
        DB::commit();
    }

    /**
     * @param TaskSet $taskSet
     * @return void
     */
    public function update(TaskSet $taskSet): void
    {
        $data = collect($this->data['tasks']);
        $taskSet->load([
            'locations.tasks' => function (BelongsToMany $builder) use ($data) {
                $builder->whereIn('id', $data->pluck('id')->filter());
            }
        ]);

        $existingTasks = $taskSet->locations->pluck('tasks')->collapse();
        $this->locations = $taskSet->locations->map(function (TaskSetLocation $location) {
            return array_merge($location->toArray(), [
                'time_from' => $location->time_from ? Carbon::parse($location->time_from) : null,
                'time_to' => $location->time_to ? Carbon::parse($location->time_to) : null,
                'tasks' => collect([]),
            ]);
        });

        // handle new tasks
        $newTasks = $data->filter(function (array $data) {
            return empty($data['id']);
        })->toArray();
        $this->mapTasksData($newTasks);

        // Check if task set has tasks without locations
        // Should be removed in future
        $tasksWithoutLocations = $taskSet->tasks()->whereDoesntHave('locations', function (Builder $builder) use ($taskSet) {
            $builder->where('task_set_id', $taskSet->id);
        })->get();
        $existingTasks = $existingTasks->merge($tasksWithoutLocations);

        // filter changed tasks
        $updatedTasks = $data->filter(function (array $data) use ($tasksWithoutLocations) {
            return !empty($data['id']);
        })->filter(function (array $task) use ($existingTasks, $tasksWithoutLocations) {
            if ($tasksWithoutLocations->firstWhere('id', $task['id'])) {
                return true;
            }

            $entity = $existingTasks->firstWhere('id', $task['id']);
            $isDirty = $this->taskIsDirty($entity, $task);

            if (!$isDirty) {
                $entity->update($task);
                return false;
            }

            return true;
        });

        // handle updated tasks
        $this->mapTasksData($updatedTasks->toArray())
            ->addTasksToLocations();

        DB::beginTransaction();
        try {
            // save tasks data
            // detach from old locations if it has changed
            $this->tasksReferences->each(function (array $reference) use ($existingTasks, $taskSet) {
                $data = $this->tasks->firstWhere('ref', $reference['ref']);
                if (isset($reference['id'])) {
                    $task = $existingTasks->firstWhere('id', $reference['id']);
                    $taskLocation = $this->locations->filter(function (array $location) use ($reference) {
                        return $location['tasks']->where('ref', $reference['ref'])->where('key', $reference['key'])->first();
                    })->first();

                    // Detach from old location
                    if ($task->pivot && $task->pivot->task_set_location_id) {
                        $task->locations()->where('task_set_location_id', $task->pivot->task_set_location_id)->detach();
                    }

                    $task->update($data['data']);
                    return;
                }

                $this->createNewTask($data, $taskSet);
            });

            $locationsOrder = $taskSet->locations->count();
            $this->locations->each(function (array $location, $index) use ($taskSet, $existingTasks, &$locationsOrder) {
                if (!$location['tasks']->count()) return;

                $locationEntity = null;
                if (!empty($location['id'])) {
                    $locationEntity = $taskSet->locations->firstWhere('id', $location['id']);
                } else {
                    $locationEntity = $taskSet->locations()->create(array_merge($location, ['order' => $locationsOrder]));
                    $locationsOrder++;
                }

                $locationEntity->loadCount('tasks');
                $order = $locationEntity->tasks_count;
                $locationEntity->tasks()->attach(
                    $location['tasks']->mapWithKeys(function (array $task) use (&$order) {
                        $id = $this->tasksReferences->firstWhere('ref', $task['ref'])['id'];
                        return [$id => ['task_order' => $order++, 'address_key' => $task['key']]];
                    })
                );
            });

            $taskSet->locations()->whereDoesntHave('tasks')->delete();
            DB::commit();
        } catch (\Exception $exception) {
            DB::rollBack();
            dd($exception);
        }
    }

    /**
     * @param array $tasks
     * @return self
     */
    private function mapTasksData(array $tasks): self
    {
        collect($tasks)->each(function ($task) {
            $ref = !empty($task['id']) ? (int)$task['id'] : uniqid();
            $this->tasks->push([
                'ref' => $ref,
                'data' => $task
            ]);

            foreach(['pickup', 'delivery'] as $key) {
                $lat = $task[$key . '_address_lat'] ?? null;
                $lng = $task[$key . '_address_lng'] ?? null;
                $timeFrom = $task[$key . '_time_from'] ?? null;
                $timeTo = $task[$key . '_time_to'] ?? null;

                if (!$lat || !$lng) return;
                $this->tasksReferences->push([
                    'ref' => $ref,
                    'id' => !empty($task['id']) ? (int)$task['id'] : null,
                    'key' => $key,
                    'lat' => $lat,
                    'lng' => $lng,
                    'time_from' => $timeFrom ? Carbon::parse($timeFrom) : null,
                    'time_to' => $timeFrom ? Carbon::parse($timeTo) : null,
                ]);
            }
        });

        $this->tasksReferences = $this->tasksReferences->sortBy(function (array $task) {
            if (!$task['time_from'] || !$task['time_to']) return 0;
            return -$task['time_from']->diffInMilliseconds($task['time_to']);
        });

        return $this;
    }

    /**
     * @return self
     */
    private function addTasksToLocations(): self
    {
        $this->tasksReferences->each(function (array $task) {
            $index = $this->locations->search(function (array $location) use ($task) {
                return $this->isInLocationZone($location, $task['lat'], $task['lng'])
                    && $this->isInLocationTimeRange($location, $task['time_from'], $task['time_to']);
            });

            if ($index === false) {
                $this->addNewLocation($task);
                return;
            }

            $location = $this->locations[$index];
            $location['tasks']->push([
                'ref' => $task['ref'],
                'key' => $task['key']
            ]);
            $location['time_from'] = $task['time_from'] && $task['time_from']->isAfter($location['time_from']) ? $task['time_from'] : $location['time_from'];
            $location['time_to'] = $task['time_to'] && $task['time_to']->isBefore($location['time_to']) ? $task['time_to'] : $location['time_to'];
            $this->locations[$index] = $location;
        });

        return $this;
    }

    /**
     * @param array $task
     * @return void
     */
    private function addNewLocation(array $task) :void
    {
        $this->locations->push([
            'lat' => $task['lat'],
            'lng' => $task['lng'],
            'time_from' => $task['time_from'],
            'time_to' => $task['time_to'],
            'tasks' => collect([
                [
                    'ref' => $task['ref'],
                    'key' => $task['key']
                ]
            ])
        ]);
    }

    /**
     * @param $location
     * @param float $lat
     * @param float $lng
     * @return bool
     */
    private function isInLocationZone($location, float $lat, float $lng): bool
    {
        $theta = $lng - $location['lng'];
        $dist = sin(deg2rad($lat)) * sin(deg2rad($location['lat'])) +  cos(deg2rad($lat)) * cos(deg2rad($location['lat'])) * cos(deg2rad($theta));
        $dist = acos($dist);
        $dist = rad2deg($dist);
        $miles = $dist * 60 * 1.1515;
        return ($miles * 1.609344) <= (static::DISTANCE_OFFSET / 1000);
    }

    /**
     * @param array $location
     * @param Carbon|null $timeFrom
     * @param Carbon|null $timeTo
     * @return bool
     */
    private function isInLocationTimeRange(array $location, ?Carbon $timeFrom = null, ?Carbon $timeTo = null): bool
    {
        if (!$location['time_from'] || !$location['time_to'] || !$timeFrom || !$timeTo) {
            return true;
        }

        return $timeFrom->lessThanOrEqualTo($location['time_to'])
            && $timeTo->greaterThanOrEqualTo($location['time_from']);
    }

    /**
     * @param Task $task
     * @param array $data
     * @return bool
     */
    private function taskIsDirty(Task $task, array $data): bool
    {
        $fields = [
            'pickup_address_lat',
            'pickup_address_lng',
            'delivery_address_lat',
            'delivery_address_lng',
            'pickup_time_from',
            'pickup_time_to',
            'delivery_time_from',
            'delivery_time_to'
        ];

        foreach($fields as $field) {
            if (empty($data[$field]) && empty($task->{$field})) continue;

            if (preg_match('/(_address_)+(lat|lng)$/', $field) && (float)$data[$field] !== (float)$task->{$field}) {
                return true;
            }

            if (preg_match('/(_time_)+(from|to)$/', $field) && $data[$field] !== $task->{$field}) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array $task
     * @param TaskSet $taskSet
     * @return Task
     */
    private function createNewTask(array $task, TaskSet $taskSet): Task
    {
        $index = $this->tasksReferences->search(function (array $reference) use ($task) {
            return $reference['ref'] === $task['ref'];
        });

        $entity = new Task($task['data']);
        $entity->user_id = $this->data['user_id'];
        $entity->task_set_id = $taskSet->id;
        $entity->device_id = $this->data['device_id'];
        $entity->save();

        $reference = $this->tasksReferences[$index];
        $reference['id'] = $entity->id;
        $this->tasksReferences[$index] = $reference;
        return $entity;
    }
}