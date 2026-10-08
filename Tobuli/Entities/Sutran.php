<?php

namespace Tobuli\Entities;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Posición encolada para retransmitir a SUTRAN (MTC).
 *
 * La escribe IntegrationPositionRecorder, la despacha DispatchSutranJobs
 * y la borra SendDataSutranBatch cuando SUTRAN la acepta.
 */
class Sutran extends AbstractEntity
{
    protected $table = 'sutran';

    protected $fillable = [
        'device_id',
        'plate',
        'direction',
        'latitud',
        'longitud',
        'speed',
        'time_device',
        'other',
        'queued_at',
    ];

    protected $casts = [
        'other'     => 'array',
        'latitud'   => 'float',
        'longitud'  => 'float',
        'speed'     => 'float',
        'queued_at' => 'datetime',
    ];

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class, 'device_id', 'id');
    }

    /** SUTRAN identifica la unidad por placa: sin placa no hay envío. */
    public function scopeWithPlate(Builder $query): Builder
    {
        return $query->whereNotNull('plate')->where('plate', '!=', '');
    }

    /** Nunca despachadas, o despachadas hace más de $ttlMinutes (job perdido). */
    public function scopeReadyToDispatch(Builder $query, int $ttlMinutes): Builder
    {
        return $query->withPlate()->where(function (Builder $q) use ($ttlMinutes) {
            $q->whereNull('queued_at')
                ->orWhere('queued_at', '<', now()->subMinutes($ttlMinutes));
        });
    }
}
