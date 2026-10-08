<?php

namespace Tobuli\Entities;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class TaskSetLocation extends AbstractEntity
{
    /**
     * @var bool
     */
    public $timestamps = false;

    /**
     * @var string[]
     */
    protected $fillable = [
        'task_set_id',
        'order',
        'lat',
        'lng',
        'time_from',
        'time_to'
    ];

    /**
     * @return BelongsTo
     */
    public function taskSet(): BelongsTo
    {
        return $this->belongsTo(TaskSet::class);
    }

    /**
     * @return BelongsToMany
     */
    public function tasks(): BelongsToMany
    {
        return $this->belongsToMany(Task::class, 'task_set_location_tasks_pivot')->withPivot([
            'task_order',
            'address_key'
        ]);
    }
}