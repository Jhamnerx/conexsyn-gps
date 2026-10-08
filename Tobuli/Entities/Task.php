<?php

namespace Tobuli\Entities;

use App\Events\TaskCreate;
use App\Events\TaskStatusChange;
use DB;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Tobuli\Traits\ChangeLogs;
use Tobuli\Traits\Customizable;
use Tobuli\Traits\DisplayTrait;
use Tobuli\Traits\Filterable;

class Task extends AbstractEntity implements DisplayInterface
{
    use ChangeLogs;
    use Customizable;
    use DisplayTrait;
    use Filterable;

    public static string $displayField = 'title';

    public static array $priorities = [
        1 => 'front.priority_low',
        2 => 'front.priority_normal',
        3 => 'front.priority_high'
    ];

    protected $fillable = [
        'device_id',
        'task_set_id',
        'title',
        'comment',
        'priority',
        'status',
        'invoice_number',
        'pickup_address',
        'pickup_address_lat',
        'pickup_address_lng',
        'pickup_time_from',
        'pickup_time_to',
        'pickup_ac',
        'pickup_ac_radius',
        'pickup_ac_duration',
        'pickup_ac_status',
        'pickup_ac_started_at',
        'pickup_ac_finished_at',
        'delivery_address',
        'delivery_address_lat',
        'delivery_address_lng',
        'delivery_time_from',
        'delivery_time_to',
        'delivery_ac',
        'delivery_ac_radius',
        'delivery_ac_duration',
        'delivery_ac_status',
        'delivery_ac_started_at',
        'delivery_ac_finished_at',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->status)) {
                $model->status = TaskStatus::STATUS_NEW;
            }
        });

        static::created(function ($model) {
            event(new TaskCreate($model));
        });

        static::saved(function ($model) {
            if ($model->isDirty('status') || $model->wasRecentlyCreated) {
                event(new TaskStatusChange($model));
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class, 'device_id', 'id');
    }

    public function taskSet(): BelongsTo
    {
        return $this->belongsTo(TaskSet::class);
    }

    public function statuses(): HasMany
    {
        return $this->hasMany(TaskStatus::class, 'task_id', 'id');
    }

    public function lastStatus(): HasOne
    {
        return $this->hasOne(TaskStatus::class, 'task_id')->orderBy('created_at', 'desc');
    }

    /**
     * @return BelongsToMany
     */
    public function locations(): BelongsToMany
    {
        return $this->belongsToMany(TaskSetLocation::class, 'task_set_location_tasks_pivot');
    }

    public function scopeAccessibleUser($query, $user_id)
    {
        return $query->join('user_device_pivot', function ($join) use ($user_id) {
            $join
                ->on('user_device_pivot.device_id', '=', 'tasks.device_id')
                ->on('user_device_pivot.user_id', '=', DB::raw("'$user_id'"));
        });
    }

    public function getDeviceNameAttribute()
    {
        return $this->device->name;
    }

    public function getStatusNameAttribute()
    {
        return trans(TaskStatus::$statuses[$this->status]);
    }

    public function getPriorityNameAttribute()
    {
        return trans(self::$priorities[$this->priority]);
    }
}