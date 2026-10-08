<?php

namespace Tobuli\Entities;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\File;
use Tobuli\Traits\Filterable;

class SensorIcon extends AbstractEntity
{
    use Filterable;

    protected $table = 'sensor_icons';

    protected $fillable = ['path', 'width', 'height'];

    protected $casts = ['id' => 'integer', 'order' => 'integer', 'width' => 'float', 'height' => 'float'];

    protected $filterables = [];

    public $timestamps = false;

    protected static function boot()
    {
        parent::boot();

        static::updating(function (SensorIcon $icon) {
            if (!$icon->isDirty('path')) {
                return;
            }

            $filename = public_path() . '/' . $icon->getOriginal('path');

            if (File::exists($filename)) {
                File::delete($filename);
            }
        });

        static::deleting(function (SensorIcon $icon) {
            $filename = public_path() . '/' . $icon->path;

            if (File::exists($filename)) {
                File::delete($filename);
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sensor(): BelongsTo
    {
        return $this->belongsTo(DeviceSensor::class);
    }
}
