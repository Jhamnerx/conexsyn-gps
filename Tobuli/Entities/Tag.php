<?php

namespace Tobuli\Entities;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Tobuli\Traits\Searchable;

/**
 * When adding new model relation, check
 * @see \Tobuli\Services\TagService::delete
 */
class Tag extends AbstractEntity
{
    use Searchable;

    protected $fillable = [
        'name',
        'is_common',
        'color',
    ];

    protected array $searchable = [
        'name',
        'color',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function devices(): MorphToMany
    {
        return $this->morphedByMany(Device::class, 'taggable');
    }

    public function scopeUserOwned(Builder $query, User $user): Builder
    {
        return $query->where('user_id', $user->id);
    }

    public function scopeUserControllable(Builder $query, User $user): Builder
    {
        if ($user->isAdmin()) {
            return $query;
        }

        return $query->userOwned($user);
    }

    public function scopeUserAccessible(Builder $query, User $user): Builder
    {
        if ($user->isAdmin()) {
            return $query;
        }

        return $query->where(function (Builder $query) use ($user) {
            $query
                ->where('is_common', 1)
                ->orWhere(fn (Builder $query) => $query->userOwned($user));

            if ($user->manager_id) {
                $query->orWhere('user_id', $user->manager_id);
            }
        });
    }
}
