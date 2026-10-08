<?php

namespace Tobuli\Entities;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Activity;
use Tobuli\Traits\Searchable;

class ModelChangeLog extends Activity
{
    use Searchable;

    protected array $searchable = [
        'log_name',
        'description',
        'causer_type',
        'subject_type',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function (ModelChangeLog $log) {
            if ($request = request()) {
                $log->ip = $request->ip();
            }
        });
    }

    public function attributesCount(): int
    {
        return count($this->properties['attributes'] ?? []);
    }

    public function getCauserName(): string
    {
        return $this->getNameProperty($this->causer);
    }

    public function getSubjectName(): string
    {
        return $this->getNameProperty($this->subject);
    }

    private function getNameProperty(?Model $model): string
    {
        if ($model instanceof DisplayInterface) {
            return $model->getDisplayName();
        }

        return '';
    }
}