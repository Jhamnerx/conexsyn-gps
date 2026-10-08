<?php

namespace Tobuli\Entities;

use Illuminate\Database\Eloquent\Builder;
use Tobuli\Traits\Filterable;
use Tobuli\Traits\Searchable;

class SentCommand extends AbstractEntity
{
    use Filterable;
    use Searchable;

    protected $table = 'sent_commands';

    protected $guarded = [];

    protected $casts = [
        'parameters' => 'array'
    ];

    protected array $searchable = [
        'device_imei',
        'command',
        'device.name',
        'template.title'
    ];

    protected array $filterables = [
        'connection',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function device()
    {
        return $this->belongsTo(Device::class, 'device_imei', 'imei');
    }

    public function actor()
    {
        return $this->morphTo();
    }

    public function template()
    {
        return $this->belongsTo(CommandTemplate::class);
    }

    public function stringifiedAttribute($attribute)
    {
        if (empty($this->$attribute))
            return '';

        if (is_string($this->$attribute))
            return $this->$attribute;

        $values = [];

        foreach ($this->$attribute as $key => $parameter)
            $values[] = "$key: $parameter";

        return implode(',', $values);
    }

    public function getCommandTitleAttribute()
    {
        if ($this->command != 'template')
            return $this->command;

        if ( ! $this->template)
            return $this->command;

        return $this->command . ' ' . $this->template->title;
    }

    public function scopeUserAccessible(Builder $query, User $user): Builder
    {
        if ($user->isAdmin()) {
            return $query;
        }

        if ($user->isSupervisor()) {
            return $query;
        }

        if ($user->isManager()) {
            return $query->whereManagerOwn('user_id', $user);
        }

        return $query->where('user_id', $user->id);
    }
}
