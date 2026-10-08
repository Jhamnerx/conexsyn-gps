<?php
namespace Tobuli\Entities;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Tobuli\Forwards\ForwardsManager;
use Tobuli\Traits\Searchable;

class Forward extends AbstractEntity
{
    use Searchable;

    protected $table = 'forwards';

    protected $fillable = [
        'active',
        'type',
        'title',
    ];

    protected $casts = [
        'payload' => 'array',
    ];

    protected $searchable = [
        'title',
        'payload'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', 1);
    }

    public function scopeUserOwned(Builder $query, User $user): Builder
    {
        return $query->where(['user_id' => $user->id]);
    }

    public function scopeUserShareable(Builder $query, User $user): Builder
    {
        return $query->where('shareable', 1);
    }

    public function scopeUserAccessible(Builder $query, User $user): Builder
    {
        return $query->where(function (Builder $query) use ($user) {
            $query->userOwned($user);
            $query->orWhere(function($q) use ($user){
                $q->userShareable($user);
            });
        });
    }

    public function scopeUserControllable(Builder $query, User $user): Builder
    {
        if ($user->isAdmin()) {
            return $query;
        }

        if ($user->isSupervisor()) {
            return $query;
        }

        if ($user->isManager())
            return $query->where(function (Builder $query) use ($user) {
                $query->whereManagerOwn('user_id', $user);
                $query->orWhereNull('user_id');
            });

        return $query->userOwned($user);
    }

    public function getTypeTitleAttribute()
    {
        return $this->type;
    }
}
