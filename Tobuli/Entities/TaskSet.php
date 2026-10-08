<?php

namespace Tobuli\Entities;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Builder as QueryBuilder;

class TaskSet extends AbstractEntity
{
    /**
     * @var string[]
     */
    protected $fillable = [
        'user_id',
        'title',
    ];

    /**
     * @var array|string[]
     */
    protected array $searchable = [
        'title'
    ];

    /**
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo
     */
    public function device(): BelongsTo
    {
        return Relation::noConstraints(function () {
            return $this->belongsTo(Device::class)->where('id', fn (QueryBuilder $query) => $query
                ->select('device_id')
                ->from('tasks')
                ->where('task_set_id', $this->id)
                ->limit(1)
            );
        });
    }

    /**
     * @return HasMany
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    /**
     * @return HasMany
     */
    public function locations(): HasMany
    {
        return $this->hasMany(TaskSetLocation::class);
    }

    /**
     * @param Builder $query
     * @param User $user
     * @return Builder
     */
    public function scopeUserOwned(Builder $query, User $user): Builder
    {
        return $query->where(['user_id' => $user->id]);
    }

    /**
     * @param Builder $query
     * @param bool $adjustSelect
     * @return Builder
     */
    public function scopeSelectDeviceId(Builder $query, bool $adjustSelect = true): Builder
    {
        if ($adjustSelect) {
            $query->select('task_sets.*');
        }

        return $query->selectSub(fn (QueryBuilder $query) => $query
            ->select('device_id')
            ->from('tasks')
            ->whereColumn('task_set_id', 'task_sets.id')
            ->limit(1),
            'device_id');
    }
}
